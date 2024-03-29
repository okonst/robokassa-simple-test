# robokassa-simple-test

Пакет для тестирования интеграции Вашего магазина с платежной системой [Робокасса](https://www.robokassa.ru). Сервис Робокасса предоставляет ограниченные возможности для проведения тестовых платежей. Поэтому для полноценной проверки того, насколько корректно Вы сделали интеграцию, можно использовать этот пакет.

Пакет позволяет тестировать:

* Разовые платежи
* Рекуррентные платежи
* Редирект на successUrl и failUrl
* Отправку webhook при успешной оплате
* Получение информации о статусе платежа через веб-сервис OpState

Документация по настройке работы с системой Robokassa - [ссылка](https://docs.robokassa.ru).

## Установка и настройка

1. Клонировать пакет с помощью git в отдельную папку.

2. Установить пакеты `composer install`.

3. Направить локальный домен (например robotest.tst) в корневую папку.

4. В файле `config.php` вставить параметры Вашего магазина:

 * `SUCCESS_URL` - url для редиректа в случае успеха платежа
 * `FAIL_URL` - url для редиректа в случае неудачи платежа
 * `WEBHOOK_URL` - url для отправки webhook уведомлений
 * `MERCHANT_LOGIN` - ID магазина в системе Робокасса
 * `PAYMENT_PASS` - тестовый пароль 1 магазина в системе Робокасса
 * `VALIDATION_PASS` - тестовый пароль 2 магазина в системе Робокасса

5. В локальных настройках Вашего магазина вставить параметры тестовой системы вместо параметров Robokassa:

 * url для платежей - вместо `https://auth.robokassa.ru/Merchant/Index.aspx` вставляете `http://robotest.tst/pay`.
 * url для рекуррентных платежей - вместо `https://auth.robokassa.ru/Merchant/Recurring` вставляете `http://robotest.tst/recurring`.
 * url для вебсервиса - вместо `https://auth.robokassa.ru/Merchant/WebService/Service.asmx` вставляете `http://robotest.tst/service`.

В настройках необходимо учесть, что платежи осуществляются методом `GET` или `POST`, уведомления webhook отправляются методом `POST`. Шифрование и проверка подписи signature осуществляется методом `md5`;

## Тестовые платежи

При проведении тестового платежа в магазине Вы переходите на тестовую страницу http://robotest.tst/pay, где можете управлять статусом платежа, осуществлять редиректы, отправлять webhook уведомления.

Используются следующие статусы платежей:

 * `0 - new` - новый платеж.
 * `5 - initiated` - операция только инициализирована, деньги от покупателя не получены.
 * `10 - cancelled` - операция отменена, деньги от покупателя не были получены.
 * `50 - processing` - деньги от покупателя получены, производится зачисление денег на счет магазина.
 * `60 - returned` - деньги после получения были возвращены покупателю.
 * `80 - suspended` – исполнение операции приостановлено.
 * `100 - completed` - операция выполнена, завершена успешно.

## Лицензия

MIT