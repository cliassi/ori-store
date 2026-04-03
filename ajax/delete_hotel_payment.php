<?php
session_start();
// ini_set('display_errors', '1');
// ini_set('display_startup_errors', '1');
// error_reporting(E_ALL);
require_once("../env.php");
require_once("../core/config.php");
require_once("../core/f.inc.php");


extract($_POST);
if($type == 0){
	$payment = R::load("hotel_statement_worker_income", $id);
	R::trash($payment);
} else{
	$payment = R::load("hotel_statement_worker_payment", $id);
	$official_receipt = R::findOne("official_receipt", "hotel_payment_id=?", [$payment->id]);
	if($official_receipt){
		// dd($official_receipt);
		R::trash($official_receipt);
	}
	// dd($payment);
	R::trash($payment);
}