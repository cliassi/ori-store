<?php
session_start();
require_once("../env.php");
require_once("../config.php");
require_once("../f.inc.php");


extract($_POST);


if(isset($update_price)){
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
