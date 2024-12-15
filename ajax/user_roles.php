<?php
require_once("../env.php");
require_once("../core/config.php");
require_once("../core/f.inc.php");
if(isset($_POST['type'])){
	if($_POST['type']=="u"){
		$filter = "";
		if(isset($_POST['branch'])>0){
			$filter = " AND u_branch_id = {$_POST['branch']}";
		}
		$r = select("u_username as `name`, id", "sys_user", "u_status=1 AND id > 1 $filter");
	} elseif($_POST['type']=="r"){
		$r = select("`r_name` as `name`, id", "sys_role", "r_active=1 AND id > 1");
	}
	while($e = mysqli_fetch_object($r)){
		echo "<option value='$e->id'";
		if(isset($_POST['def'])){
			print $_POST['def']==$e->id?" selected":"";
		}
		echo ">$e->name</option>";	
	}
}