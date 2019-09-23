<?php
/**
 * Управление базой платежей Robokassa (sqlite)
 *
 */

namespace Controllers;

/**
 * Класс SQLite3 для работы с database
 */
class MyDB extends \SQLite3
{
    function __construct(){
        $this->open('./database/sqlite.db');
    }
}

/**
 * Класс Payment для работы с платежами
 */
class Payment{
	
	// Database
	public $db;

	// Платежи
	public $payments = [];
	
	// Статусы платежей
	public $status = [
		0 => 'new',
	    5 => 'initiated',
	    10 => 'cancelled',
	    50 => 'processing',
	    60 => 'returned',
	    80 => 'suspended',
	    100 => 'completed',
	];

	/**
	 * Создает объект класса Payment
	 */
	public function __construct(){
		// Инициализация базы
		$this->db = new MyDB();
		// Создаем таблицу (если ее нет)
		$this->db->exec("CREATE TABLE IF NOT EXISTS `payments` (
		  `id` INTEGER PRIMARY KEY NOT NULL,
		  `inv_id` INTEGER NOT NULL,
		  'payload' TEXT NOT NULL,
		  `status` VARCHAR,
		  `created_at` TEXT
		);");
	}

	/**
	 * Получить все платежи
	 */
	public function getPayments(){
		$res = $this->db->query("SELECT * FROM `payments`;");
		$out = [];
		while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
		    $out[] = $row;
		}
		return $out;
	}

	/**
	 * Получить платеж по id
	 * @param int $inv_id - id платежа (InvId)
	 */
	public function getPayment($inv_id){
		$res = $this->db->query("SELECT * FROM `payments` WHERE `inv_id` = $inv_id LIMIT 1;");
		return $res->fetchArray(SQLITE3_ASSOC);
	}

	/**
	 * Сохранить платеж
	 * @param arr $params - параметры платежа
	 * @param str $status - статус платежа (optional)
	 */
	public function savePayment($params, $status = null){
		if(!isset($params['InvId'])){
			throw new \Exception("InvId Required");
		}
		if(! $this->verifySignature($params, 'paymentPass')){
			throw new \Exception("Signature Is Not Valid");
		}
		if($this->getPayment($params['InvId'])){
			throw new \Exception("Payment with InvId #".$params['InvId']." already exists.");
		}
		$str = $this->db->escapeString(serialize($params));
		// готовим insert
		$stmt = $this->db->prepare("INSERT INTO `payments` (inv_id, payload, status, created_at) VALUES (:inv_id, :payload, :status, :created_at);");
		$stmt->bindValue(':inv_id', $params['InvId']);
		$stmt->bindValue(':payload', $str);
		$stmt->bindValue(':status', $status);
		$stmt->bindValue(':created_at', (string) (new \DateTime)->format(DATE_ATOM));
		$stmt->execute();
	}

	/**
	 * Изменить статус платежа
	 * @param int $inv_id - id платежа (InvId)
	 * @param str $status - статус платежа
	 */
	public function updatePaymentStatus($inv_id, $status = null){
		$this->db->exec("UPDATE `payments` SET `status` = ". $status .
			" WHERE `inv_id` = ". $inv_id .";");
	}

	/**
	 * Удалить платеж
	 * @param int $inv_id - id платежа (InvId)
	 */
	public function deletePayment($inv_id){
		$this->db->exec("DELETE FROM `payments` WHERE `inv_id` = ". $inv_id .";");
	}

	/**
	 * Проверить подпись
	 * @param arr $params - параметры запроса
	 * @param str $pass - вид пароля (validationPass / paymentPass)
	 */
	public function verifySignature($params, $pass = 'validationPass'){
		// основные параметры
    	//$str = "$sum:$orderId:$merchantPass2";
    	$str = vsprintf('%s:%01.2f:%u:%s:%s', [
    		MERCHANT_LOGIN,
            $params['OutSum'],
            $params['InvId'],
            $params['Receipt'],
            ($pass == 'validationPass' ? VALIDATION_PASS : PAYMENT_PASS)
        ]);
    	// добавляем пользовательские параметры
    	$customParams = array_filter($params, function($key){
    		return strtoupper(mb_substr($key, 0, 3)) == 'SHP';
    	}, ARRAY_FILTER_USE_KEY);
    	if($customParams && count($customParams) > 0){
    		// sort params alphabetically
            ksort($customParams);
    		foreach ($customParams as $key => $value) {
    			$str .= ":$key=$value";
    		}
    	}

    	return strtoupper($params['SignatureValue']) === strtoupper(md5($str));
	}

	/**
	 * Сформировать подпись
	 * @param arr $params - параметры запроса
	 * @param str $pass - вид пароля (validationPass / paymentPass)
	 */
	public function makeSignature($params, $pass = 'validationPass'){
		
		$str = vsprintf('%01.2f:%u:%s', [
            $params['OutSum'],
            $params['InvId'],
            ($pass == 'validationPass' ? VALIDATION_PASS : PAYMENT_PASS)
        ]);

        // добавляем пользовательские параметры
    	$customParams = array_filter($params, function($key){
    		return strtoupper(mb_substr($key, 0, 3)) == 'SHP';
    	}, ARRAY_FILTER_USE_KEY);
    	if($customParams && count($customParams) > 0){
    		// sort params alphabetically
            ksort($customParams);
    		foreach ($customParams as $key => $value) {
    			$str .= ":$key=$value";
    		}
    	}
    	return strtoupper(md5($str));
	}

	/**
	 * Готовит строку query для редиректа на success_url
	 * @param arr $payment - параметры платежа
	 */
	public function prepareSuccessQuery($payment){
		$query = [
			'OutSum' => $payment['payload']['OutSum'],
			'InvId' => $payment['inv_id'],
		];
		// добавляем пользовательские параметры
    	$customParams = array_filter($payment['payload'], function($key){
    		return strtoupper(mb_substr($key, 0, 3)) == 'SHP';
    	}, ARRAY_FILTER_USE_KEY);
    	foreach ($customParams as $key => $val) {
    		$query[$key] = $val;
    	}
    	// добавляем подпись
    	$query['SignatureValue'] = $this->makeSignature($payment['payload'], 'paymentPass');

    	return http_build_query($query);
	}

	
}
	


?>
