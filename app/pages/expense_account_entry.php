<?php 
$object = R::dispense("expense_account_entry");
$controller = PAGE;
if(defined('ID')){
	$object = R::load("expense_account_entry", ID);
}
switch (METHOD){
	case "view":
	case "hotel":{
		require("view/$controller.php");
	} break;
	case "erase":{
		if(isset($get->conf)){		
			$object = R::load("expense_account_entry", $id);
			R::trash($object);
			redir("../view");
		} else{
			?>
			<script type="text/javascript">
				if(confirm("Are you sure you want to completly remove this Expense Account Entry?")){
					location.href = "?conf";
				} else{
					location.href = "../view";	
				}
			</script>
			<?php
		}

	} break;
	case "edit":
	case "add":{
		if(isset($post->save)){
			require_once("model/$controller.php");
			$parent = 1;
			$account = R::load("expense_account", $object->accountid);
			if(strpos($account->path, '/2/') !== FALSE){
				$parent = 2;
			}
			if($account->company){
				redir("/store/expense_account/carwash?company=$account->company");
			}
			if(isset($get->a)){
				redir("/store/expense_account/view?parent=$parent");
			}
			redir((METHOD=='edit'?'../':'')."view");
		}
		require_once("forms/$controller.php");
	} break;
}