<?php

/**
 * Класс для имитации вебсервиса тестовой системы Robokassa
 *
 */

namespace Controllers;

class WebService{

	// Результаты запроса
	private $result = [
		0 => 'Операция найдена',
		1 => 'Неверная цифровая подпись запроса',
		2 => 'Информация о магазине с таким MerchantLogin не найдена или магазин не активирован',
		3 => 'Не удалось найти операцию',
		4 => 'Найдено две операции с таким InvoiceID',
	];


	/**
	 * Готовит ответ на запрос в формате xml
	 * @param arr $params - параметры запроса
	 * @param arr $payment - параметры платежа
	 */
	public function prepareXML($params, $payment){
		$resultCode = $this->validateRequest($params);

		// ошибка в запросе
		if($resultCode != 0){
			$response = simplexml_load_string($this->xmlError);
			$response->Result->Code = $resultCode;
			$response->Result->Description = $this->result[$resultCode];
			return (string) $response->asXML();
		}
		
		// платеж не найден (result code = 3)
		if(!$payment){
			$response = simplexml_load_string($this->xmlError);
			$response->Result->Code = 3;
			$response->Result->Description = $this->result[3];
			return (string) $response->asXML();
		}

		$response = simplexml_load_string($this->xml);
		// Result
		$response->Result->Code = 0;
		$response->Result->Description = $this->result[0];
		// State
		$response->State->Code = $payment['status'];
		$response->State->RequestDate = (string) (new \DateTime)->format(DATE_ATOM);
		$response->State->StateDate = (string) (new \DateTime($payment['payload']['created_at']))->format(DATE_ATOM);
		// Info
		$response->Info->IncCurrLabel = 'RUR';
		$response->Info->IncSum = $payment['payload']['OutSum'];
		$response->Info->IncAccount = "123123123";
		$response->Info->PaymentMethod->Code = 'BankCard';
		$response->Info->PaymentMethod->Description = 'Оплата банковской картой';
		$response->Info->OutCurrLabel = 'RUR';
		$response->Info->OutSum = $payment['payload']['OutSum'];

		return $response->asXML();
	}

	/**
	 * Валидация запроса и подписи
	 * @param arr $request - праметры запроса к веб-сервису
	 */
	public function validateRequest($request){
		if(!isset($request['MerchantLogin'])) throw new Exception("MerchantLogin Required", 1);		
		if(!isset($request['InvoiceID'])) throw new Exception("InvoiceID Required");		
		if(!isset($request['Signature'])) throw new Exception("Signature Required");

		if($request['MerchantLogin'] != MERCHANT_LOGIN) return 2;

		// сформировать подпись
    	$str = vsprintf('%s:%u:%s', [
            // '$login:$InvId:$validationPass'
            MERCHANT_LOGIN,
            $request['InvoiceID'],
            VALIDATION_PASS
        ]);
		// генерируем подпись
    	$signature = md5($str);

    	$valid = strtoupper($signature) == strtoupper($request['Signature']);
    	if(!$valid) return 1;

    	return 0;
	}


	// Шаблон ответа XML
	private $xml = <<<XML
<?xml version="1.0" encoding="utf-8" ?>
<OperationStateResponse xmlns="http://auth.robokassa.ru/Merchant/WebService/">
  <Result>
    <Code>integer</Code>
    <Description>string</Description>
  </Result>
  <State>
    <Code>integer</Code>
    <RequestDate>datetime</RequestDate>
    <StateDate>datetime</StateDate>
  </State>
  <Info>
    <IncCurrLabel>string</IncCurrLabel>
    <IncSum>decimal</IncSum>
    <IncAccount>string</IncAccount>
    <PaymentMethod>
      <Code>string</Code>
      <Description>string</Description>
    </PaymentMethod>
    <OutCurrLabel>string</OutCurrLabel>
    <OutSum>decimal</OutSum>
  </Info>
</OperationStateResponse>
XML;

	// Шаблон ответа с ошибкой
	private $xmlError = <<<XML
<?xml version="1.0" encoding="utf-8" ?>
<OperationStateResponse xmlns="http://auth.robokassa.ru/Merchant/WebService/">
  <Result>
    <Code>integer</Code>
    <Description>string</Description>
  </Result>
</OperationStateResponse>
XML;

}

	


?>
