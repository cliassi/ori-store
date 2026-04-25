<?php
// REMOVE THIS LINE - object is already loaded from controller
// $object = R::dispense('expense_account');

if (!isset($object) || !is_object($object)) {
	$object = R::dispense('expense_account');
	if (defined('METHOD') && METHOD == 'edit' && defined('ID')) {
		$object = R::load('expense_account', ID);
	}
}

if(isset($post->save)){

	$fields = ['name','code','fullcode','parent','description','opening_balance', 'company', 'hotel'];

	foreach ($fields as $field) {
		if(isset($post->$field) && nn($post->$field)) {
			$object->$field = trim($post->$field);
		}
	}

	// $object->has_child = isset($post->has_child)?$post->has_child:0;
	if($post->parent){
		$parent = R::load("expense_account", $post->parent);	
		$parent->has_child = 1;
		$parentCode = getName("expense_account", $parent->parent, 'fullcode');
		$parent->fullcode = "{$parentCode}{$parent->code}";
		R::store($parent);
		$object->breadcrumbs = $parent->breadcrumbs.' > '.$object->name;
		$object->company = $parent->company;
		$object->hotel = $parent->hotel;
		//// $object->type= $parent->type;
	} else{
		$object->breadcrumbs = $object->name;
	}


	if(METHOD=="add") {
		$object->entry_by = uid();
		$object->entry_time = now();
		if($post->parent){
			$object->code = getNextCount("expense_account", "code", "parent=$post->parent");
		} else{
			$object->code = getNextCount("expense_account", "code", "parent IS NULL");
		}
	}
	if(METHOD=="edit") {
		$object->modify_by = uid();
		$object->modify_time = now();
		if(!nn($object->code)){
			if($post->parent){
				$object->code = getNextCount("expense_account", "code", "parent=$post->parent");
			} else{
				$object->code = getNextCount("expense_account", "code", "parent IS NULL");
			}
		}
		if($post->parent){
			$object->path = $parent->path.$object->id."/";
		}
	}

	// dd($object->path);
	R::store($object); 

	// if(METHOD == 'add'){
		if(isset($post->parent) && nn($post->parent)){
			$object->path = $parent->path.$object->id."/";
		} else{
			$object->path = "/$object->id/";
		}
	// }

	if(METHOD == 'add'){
		$object->sortorder = $object->id;
	}

	$object->depth = substr_count($object->path, "/") - 1;


	// vd($parent);
	// dd($object);
	if(!$object->has_child){
		$parentCode = getName("expense_account", $post->parent, 'fullcode');
		$object->fullcode = $parentCode . zerofill($object->code, 6 - strlen($parentCode));
		// print "<h1>$parentCode</h1>";
		// print "<h1>".strlen("{$parentCode}{$object->code}")."</h1>";
		// print "<h1>".zerofill($object->code, 7 - strlen("{$parentCode}{$object->code}"))."</h1>";
		// die($object->fullcode);
	}
	R::store($object);

	redir('/store/expense_account/?t='.$get->t);

}
openForm();
print "<table align='center'>
	<tr><td colspan='5'><b>".str(METHOD=="edit" ? "Edit Expense Account" : "Expense Account Details")."</b></td><tr>
		<tr><td>".str("Parent")."</td><td><select name='parent' class='parent form-control selectpicker' data-live-search='true' ><option value=''>--SELECT--</option>".getAccountsWithChild($object->parent?$object->parent:(isset($get->parent)?$get->parent:''))."</select></td>
		</tr>
		</tr>
		<tr><td>".str("Name")."</td><td><input type='text' autofocus name='name' id='name' value='$object->name' class='form-control required' /></td></tr>
	</table>";
closeForm();