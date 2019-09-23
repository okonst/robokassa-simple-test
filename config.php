<?php

/**
 * Конфиг тестовой системы Robokassa
 *
 * Необходимо вставить параметры Вашего магазина, которые указаны на
 * странице технических настроек Вашего магазина в сервисе Robokassa.
 *
 */


// Редирект url в случае успеха платежа
const SUCCESS_URL = "http://your-domain.ru/your-success-redirect-url";

// Редирект url в случае неудачи платежа
const FAIL_URL = "http://your-domain.ru/your-fail-redirect-url";

// Url для уведомлений webhook
const WEBHOOK_URL = "http://your-domain.ru/your-webhook-url";

// ID магазина
const MERCHANT_LOGIN = "your-shop-id";

// Тестовый пароль 1 в Робокассе
const PAYMENT_PASS = "your-test-pass1";

// Тестовый пароль 2 в Робокассе
const VALIDATION_PASS = "your-test-pass2";
	


?>
