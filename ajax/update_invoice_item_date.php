<?php
session_start();
// ini_set('display_errors', '1');
// ini_set('display_startup_errors', '1');
// error_reporting(E_ALL);
require_once("../env.php");
require_once("../core/config.php");
require_once("../core/f.inc.php");

extract($_POST);


if(isset($update_date)){
    $ids = array_filter(array_map('intval', explode(',', $invoice_item_id)));
    $invoice_item_id = implode(',', array_unique($ids));
    if (!$invoice_item_id || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        exit;
    }
    $date = mysqli_real_escape_string($c, $date);
	//updatep("invoice_item", "delivery_date='$date'", "product_variance_id=$item->product_variance_id and delivery_date <= curdate() AND quantity > delivered and invoice_id IN  (SELECT id FROM invoice WHERE customer_id=$inv->customer_id)");
	// updatep("invoice_item", "delivery_date='$date'", "product_variance_id IN ($item->product_variance_id) and quantity > delivered and invoice_id IN  (SELECT id FROM invoice WHERE customer_id=$inv->customer_id)");
	update("invoice_item", "delivery_date='$date'", "id IN ($invoice_item_id)");
    update("invoice", "invoice_date='$date'", "id IN (SELECT invoice_id FROM invoice_item WHERE id IN ($invoice_item_id))");
    print df($date);
}
