<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
session_start();

date_default_timezone_set('Asia/Kuala_Lumpur');
require_once ('env.php');
require_once ('core/config.php');
require_once ('core/functions.php');
require_once ('core/f.inc.php');
$get = array();
foreach ($_GET as $key => $value) {
    $get[$key] = $value;
}
unset($_GET);
$get = (object) $get;
$post = array();
foreach ($_POST as $key => $value) {
    $post[$key] = $value;
}
unset($_POST);
$post = (object) $post;

$page = 'home';
if(!loggedin()){
    $get->q = "auth/login";
}
// define('APP', "store");

if (isset($get->q)) {
    $params = explode("/", $get->q);
    $page = $params[0];
    if (count($params) > 1) {
        define('METHOD', $params[1]);
    } else{
        define('METHOD', 'view');
    }
    if (count($params) > 2) {
        define('PARAM1', $params[1]);
        define('ID', $params[2]);
    }
    if (!file_exists('app/pages/' . $page . '.php')) {
        $page = '404';
    }
}
define('PAGE', $page);
$theme = 'able';
require_once 'theme/'.$theme.'/index.php';