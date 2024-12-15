<?php
require_once("../env.php");
require_once("../core/config.php");
require_once("../core/f.inc.php");

if($_POST['id']){
	extract($_POST);
	update("invoice_item", "delivered_by='$delivered_by', delivered_at=now()", "id=$id");
}