<?php
$logFile = __DIR__ . '/login_issue.log';
if (file_exists($logFile) && (time() - filemtime($logFile)) > 21600) {
    file_put_contents($logFile, '');
}
file_put_contents($logFile, json_encode(array(
    'time' => date('Y-m-d H:i:s'),
    'file' => 'index.php',
    'method' => isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'N/A',
    'uri' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'N/A',
    'query' => isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : 'N/A',
    'remote_addr' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'N/A',
    'origin' => isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'N/A',
    'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 120) : 'N/A',
)) . "\n", FILE_APPEND);

session_start();

$page = isset($_GET['page']) ? $_GET['page'] : '';
if ($page === 'checkpin') {
    require_once('env.php');
    require_once('config.php');

    $logFile = __DIR__ . '/login_issue.log';
    if (file_exists($logFile) && (time() - filemtime($logFile)) > 21600) {
        file_put_contents($logFile, '');
    }

    $allowed_origins = array('http://localhost', 'https://localhost', 'capacitor://localhost');
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
    if (in_array($origin, $allowed_origins)) {
        header("Access-Control-Allow-Origin: $origin");
        header("Access-Control-Allow-Methods: POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type");
    }
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }

    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'));

    $debug = array(
        'time' => date('Y-m-d H:i:s'),
        'file' => 'index.php?page=checkpin',
        'method' => isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'N/A',
        'uri' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'N/A',
        'remote_addr' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'N/A',
        'origin' => $origin,
        'post_keys' => array_keys($_POST),
        'input_raw' => file_get_contents('php://input'),
        'content_type' => isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : 'N/A',
    );

    $pin = isset($_POST['pin']) ? trim($_POST['pin']) : '';
    if ($pin === '' && isset($input->pin)) {
        $pin = trim($input->pin);
    }
    $debug['pin_received'] = ($pin !== '') ? 'yes(len=' . strlen($pin) . ')' : 'empty';

    if ($pin === '') {
        $debug['result'] = 'FAIL: empty pin';
        file_put_contents($logFile, json_encode($debug) . "\n", FILE_APPEND);
        echo json_encode(array('error' => 1, 'message' => 'PIN required'));
        exit;
    }

    $pinEsc = mysqli_real_escape_string($c, $pin);
    $r = $c->query("SELECT id FROM sys_user WHERE u_pin='$pinEsc' AND u_status=1 LIMIT 1");
    if (!$r) {
        $debug['result'] = 'FAIL: query error';
        $debug['db_error'] = function_exists('mysqli_error') ? mysqli_error($c) : 'unknown';
        file_put_contents($logFile, json_encode($debug) . "\n", FILE_APPEND);
        echo json_encode(array('error' => 1, 'message' => 'DB error'));
        exit;
    }
    $user = mysqli_fetch_assoc($r);
    if ($user) {
        $userId = intval($user['id']);
        $token = bin2hex(random_bytes(16));
        $tokenEsc = mysqli_real_escape_string($c, $token);
        $c->query("INSERT INTO sys_token (user_id, token) VALUES ($userId, '$tokenEsc')");
        $debug['result'] = 'OK: user_id=' . $userId;
        file_put_contents($logFile, json_encode($debug) . "\n", FILE_APPEND);
        echo json_encode(array('error' => 0, 'token' => $token, 'user_id' => $userId));
    } else {
        $debug['result'] = 'FAIL: no user with this pin';
        file_put_contents($logFile, json_encode($debug) . "\n", FILE_APPEND);
        echo json_encode(array('error' => 1, 'message' => 'Invalid PIN'));
    }
    exit;
}

if(isset($_GET['uid'])){
  define('UID', $_GET['uid']);
  $_SESSION['UID'] = UID;
} elseif(isset($_SESSION['UID'])){
  define('UID', $_SESSION['UID']);
} else{
  exit;
}
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 1);
// header("HTTP/1.1 200 OK");

//print $_SERVER['REQUEST_URI'];
$allowed_origins = [
    'http://localhost',
    'https://localhost',
    'capacitor://localhost'
];

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit();
}
// header("Content-Type: application/force-download");
// header("Content-type: application/pdf");
// require_once "vendor/fpdf/fpdf.php";
// require_once "db.php";
// require_once "color.php";

// define("SST_NO", "W10-2009-22000010");
date_default_timezone_set('Asia/Kuala_Lumpur');

require_once ('env.php');
require_once ('config.php');
require_once ('functions.php');
require_once ('../core/functions.php');
// require_once ('f.inc.php');

$get = array(); foreach ($_GET as $key => $value) $get[$key] = $value; unset($_GET); $get = (object)$get;
$post = array(); foreach ($_POST as $key => $value) $post[$key] = $value; unset($_POST); $post = (object)$post;
$input = json_decode(file_get_contents('php://input'));

$actions = ['delivery','home','cnr','order_list','order','delivery_status','customer_order', 'collect','customer_add','invoice','cnr_report','collection','customer','customer_details', 'customer_collection','customer_due','daily_order','daily_purchase','daily_sales','dashboard','expenses','home','order','pending_order','petty_cash','product_add','stock_in','supplier_add','supplier_due'];
$action = isset($get->page)?$get->page:'home';
$response = [];


// var_dump(UID) ;
require_once 'pages/_main.php';

function getDateFromTimestamp($timestamp) {
  //return date('Y-m-d', strtotime($timestamp));
  $dateTime = new DateTime($timestamp);
  return $dateTime->format('d F Y');
}

// Function to get the time from a timestamp
function getTimeFromTimestamp($timestamp) {
  return date('H:i:s', strtotime($timestamp));
}

function sanitizeString($string) {
  return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
