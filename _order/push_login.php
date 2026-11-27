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


if (isset($post->type) && isset($post->id) && isset($post->uuid)) {
    $type = (string) $post->type;
    $id = (int) $post->id;
    $uuid = (string) $post->uuid;
    $ipValue = isset($post->last_ip) && is_scalar($post->last_ip)
        ? trim((string) $post->last_ip)
        : '';
    $lastIp = $ipValue !== '' && filter_var($ipValue, FILTER_VALIDATE_IP)
        ? $ipValue
        : null;
    $deviceValue = isset($post->last_device) && is_scalar($post->last_device)
        ? trim((string) $post->last_device)
        : '';
    $lastDevice = $deviceValue !== '' ? substr($deviceValue, 0, 255) : null;

    $statement = $c->prepare(
        'INSERT INTO push_client '
        . '(`type`, id, uuid, last_ip, last_device) '
        . 'VALUES (?, ?, ?, ?, ?) '
        . 'ON DUPLICATE KEY UPDATE '
        . '`type` = VALUES(`type`), id = VALUES(id), uuid = VALUES(uuid), '
        . 'last_ip = COALESCE(VALUES(last_ip), last_ip), '
        . 'last_device = COALESCE(VALUES(last_device), last_device)'
    );
    if ($statement) {
        $statement->bind_param('sisss', $type, $id, $uuid, $lastIp, $lastDevice);
        if (!$statement->execute()) {
            error_log('Push subscription registration failed: ' . $statement->error);
        }
        $statement->close();
    } else {
        error_log('Push subscription registration failed: ' . $c->error);
    }
}