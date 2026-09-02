<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
session_start();

$__pushLoginLog =  'push_login.log';
try {
    $logEntry = [
        'time' => date('Y-m-d H:i:s'),
        'ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
        'ua' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
        'get' => $_GET,
        'post' => $_POST,
    ];
    @file_put_contents($__pushLoginLog, json_encode($logEntry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
} catch (Throwable $e) {
    // ignore logging failures
}

date_default_timezone_set('Asia/Kuala_Lumpur');
require_once ('env.php');
require_once ('config.php');
require_once ('functions.php');
require_once ('f.inc.php');
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


if(isset($post->type) && isset($post->id) && isset($post->uuid)){
    replace("push_client", "type,id,uuid", "'$post->type', $post->id, '$post->uuid'");
}