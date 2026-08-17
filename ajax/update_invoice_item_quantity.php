<?php
session_start();
require_once("../env.php");
require_once("../core/config.php");
require_once("../core/f.inc.php");
require_once("../core/functions.php");

if (!canEditPriceAndQuantity()) {
    http_response_code(403);
    exit;
}

$invoice_item_id = isset($_POST['invoice_item_id']) ? intval($_POST['invoice_item_id']) : 0;
$quantity = isset($_POST['quantity']) ? floatval($_POST['quantity']) : 0;
$update_quantity = isset($_POST['update_quantity']) ? $_POST['update_quantity'] : '';

if ($update_quantity && $invoice_item_id > 0 && $quantity > 0) {
    update("invoice_item", "quantity=$quantity", "id=$invoice_item_id");
    update("stock_collect_item", "quantity=$quantity", "invoice_item_id=$invoice_item_id");
}
