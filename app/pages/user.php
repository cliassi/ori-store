<?php 
if(METHOD == 'add'){
  require 'forms/user.php';
} elseif(METHOD == 'edit' && defined('ID')){
  require 'forms/user.php';
} elseif(METHOD == 'permission'){
  require 'view/permission.php';
}  elseif(METHOD == 'role'){
  require 'view/role.php';
} else{ 
  require 'view/user.php';
}