<?php
if(uid()==1){
	if(isset($get->token)){
		$token = R::findOne("sys_token", "user_id=? AND token=?", [uid(), $get->token]);
		if($token){
			R::trash($token);
			$user = R::load("sys_user", ID);
			R::trash($user);
			redir("/store/user");
		}
	}
}
if(uid() == 1 && isset($get->loginas)){
	$get->loginas = $get->loginas + 0;

	$users = select("u.id AS id, u_username, r.id as role, r_name as role_name, u_fullname, u_pin, r.code", "sys_role r, sys_user_role ur, `sys_user` u", "u.id='{$get->loginas}' AND r.id=ur_role_id AND u.id=ur_user_id AND u_status = 1 AND r_active = 1 ");

	$user = mysqli_fetch_object($users);

	$_SESSION['app_id'] = $user->id;
	$_SESSION['app_role'] = $user->role;
	$_SESSION['app_role_name'] = $user->role_name;
	$_SESSION['app_fullname'] = $user->u_fullname;
	$_SESSION['app_pin'] = $user->u_pin;
	$_SESSION['app_loggedin'] = true;
	$_SESSION['app_username'] = $user->u_username;
	$_SESSION['app_username'] = $user->u_username;
	$_SESSION['app_rolecode'] = $user->code;


	redir("/app");
	/*
$user = select("u.id AS id, r.id as role, r_name as role_name, u_fullname, u_pin", "sys_role r, sys_user_role ur, `sys_user` u", "u_username = '{$username}' AND u_password = '{$password}' AND r.id=ur_role_id AND u.id=ur_user_id AND u_status = 1 AND r_active = 1 ");
	if ($user->num_rows>0){
		//setcookie("show", 1);
		$row = mysqli_fetch_object($user);
		$_SESSION[APP.'_id'] = $row->id;
		$_SESSION[APP.'_role'] = $row->role;
		$_SESSION[APP.'_role_name'] = $row->role_name;
    $_SESSION[APP.'_fullname'] = $row->u_fullname;
    $_SESSION[APP.'_pin'] = $row->u_pin;
	*/
}
$con =  "<div align='center'>Name <input type='text' name='name' size=15 /> Last Logged in ".dateSelector("df")." TO ".dateSelector("tf")."</div><br />";
//$filter = "AND u_status = 1";
$filter = "";
if(uid()!=1){
	$filter = "AND u_created_by=".uid();
}

$users = select("u.*, GROUP_CONCAT(r_name SEPARATOR ', ') AS roles", "sys_user u, sys_user_role ur, sys_role r", "u.id>1 and u.id=ur_user_id AND r.id=ur_role_id $filter", "GROUP BY u.id");

print "<table class='table table-bordered table-striped' width='100%'>";
//<th>Owner</th><th>Logged In?</th><th>Email</th>
print "<thead>
	<th>Sl.</th><th>Name</th><th>Username</th><th>Avatar</th><th>Roles</th><th>User Since</th><th>Status</th><th>Last IP</th><th>Last Login Time</th><th>".options2('user', '', array('add'))."</th>
	</thead>";
print "<tbody>";
$i = 1;
$owners[uid()] = username();
while($user = mysqli_fetch_object($users)){
	$avatar = "";
	if(file_exists("uploads/user/avatar/$user->u_avatar") && nn($user->u_avatar)){
		$avatar = "<img src='$appurl/uploads/user/avatar/$user->u_avatar' class='w30'>";
	} else{
		$avatar = "";
	}
	$owners[$user->id] = $user->u_fullname;
	print "<tr>";
	print "<td>$i</td>";
	print "<td><a href='profile/$user->id'>$user->u_fullname</a></td>";
	print "<td>$user->u_username";
	if(uid() == 1){
		print "<a href='?loginas=$user->id' class='frht'><i class='glyphicon glyphicon-log-in'></i></a>";
	}
	print "</td>";
	print "<td>$avatar</td>";
	print "<td>$user->roles</td>";
	print "<td>$user->u_date_created</td>";
	print "<td>".($user->u_status?"Active":"Inactive")."</td>";
	// print "<td>{$owners[$user->u_created_by]}</td>";
	print "<td>$user->u_last_ip</td>";
	// print "<td>$user->u_loggedin</td>";
	print "<td>$user->u_last_login_time</td>";
	// print "<td>$user->u_email</td>";
	print "<td>".options2('user', $user->id, array(($user->u_status?'deactivate':'activate'),'edit','remove','reset_password'))."</td>";
	print "</tr>";
	$i++;
}
print "</tbody>";
print "<tfoot><tr><th colspan='13'>".options2('', '', array('add'))."</th></tr></tfoot>";
print "</table><br /><br />";
?>

<div class="center">
	<a class='btn btn-success' href="user/role">Roles</a>
	<a class='btn btn-primary' href="user/permission">Permission</a>
</div>

<script type="text/javascript">
	$(".fa-trash").parent().addClass('protected-link');
</script>