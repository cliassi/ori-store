<?php
session_start();
require_once("../env.php");
require_once("../core/config.php");
require_once("../core/f.inc.php");
require_once("../core/functions.php");

$update_date = isset($_POST['update_date']) ? $_POST['update_date'] : '';
$date = isset($_POST['date']) ? $_POST['date'] : '';
$invoice_item_id = isset($_POST['invoice_item_id']) ? $_POST['invoice_item_id'] : '';

if (!canEditAnything()) {
    http_response_code(403);
    exit;
}

if ($update_date) {
    $ids = array_filter(array_map('intval', explode(',', $invoice_item_id)));
    $invoice_item_id = implode(',', array_unique($ids));
    if (!$invoice_item_id || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        exit;
    }
    $date = mysqli_real_escape_string($c, $date);
    update("invoice_item", "delivery_date='$date'", "id IN ($invoice_item_id)");
    update("invoice", "invoice_date='$date'", "id IN (SELECT invoice_id FROM invoice_item WHERE id IN ($invoice_item_id))");
    print df($date);
}
