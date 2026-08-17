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
$price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
$update_price = isset($_POST['update_price']) ? $_POST['update_price'] : '';

if ($update_price && $invoice_item_id > 0 && $price > 0) {
    $item = R::load("invoice_item", $invoice_item_id);
    if ($item && $item->id) {
        $item->old_price = $item->price;
        $item->price_updated_by = uid();
        $item->price_updated_at = now();
        $item->price = $price;
        R::store($item);
        print nf($item->quantity * $item->price);
    }
}
