<?php
$object = R::dispense("bank_transaction");
if (isset($id) && $function != 'add') {
  $object = R::load("bank_transaction", $id);
}
switch ($function) {
  case "view": {
    require("view/$controller.php");
  }
    break;
  case "history": {
    require("history/$controller.php");
  }
    break;
}
if (METHOD == 'add') {
  require 'forms/customer.php';
} elseif (METHOD == 'edit' && defined('ID')) {
  require 'forms/customer.php';
} elseif (METHOD == 'details' && defined('ID')) {
  require 'details/customer.php';
} elseif (METHOD == 'pending_delivery' && defined('ID')) {
  require 'details/customer.php';
  // require 'details/customer_pending_delivery.php';
} elseif (METHOD == 'statement' && defined('ID')) {
  require 'details/statement.php';
} else {
  require 'view/bank_transaction_item.php';
}