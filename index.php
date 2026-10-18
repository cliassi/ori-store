<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
ob_start();
session_start();

date_default_timezone_set('Asia/Kuala_Lumpur');
require_once ('env.php');
require_once ('core/config.php');
require_once ('core/functions.php');
require_once ('core/f.inc.php');
require_once ('core/totp.php');
ensureDcollectPrivileges();
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

if (loggedin()) {
    $userId = (int) uid();
    $stmt = $c->prepare("SELECT u_status, COALESCE(totp_enabled, 0) AS totp_enabled, (u_totp_secret IS NOT NULL AND u_totp_secret <> '') AS totp_configured FROM sys_user WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result ? $result->fetch_object() : false;
        $stmt->close();
    } else {
        $user = false;
    }
    if (!$user || !(int) $user->u_status || ((int) $user->totp_enabled === 1 && (empty($_SESSION[APP . '_totp_verified']) || (int) (isset($_SESSION[APP . '_totp_verified_user']) ? $_SESSION[APP . '_totp_verified_user'] : 0) !== $userId || empty($user->totp_configured)))) {
        unset($_SESSION[ROOT . '_loggedin'], $_SESSION[ROOT . '_username'], $_SESSION[APP . '_id'], $_SESSION[APP . '_role'], $_SESSION[APP . '_rolecode'], $_SESSION[APP . '_role_name'], $_SESSION[APP . '_fullname'], $_SESSION[APP . '_pin'], $_SESSION[APP . '_totp_verified'], $_SESSION[APP . '_totp_verified_user'], $_SESSION[APP . '_totp_pending'], $_SESSION[APP . '_totp_message']);
        unset($_SESSION['branch_id'], $_SESSION['branch_name'], $_SESSION[APP . '_branch']);
    }
}

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
        if($page == 'report' && isset($get->product_variance) && $get->product_variance > 0){
            define('ID', $get->product_variance + 0);
        } else{
            define('ID', $params[2]);
        }
    }
    if (!file_exists('app/pages/' . $page . '.php')) {
        $page = '404';
    }
}
define('PAGE', $page);
$theme = 'able';
require_once 'theme/'.$theme.'/index.php';