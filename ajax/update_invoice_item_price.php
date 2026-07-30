<?php
session_start();
// ini_set('display_errors', '1');
// ini_set('display_startup_errors', '1');
// error_reporting(E_ALL);
require_once("../env.php");
require_once("../core/config.php");
require_once("../core/f.inc.php");

$restrictedUser = (uid() == 53 || (isset($_SESSION['store_username']) && $_SESSION['store_username'] == 'anowar'));

extract($_POST);


if(isset($update_price)){
	if ($restrictedUser) { print ""; exit; }
	$item = R::load("invoice_item", $invoice_item_id);
	// if($item->price < $price){
		$item->old_price = $item->price;
		$item->price_updated_by = uid();
		$item->price_updated_at = now();
		$item->price = $price;
		R::store($item);
		print nf($item->quantity * $item->price);
	// }
	print "";
}
