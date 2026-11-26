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


header('Content-Type: application/json; charset=utf-8');

$type = isset($post->type) && is_string($post->type) ? $post->type : '';
$idValue = isset($post->id) && is_scalar($post->id) ? (string) $post->id : '';
$uuid = isset($post->uuid) && is_scalar($post->uuid) ? trim((string) $post->uuid) : '';
$id = filter_var($idValue, FILTER_VALIDATE_INT);

if (
    !in_array($type, ['a', 'c'], true)
    || $id === false
    || $id <= 0
    || !preg_match('/^[0-9a-f-]{36}$/i', $uuid)
) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid push subscription data.']);
    exit;
}

$statement = $c->prepare(
    'INSERT INTO push_client (type, id, uuid) VALUES (?, ?, ?) '
    . 'ON DUPLICATE KEY UPDATE type = VALUES(type), id = VALUES(id), uuid = VALUES(uuid)'
);
if (!$statement) {
    error_log('Push subscription registration prepare failed: ' . $c->error);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Registration failed.']);
    exit;
}

$statement->bind_param('sis', $type, $id, $uuid);
if (!$statement->execute()) {
    error_log('Push subscription registration failed: ' . $statement->error);
    $statement->close();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Registration failed.']);
    exit;
}

$statement->close();
echo json_encode(['success' => true]);