<?php 
if(METHOD == 'add'){
	if(isset($post->save)){
	  require 'model/bank_transaction.php';
	}
  require 'forms/bank_transaction.php';
} elseif(METHOD == 'edit' && defined('ID')){
  require 'forms/bank_transaction.php';
} elseif(METHOD == 'history' && defined('ID')){
  require 'view/bank_transaction_history_in.php';
} elseif(METHOD == 'view' && defined('ID')){
  require 'view/bank_transaction.php';
  // require 'details/bank_transaction_pending_delivery.php';
} elseif(METHOD == 'statement' && defined('ID')){
  require 'details/statement.php';
} else{ 
  require 'view/bank_transaction.php';
}