<?php
session_start();
require_once("../env.php");
require_once("../config.php");
require_once("../f.inc.php");
require_once("../core/functions.php");

extract($_POST);

if (!canEditDateOnly()) {
    http_response_code(403);
    exit;
}

if(isset($update_date)){
    $item = R::load('invoice_item', $invoice_item_id);
    $inv = R::load('invoice', $item->invoice_id);
	//updatep("invoice_item", "delivery_date='$date'", "product_variance_id=$item->product_variance_id and delivery_date <= curdate() AND quantity > delivered and invoice_id IN  (SELECT id FROM invoice WHERE customer_id=$inv->customer_id)");
	// updatep("invoice_item", "delivery_date='$date'", "product_variance_id IN ($item->product_variance_id) and quantity > delivered and invoice_id IN  (SELECT id FROM invoice WHERE customer_id=$inv->customer_id)");
	update("invoice_item", "delivery_date='$date'", "id IN ($invoice_item_id)");
    update("invoice", "invoice_date='$date'", "id IN ($inv->id)");
    print df($date);
}
