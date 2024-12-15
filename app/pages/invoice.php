<?php 
if(METHOD == 'add'){
  require 'forms/customer.php';
} elseif(METHOD == 'edit' && defined('ID')){
  require 'forms/invoice.php';
} elseif(METHOD == 'print' && defined('ID')){
  require 'view/invoice.php';
} elseif(METHOD == 'details' && defined('ID')){
  require 'details/customer.php';
}   elseif(METHOD == 'statement' && defined('ID')){
  require 'details/statement.php';
} else{ 
  require 'forms/invoice.php';
}