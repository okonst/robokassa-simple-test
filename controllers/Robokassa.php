<?php
/**
 * Контроллер Robokassa
 *
 */

namespace Controllers;

use Controllers\Payment;
use Controllers\Webservice;


class Robokassa{

	// Payments
	private $payStorage;
	// Webservice
	private $webservice;

	/**
	 * Создает объект класса Robokassa
	 */
	public function __construct(){
		$this->payStorage = new Payment;
		$this->webservice = new Webservice;
	}

	/**
	 * Сохранить новый платеж
	 *	@param arr $params - параметры платежа
	 */
	public function newPayment($params){
		// убираем лишние параметры
		unset($params[0], $params['pay']);
		if(isset($params['PreviousInvoiceID'])) unset($params['recurring']);
		// сохраняем новый платеж
		$this->payStorage->savePayment((array) $params, 0);
		return;
	}

	/**
	 * Готовит инфо для обработки платежа
	 *	@param int $inv_id - id платежа (InvId)
	 */
	public function handlePayment($inv_id){
		if(!$inv_id) throw new \Exception("InvId parameter is required.");
		$payment = $this->payStorage->getPayment($inv_id);
		if(!$payment) throw new \Exception("Payment with InvId #$inv_id not found");
		$payment['payload'] = unserialize($payment['payload']);
		// Форма для апдейта статуса
		$statusForm = $this->getStatusForm($payment);
		// Форма отправки webhook
		$webhookForm = $this->getWebhookForm($payment);
		// Готовим url для редиректов
		$payment['success_url'] = SUCCESS_URL . "?" . $this->payStorage->prepareSuccessQuery($payment);
		$payment['fail_url'] = FAIL_URL . "?" . http_build_query([
			'OutSum' => $payment['payload']['OutSum'],
			'InvId' => $payment['inv_id'],
		]);
		// Описание типа платежа
		$type = 'Разовый';
		if (isset($payment['payload']['Recurring'])){
			$type = "Рекуррентный (начальный)";
		}elseif(isset($payment['payload']['PreviousInvoiceID'])){
			$type = "Рекуррентный (повторный) - ссылается на начальный платеж #" . $payment['payload']['PreviousInvoiceID'];
		}
		$payment['type'] = $type;
		return [$payment, $statusForm, $webhookForm];
	}

	/**
	 * Изменить статус платежа
	 *	@param int $inv_id - id платежа (InvId)
	 *	@param str $status - код статуса платежа
	 */
	public function updatePaymentStatus($inv_id, $status){
		$this->payStorage->updatePaymentStatus($inv_id, $status);
		return;
	}

	/**
	 * Удалить платеж
	 *	@param int $inv_id - id платежа (InvId)
	 */
	public function deletePayment($inv_id){
		$this->payStorage->deletePayment($inv_id);
		return;
	}

	/**
	 * Обрабатывает запрос к сервису OpState о состоянии платежа
	 *	@param arr $params - параметры запроса
	 */
	public function handleServiceOpState($params){
		// находим платеж
		$payment = $this->payStorage->getPayment($params['InvoiceID']);
		if($payment) $payment['payload'] = unserialize($payment['payload']);
		unset($params[0], $params['/service/OpState']);
		// формируем ответ в xml
		$xml = $this->webservice->prepareXML($params, $payment);
		return $xml;
	}

	/**
	 * Проверяет параметры рекуррентного (повторного) платежа
	 *	@param arr $params - параметры рекуррентного платежа
	 */
	public function validateRecurringPayment($params){
		if(!isset($params['InvId'])) throw new \Exception("InvId Required");
		if(!isset($params['PreviousInvoiceID'])) throw new \Exception("PreviousInvoiceID Required");
		if(array_key_exists('IncCurrLabel', $params)) throw new \Exception("Recurring payment cannot have 'IncCurrLabel' param");
		if(array_key_exists('ExpirationDate', $params)) throw new \Exception("Recurring payment cannot have 'ExpirationDate' param");
		if(array_key_exists('Recurring', $params)) throw new \Exception("Recurring payment cannot have 'Recurring' param");
		
		// Проверяем начальный платеж
		$paymentInitial = $this->payStorage->getPayment($params['PreviousInvoiceID']);
		// если нет такого платежа
		if(!$paymentInitial) throw new \Exception("Initial payment with PreviousInvoiceID #".$params['PreviousInvoiceID']." is not found");
		$paymentInitial['payload'] = unserialize($paymentInitial['payload']);
		// если начальный платеж был без параметра recurring
		if($paymentInitial['payload']['Recurring'] != 1) throw new \Exception("Initial payment with PreviousInvoiceID #".$params['PreviousInvoiceID']." was not recurring");
		// если начальный платеж не удался
		if($paymentInitial['status'] != 100) throw new \Exception("Initial payment with PreviousInvoiceID #".$params['PreviousInvoiceID']." was not successful");
		
		return true;
	}

	/**
	 * Готовит таблицу платежей
	 */
	public function getTable(){
		$payments = $this->payStorage->getPayments();

		$table = "<table class='table table-condensed table-bordered'><thead><tr>".
		"<th>Id</th>".
		"<th>Inv_Id</th>".
		"<th>Payload</th>".
		"<th>Status</th>".
		"<th>Created</th>".
		"<th>Actions</th>".
		"</tr></thead><tbody>";

		foreach ($payments as $key => $pay) {
			$btnDelete = "<a class='btn btn-danger btn-sm' href='/remove?InvId={$pay['inv_id']}'>Delete</a>";
			$btnHandle = "<a class='btn btn-primary btn-sm' href='/handle?InvId={$pay['inv_id']}' style='margin-bottom:5px;'>Handle</a>";

			$payload = json_encode(unserialize($pay['payload']), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

			//$payload = print_r(unserialize($pay['payload']), true);

			$status = "<form action='{$_SERVER['SCRIPT_NAME']}/update'><input type='hidden' name='InvId' value='{$pay['inv_id']}'>";
			$status .= "<select class='form-control input-sm' name='status' style='width:auto;margin-bottom:5px;'>";
			//$btnUpdate = $this->getButton($pay, 'update', 'primary', 'xs');
			$btnUpdate = "<button class='btn btn-primary btn-xs' type='submit'>Update</button>";
			foreach ($this->payStorage->status as $key => $value) {
				$status .= "<option value=".$key." ". ($pay['status'] == $key ? "selected" : "") .">$key - $value</option>";
			}
			$status .= "</select>{$btnUpdate}</form>";

			$table .= "<tr>";
			$table .= "<td>{$pay['id']}</td>";
			$table .= "<td>{$pay['inv_id']}</td>";
			$table .= "<td>{$payload}</td>";
			$table .= "<td>{$pay['status']} - {$this->payStorage->status[$pay['status']]}</td>";
			$created = (new \DateTime($pay['created_at']))->format('d.m.Y H:i');
			$table .= "<td>{$created}</td>";
			$table .= "<td>$btnHandle $btnDelete</td>";
			$table .= "</tr>";
		}

		if(count($payments) == 0) $table .= "<tr><td colspan=6 align='center'>Платежи отсутствуют</td></tr>";

		$table .= "</tbody></table>";

		return $table;
	}

	/**
	 * Готовит форму для изменения статуса платежа
	 *	@param arr $payment - параметры платежа
	 */
	public function getStatusForm($payment){
		$status = "<form action='/update' class='form-inline'><input type='hidden' name='InvId' value='{$payment['inv_id']}'>";
		$status .= "<div class='input-group'>";
		$status .= "<select class='form-control input-sm' name='status'>";
		//$btnUpdate = $this->getButton($pay, 'update', 'primary', 'xs');
		$btnUpdate = "<button class='btn btn-primary btn-sm' type='submit'>Update</button>";
		foreach ($this->payStorage->status as $key => $value) {
			$status .= "<option value=".$key." ". ($payment['status'] == $key ? "selected" : "") .">$key - $value</option>";
		}
		$status .= "</select><span class='input-group-btn'>{$btnUpdate}</span></div></form>";
		return $status;
	}

	/**
	 * Готовит форму отправки webhook
	 * @param arr $payment - параметры платежа
	 */
	public function getWebhookForm($payment){
		$btnSend = "<button class='btn btn-primary btn-sm' type='submit'>Отправить webhook</button>";
		$status = "<form action='".WEBHOOK_URL."' method='POST' target='_blank' class='form-inline'>";
		// Поля
		$status .= "<input type='hidden' name='InvId' value='{$payment['inv_id']}'>";
		$status .= "<input type='hidden' name='OutSum' value='{$payment['payload']['OutSum']}'>";
		$status .= "<input type='hidden' name='EMail' value='{$payment['payload']['Email']}'>";
		$status .= "<input type='hidden' name='Fee' value='". (number_format($payment['payload']['OutSum'] * 0.04, 2)) ."'>";
		// Добавляем пользовательские параметры
    	$customParams = array_filter($payment['payload'], function($key){
    		return mb_substr($key, 0, 3) == 'shp';
    	}, ARRAY_FILTER_USE_KEY);
    	foreach ($customParams as $key => $val) {
    		$status .= "<input type='hidden' name='$key' value='{$val}'>";
    	}
		// Подпись
		$signatureValue = $this->payStorage->makeSignature($payment['payload'], 'validationPass');
		$status .= "<input type='hidden' name='SignatureValue' value='{$signatureValue}'>";
		
		$status .= "{$btnSend}</form>";
		return $status;
	}


}
	

?>
