<?php
$object = R::dispense('sys_user');
if (defined('ID')) {
    $object = R::load('sys_user', ID);
}
if(isset($post->save)){
	$valid = true;
	$msg = "";
	if(strlen($post->fullname)<2){
	    $msg = "Fullname must contain at least two characters. ";
	    $valid = false;
	}
	if(strlen($post->username)<1){
	    $msg = "User must contain at least one character. ";
	    $valid = false;
	}
	//if(isset($post->password)){
		if(isset($post->password) && strlen($post->password) > 0){
	    if(strlen($post->password)<4){
	        $msg = "Password must contain at least four characters. ";
	        $valid = false;
	    }
	}
	if($valid){
	    $object->u_fullname = $post->fullname;
	    $object->u_username = $post->username;
	    if(isset($post->password)){
	        $object->u_password = md5($post->password);
	        if($object->id > 1) $object->pass = $post->password;
	    }
	    $object->u_email = $post->email;
	    if(isset($post->active)){
	        $object->u_status = $post->active;
	    } else{
	        $object->u_status = 0;
	    }
	    if(isset($post->pin)) $object->u_pin = md5($post->pin);
	    if(isset($post->pin)) $object->u_pin = $post->pin;
	    // $object->u_remarks = $post->remarks;
	    $object->u_date_created = now();
	    $object->u_created_by = uid();
	    $object->u_last_modified_by = uid();
	    $id = R::store($object);

	    //Account    
	    if(hasField('sys_user', 'account_id')){
	        if($object->account_id){
	            $account = R::load("accounts", $object->account_id);
	        } else{
	            $account = R::dispense("accounts");
	        }

	        $account->name = "Operator: ".$object->u_fullname;
	        $account->account_category = 6;
	        $account->group = 'user';
	        R::store($account);

	        if(!$object->account_id){
	            $object->account = $account;
	        }
	    }
	    //End Account

	    // if ($_FILES['file']['size'] > 0){
	    //     $filename = upload($_FILES, $object->id , "uploads/user/avatar");
	    //     $object->u_avatar = $filename;
	    // }
	    R::store($object);

		if(isset($post->type_id)){
			switch (strtolower(rolename($post->role))){
				case 'customer': {
				  $customer = R::load("sales_customer", $post->type_id);
				  $customer->c_user_id = $id;
				  R::store($customer);
				} break;
				case 'supplier': {
				  $supplier = R::load("purchase_supplier", $post->type_id);
				  $supplier->s_user_id = $id;
				  R::store($supplier);
				} break;
				case 'agent': {
				  $agent = R::load("sales_agent", $post->type_id);
				  $agent->a_user_id = $id;
				  R::store($agent);
				} break;
				default: {
				  $employee = R::load("employee_details", $post->type_id);
				  $employee->pd_user_id = $id;
				  R::store($employee);
				} break;
			 }
		}
	    del("sys_user_role", "ur_user_id = $id");
	    replace("sys_user_role", "ur_user_id, ur_role_id", "$id, {$post->role}");
	    redir("/store/user");
	} else{
	    global $back;
	    die($msg."<br />".$back);	
	}
}
$checked = "";
if(METHOD == 'add'){
	$checked = "checked";
} elseif($object->u_status){
	$checked = "checked";
}
openForm('post', true);
print "<table align='center' class=''>
	<tr><td>Fullname</td><td><input type='text' name='fullname' value='$object->u_fullname' class='required form-control' /></td></tr>";
	print "<tr><td>Username</td><td><input type='text' name='username' value='$object->u_username' class='required form-control' /></td></tr>";
	print "<tr><td>Active</td><td><input type='checkbox' name='active' $checked value='1' class='required' /></td></tr>";
	echo "<tr><td>PIN</td><td><input type='text' name='pin' value='$object->u_pin' class='form-control' /></td></tr>";
	echo "<tr><td>Password</td><td><input type='password' name='password' value='' class='form-control' /></td></tr>";
	// echo "<tr ck><td>Confirm Password ".space(5)."</td><td><input type='password' name='conf_password' value='' class='form-control' /></td></tr>";
if(METHOD!="edit") {
	 echo "
	 <tr class='hidden'><td>PIN</td><td><input type='text' name='pin' value='$object->u_pin' class='number form-control required' /></td></tr>";
}		
echo "<tr class='hidden'><td>Email</td><td><input type='text' name='email' value='$object->u_email' class='email form-control' /></td></tr>
	<tr><td>Role</td><td>".selectOption("name='role' id='role' onchange='getNames()' class='form-control'", "sys_role", "r_name", "id", $object->id?getFieldValue("sys_user_role", "ur_role_id", "ur_user_id=$object->id"):'', "id>1")."<span id='names'></span></td></tr>
	</table>";
closeForm();