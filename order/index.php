<?php
session_start();
$get = array(); foreach ($_GET as $key => $value) $get[$key] = $value; unset($_GET); $get = (object)$get;
$post = array(); foreach ($_POST as $key => $value) $post[$key] = $value; unset($_POST); $post = (object)$post;
$input = json_decode(file_get_contents('php://input'));

if($_SERVER['REQUEST_URI'] == '/order/'){
  if(isset($_SESSION['UID'])){
    session_destroy();
    unset($_SESSION);
    session_start();
  }
}

if(isset($get->uid)){
  define('UID', $get->uid);
  $_SESSION['UID'] = UID;
  define('GUEST', false);
} elseif(isset($_SESSION['UID'])){
  define('UID', $_SESSION['UID']);
  define('GUEST', false);
} else{
  $get->uid = 0;
  define('GUEST', true);
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
// require_once ('f.inc.php');

// sendPush('c81087f4-0b65-4dab-a202-5687b9995f7b', 'New order from customer');
// notifyUsers('New order from customer TEST 123');


$actions = ['delivery', 'place_order','home','cnr','order_list','order','delivery_status', 'collect','customer_add','invoice','cnr_report','collection','customer','customer_details', 'customer_collection','customer_due','daily_order','daily_purchase','daily_sales','dashboard','expenses','home','order','pending_order','petty_cash','product_add','stock_in','supplier_add','supplier_due'];
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
