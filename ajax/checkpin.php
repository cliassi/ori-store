<?php
session_start();
// ini_set('display_errors', '1');
// ini_set('display_startup_errors', '1');
// error_reporting(E_ALL);
require_once("../env.php");
require_once("../core/config.php");
require_once("../core/f.inc.php");

if($_POST['pin']){
	extract($_POST);
	$count = getFieldValue("sys_user", "COUNT(*)", "id=".uid()." AND u_pin='$pin'");
	// dd($count);
	if($count == 1){
		$token = uuid();
		insert("sys_token", "user_id, token", uid().",'$token'");
		print $token;
	} else{
		print "";
	}
}
