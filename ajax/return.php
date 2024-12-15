<?php
require_once("../env.php");
require_once("../core/config.php");
require_once("../core/f.inc.php");

if($_POST['id']){
	extract($_POST);
	// $item = R::load("stock_collect_item")
	update("stock_collect_item", "returned_quantity=$quantity", "id=$id");
}