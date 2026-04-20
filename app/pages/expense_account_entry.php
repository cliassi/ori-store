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
		if(uid() != 1){
			http_response_code(403);
			die("You do not have permission to delete expense entries!");
		}
		if(isset($get->conf)){
			$deleteId = defined('ID') ? (int)ID : 0;
			if(!isset($get->pin) || $get->pin != upin()){
				?>
				<script type="text/javascript">
					if(typeof Swal !== "undefined"){
						Swal.fire({icon: "error", title: "Wrong PIN", text: "Invalid admin PIN."}).then(function(){
							location.href = "../view";
						});
					} else{
						alert("Wrong PIN entered!");
						location.href = "../view";
					}
				</script>
				<?php
				break;
			}
			if($deleteId > 0){
				$object = R::load("expense_account_entry", $deleteId);
				if((int)$object->id > 0){
					R::trash($object);
				}
			}
			redir("../view");
		}
		redir("../view");

	} break;
	case "edit":
	case "add":{
		if(METHOD == "edit" && !(uid() == 1 || isUserIn(['lemon']))){
			http_response_code(403);
			die("You do not have permission to edit expense entries!");
		}
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
