<?php
$logFile = __DIR__ . '/login_issue.log';
if (file_exists($logFile) && (time() - filemtime($logFile)) > 21600) {
    file_put_contents($logFile, '');
}
file_put_contents($logFile, json_encode(array(
    'time' => date('Y-m-d H:i:s'),
    'file' => 'api.php',
    'method' => isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'N/A',
    'uri' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'N/A',
    'query' => isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : 'N/A',
    'remote_addr' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'N/A',
    'origin' => isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'N/A',
    'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 120) : 'N/A',
)) . "\n", FILE_APPEND);

session_start();
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

require_once('env.php');
require_once('config.php');
require_once('functions.php');
// require_once ('f.inc.php');

$get = array();
foreach ($_GET as $key => $value)
  $get[$key] = $value;
unset($_GET);
$get = (object) $get;
$post = array();
foreach ($_POST as $key => $value)
  $post[$key] = $value;
unset($_POST);
$post = (object) $post;
$input = json_decode(file_get_contents('php://input'));

$actions = ['home', 'products', 'product', 'products_variances', 'variance_detail', 'checkpin'];
$action = isset($get->action) ? $get->action : 'home';
$response = [];
if (in_array($action, $actions)) {
  $response['error'] = 0;
  $response['message'] = 'ok';
  switch ($action) {
    case 'home': {
      $file = sanitizeString($action);
      if (!file_exists('pages/' . $file . '.php')) {
        file_put_contents('pages/' . $file . '.php', "");
      }
      require_once 'pages/' . $file . '.php';
      exit;
    }
      break;
    case 'home1': {
      $product_categories = R::find('product_category');
      $data = [];
      foreach ($product_categories as $key => $product_category) {
        $products = R::find('product', 'product_category_id=?', [$product_category->id]);
        $productData = [];
        foreach ($products as $key => $product) {
          array_push($productData, ['id' => $product->id, 'name' => $product->name, 'image' => $product->image, 'image_orientation' => $product->image_orientation]);
        }
        array_push($data, $productData);
      }

      $response = $data;
    }
      break;
    case 'products': {
      $data = [];
      $products = R::find('product', 'product_category_id=?', [$get->id]);
      foreach ($products as $key => $product) {
        array_push($data, ['id' => $product->id, 'name' => $product->name, 'image' => $product->image, 'image_orientation' => $product->image_orientation]);
      }
      $response = $data;
    }
      break;
    case 'products_variances': {
      $products = R::find('product', 'product_category_id=?', [$get->id]);
      $data = [];
      foreach ($products as $key => $product) {
        $variances = R::find('product_variance', 'product_id=?', [$product->id]);
        $variancesData = [];
        foreach ($variances as $key => $variance) {
          array_push($variancesData, [
            'id' => $variance->id,
            'name' => $variance->particulars,
            'image' => $variance->image,
            'image_orientation' => $product->image_orientation
          ]);
        }
        array_push($data, $variancesData);
      }
      $response = $data;
    }
      break;
    case 'variances': {
      $products = R::find('product_variance', "product_id=?", [$get->id]);
      $data = [];
      foreach ($products as $key => $product) {
        array_push($data, ['id' => $product->id, 'name' => $product->particulars, 'image' => $product->image, 'size' => $product->size]);
      }
      $response['data'] = $data;
    }
      break;
    case 'variance_detail': {
      $variance = R::load('product_variance', $get->id);
      $data = [];
      $data['main'] = [
        'id' => $variance->id,
        'name' => $variance->particulars,
        'image' => $variance->image,
        'size' => $variance->size,
        'price' => $variance->price,
        'unit' => $variance->unit,
      ];
      $data['related'] = [];

      $variances = R::find('product_variance', 'product_id=? AND id <> ?', [$variance->product_id, $variance->id]);
      foreach ($variances as $key => $variance) {
        array_push($data['related'], [
          'id' => $variance->id,
          'name' => $variance->particulars,
          'image' => $variance->image,
          'size' => $variance->size,
          'price' => $variance->price,
          'unit' => $variance->unit,
        ]);
      }
      $response = $data;
    }
      break;

    case 'checkpin': {
      $logFile = __DIR__ . '/login_issue.log';
      if (file_exists($logFile) && (time() - filemtime($logFile)) > 21600) {
        file_put_contents($logFile, '');
      }

      $debug = array(
        'time' => date('Y-m-d H:i:s'),
        'file' => 'api.php?action=checkpin',
        'method' => isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'N/A',
        'uri' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'N/A',
        'remote_addr' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'N/A',
        'origin' => isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '',
        'post_keys' => array_keys((array) $post),
        'input_raw' => file_get_contents('php://input'),
        'content_type' => isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : 'N/A',
      );

      $pin = isset($get->pin) ? trim($get->pin) : '';
      if ($pin === '' && isset($input->pin)) {
        $pin = trim($input->pin);
      }
      $debug['pin_received'] = ($pin !== '') ? 'yes(len=' . strlen($pin) . ')' : 'empty';

      if ($pin === '') {
        $debug['result'] = 'FAIL: empty pin';
        file_put_contents($logFile, json_encode($debug) . "\n", FILE_APPEND);
        $response = array('error' => 1, 'message' => 'PIN required');
      } else {
        $pinEsc = mysqli_real_escape_string($c, $pin);
        $r = $c->query("SELECT id FROM sys_user WHERE u_pin='$pinEsc' AND u_status=1 LIMIT 1");
        if (!$r) {
          $debug['result'] = 'FAIL: query error';
          $debug['db_error'] = function_exists('mysqli_error') ? mysqli_error($c) : 'unknown';
          file_put_contents($logFile, json_encode($debug) . "\n", FILE_APPEND);
          $response = array('error' => 1, 'message' => 'DB error');
        } else {
          $user = mysqli_fetch_assoc($r);
          if ($user) {
            $userId = intval($user['id']);
            $token = bin2hex(random_bytes(16));
            $tokenEsc = mysqli_real_escape_string($c, $token);
            $c->query("INSERT INTO sys_token (user_id, token) VALUES ($userId, '$tokenEsc')");
            $debug['result'] = 'OK: user_id=' . $userId;
            file_put_contents($logFile, json_encode($debug) . "\n", FILE_APPEND);
            $response = array('error' => 0, 'token' => $token, 'user_id' => $userId);
          } else {
            $debug['result'] = 'FAIL: no user with this pin';
            file_put_contents($logFile, json_encode($debug) . "\n", FILE_APPEND);
            $response = array('error' => 1, 'message' => 'Invalid PIN');
          }
        }
      }
    }
      break;

    default:
      $response = ['error' => 1, 'message' => 'unknown request', 'data' => []];
      break;
  }
} else {
  $response = ['error' => 1, 'message' => 'unknown request', 'data' => []];
}

print json_encode($response);

function getDateFromTimestamp($timestamp)
{
  //return date('Y-m-d', strtotime($timestamp));
  $dateTime = new DateTime($timestamp);
  return $dateTime->format('d F Y');
}

// Function to get the time from a timestamp
function getTimeFromTimestamp($timestamp)
{
  return date('H:i:s', strtotime($timestamp));
}

function sanitizeString($string)
{
  return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}