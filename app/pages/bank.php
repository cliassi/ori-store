<?php 
if(METHOD == 'add'){
  require 'forms/bank.php';
} elseif(METHOD == 'edit' && defined('ID')){
  require 'forms/bank.php';
} elseif(METHOD == 'view' && defined('ID')){
  require 'view/bank.php';
} elseif(METHOD == 'pending_delivery' && defined('ID')){
  require 'details/bank.php';
  // require 'details/bank_pending_delivery.php';
} elseif(METHOD == 'statement' && defined('ID')){
  require 'details/statement.php';
} else{ 
  require 'view/bank.php';
}