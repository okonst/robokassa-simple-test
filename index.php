<?php
/**
*	Тестовая система Robokassa - routing
*/

require('vendor/autoload.php');
require('config.php');

use Controllers\Robokassa;

// Обработчик ошибок Whoops (https://github.com/filp/whoops)
$whoops = new \Whoops\Run;
$whoops->prependHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();

// Робокасса контроллер
$robokassa = new Robokassa;

// Роутер Klein (https://github.com/klein/klein.php)
$router = new \Klein\Klein();


/* ROUTES
----------------------------------------------------*/

// Главная страница
$router->respond('GET', '/', function ($request, $response, $service) use($robokassa) {
	// view главной страницы
    $service->render('view.php', ['table' => $robokassa->getTable()]);
});

// Новый платеж
$router->respond(['GET', 'POST'], '/pay', function ($request, $response) use($robokassa){
	// сохраняем новый платеж
    $robokassa->newPayment($request->params());
    // редирект на страницу обработки платежа
    $response->redirect('/handle?InvId='.$request->param('InvId'));
});

// Новый рекуррентный (повторный) платеж
$router->respond(['GET', 'POST'], '/recurring', function ($request, $response) use($robokassa) {
    // проверяем корректность платежа
    $valid = $robokassa->validateRecurringPayment($request->params());
	if($valid){
		$robokassa->newPayment($request->params());
		$response->body('Recurring payment initiated');
	}
});

// Обработка платежа
$router->respond('GET', '/handle', function ($request, $response, $service) use($robokassa) {
	// находим платеж и создаем необходимые формы
	list($payment, $statusForm, $webhookForm) = $robokassa->handlePayment($request->param('InvId'));
    // view страницы обработки платежа
    $service->render('view.php', [
    	'table' => $robokassa->getTable(),
    	'payment' => $payment,
    	'statusForm' => $statusForm,
    	'webhookForm' => $webhookForm,
    ]);
});

// Апдейт статуса платежа
$router->respond('GET', '/update', function ($request, $response) use($robokassa) {
	// апдейтим статус
	$robokassa->updatePaymentStatus($request->param('InvId'), $request->param('status'));
    // редирект на страницу обработки платежа
    $response->redirect('/handle?InvId='.$request->param('InvId'));
});

// Удаление платежа
$router->respond('GET', '/remove', function ($request, $response) use($robokassa) {
	// Удаляем платеж
	$robokassa->deletePayment($request->param('InvId'));
    // редирект на главную страницу
    $response->redirect('/');
});

// Запрос к вебсервису о состоянии платежа
$router->respond(['GET', 'POST'], '/service/OpState', function ($request, $response) use($robokassa) {
	// обрабатываем запрос
	$xml = $robokassa->handleServiceOpState($request->params());
	// отправляем xml ответ
	$response->header('Content-type', 'text/xml; charset=utf-8');
	$response->body($xml);
});

// 404
$router->onHttpError(function ($code, $router) {
    if ($code == 404) $router->response()->body("Robokassa-Simple-Test: Page not found (error 404).<br/> Didn't you forget to direct your test domain to root folder?");
});


$router->dispatch();

