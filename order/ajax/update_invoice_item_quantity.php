<?php
session_start();
require_once("../env.php");
require_once("../config.php");
require_once("../f.inc.php");

extract($_POST);


if(isset($update_quantity)){
	update("invoice_item", "quantity=$quantity", "id=$invoice_item_id");
	update("stock_collect_item", "quantity=$quantity", "invoice_item_id=$invoice_item_id");
}
