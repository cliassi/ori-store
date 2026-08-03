<?php
session_start();
// ini_set('display_errors', '1');
// ini_set('display_startup_errors', '1');
// error_reporting(E_ALL);
require_once("../env.php");
require_once("../core/config.php");
require_once("../core/f.inc.php");

$restrictedUser = (uid() == 60 || uid() == 53 || (isset($_SESSION['store_username']) && $_SESSION['store_username'] == 'anowar'));

extract($_POST);


if(isset($update_quantity)){
	if ($restrictedUser) exit;
	update("invoice_item", "quantity=$quantity", "id=$invoice_item_id");
	update("stock_collect_item", "quantity=$quantity", "invoice_item_id=$invoice_item_id");
}
