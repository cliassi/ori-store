<?php
require_once("../env.php");
require_once("../core/config.php");
require_once("../core/f.inc.php");

if($_POST['pin']){
	extract($_POST);
	$count = getFieldValue("sys_user", "COUNT(*)", "id=".uid()." AND u_pin='$pin'");
	if($count == 1){
		$token = uuid();
		insert("sys_token", "user_id, token", uid().",'$token'");
		print $token;
	} else{
		print "";
	}
}
