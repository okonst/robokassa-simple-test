<?php

/**
 *   View основной страницы тестовой системы Robokassa
 */

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Document</title>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
	<link href="https://fonts.googleapis.com/css?family=Roboto:400,700&display=swap&subset=cyrillic" rel="stylesheet">
	<style>
		.container{
			font-family: 'Roboto', sans-serif;
		}
		.container-fluid{
			background-color: #f2f2f2;
			margin-bottom: 20px;
		}
		.row.form {
			background-color: #f2f2f2;
			padding-top: 18px;
			padding-bottom: 18px;
			border-radius: 5px;
		}
		.row.param{
			border-bottom: 1px solid #f2f2f2;
		}
		.section {
			margin-top: 15px;
			margin-bottom: 15px;
		}
		.type{ 
			padding: 8px 10px;
			margin-bottom: 10px;
		 }
		 h1{
		 	margin-bottom: 20px;
		 }
		 h1 img{
		 	vertical-align: baseline;
		 	width: 150px;
		 	float: right;
		 }
		 .table th{
		 	background-color: #f2f2f2;
		 }
	</style>
</head>
<body>
	<div class="container-fluid">
	  <div class="container">
	    <h1>Тестовая система ROBOKASSA <img src="https://www.robokassa.ru/Images/logo.png" alt=""></h1>
	  </div>
	</div>
	<div class="container">
		
		<!-- ОБРАБОТКА ПЛАТЕЖА -->
		<? if(isset($this->payment)){ ?>
			
			<h3>Тестовый платеж #<? echo $this->payment['inv_id'] ?></h3>
			<div class="bg-info text-info type">
				Тип платежа: <? echo $this->payment['type'] ?>
				<span class='pull-right'>создан: <? echo (new \DateTime($this->payment['created_at']))->format('d.m.Y H:i') ?></span>
			</div>
			
			<!-- ПАРАМЕТРЫ ПЛАТЕЖА -->
			<div class="col-md-12">
				<? foreach($this->payment['payload'] as $key => $value){ ?>
					<div class="row param">
						<div class="col-md-2"><b><? echo "$key:" ?></b></div>
						<div class="col-md-6 text-info"><? echo "$value"; ?></div>
					</div>
				<? } ?>
			</div>
			
			<!-- ФОРМА УПРАВЛЕНИЯ -->
			<div class="col-md-12 section">
				<div class="row form">
					<div class="col-md-3">
						<label>Статус платежа:</label>
						<? echo $this->statusForm ?>
					</div>
					<div class="col-md-4">
						<label>Редирект:</label>
						<div>
							<a href="<? echo $this->payment['success_url'] ?>" class="btn btn-success btn-sm" target="_blank">Редирект на success_url</a>
							<a href="<? echo $this->payment['fail_url'] ?>" class="btn btn-danger btn-sm" target="_blank">Редирект на fail_url</a>
						</div>
					</div>
					<div class="col-md-3">
						<label>Подтверждение оплаты:</label>
						<? echo $this->webhookForm ?>
					</div>
				</div>
			</div>
			
			<div class="section">
				<a href="/" class="btn btn-default btn">Назад</a>
			</div>
			<hr/>
		<? } ?>

		<!-- ТАБЛИЦА С ПЛАТЕЖАМИ -->
		<h3>Платежи</h3>
		<div class="table-responsive">
			<? echo $this->table; ?>
		</div>


	</div>
</body>
</html>
