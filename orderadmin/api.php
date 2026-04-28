<?php
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

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

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

$get = array(); foreach ($_GET as $key => $value) $get[$key] = $value; unset($_GET); $get = (object)$get;
$post = array(); foreach ($_POST as $key => $value) $post[$key] = $value; unset($_POST); $post = (object)$post;
$input = json_decode(file_get_contents('php://input'));

$actions = ['home','products', 'product', 'products_variances','variance_detail'];
$action = isset($get->action)?$get->action:'home';
$response = [];
if(in_array($action, $actions)){
  $response['error'] = 0;
  $response['message'] = 'ok';
  switch ($action) {
    case 'home':{
      $file = sanitizeString($action);
      if(!file_exists('pages/'.$file.'.php')){
        file_put_contents('pages/'.$file.'.php', ""); 
      }
      require_once 'pages/'.$file.'.php';
      exit;
    } break;
    case 'home1':{
      $product_categories = R::find('product_category');
      $data = [];
      foreach ($product_categories as $key => $product_category) {
        $products = R::find('product', 'product_category_id=?', [$product_category->id]);
        $productData = [];
        foreach ($products as $key => $product) {
          array_push($productData, ['id'=>$product->id,'name'=>$product->name, 'image'=>$product->image, 'image_orientation'=>$product->image_orientation]);
        }
        array_push($data,$productData);
      }
      
      $response = $data;
    } break;
    case 'products':{
      $data = [];
      $products = R::find('product', 'product_category_id=?', [$get->id]);
      foreach ($products as $key => $product) {
        array_push($data, ['id'=>$product->id,'name'=>$product->name, 'image'=>$product->image, 'image_orientation'=>$product->image_orientation]);
      }
      $response = $data;
    } break;
    case 'products_variances':{
      $products = R::find('product', 'product_category_id=?', [$get->id]);
      $data = [];
      foreach ($products as $key => $product) {
        $variances = R::find('product_variance', 'product_id=?', [$product->id]);
        $variancesData = [];
        foreach ($variances as $key => $variance) {
          array_push($variancesData, [
            'id'=>$variance->id,
            'name'=>$variance->particulars, 
            'image'=>$variance->image, 
            'image_orientation'=>$product->image_orientation
          ]);
        }
        array_push($data,$variancesData);
      }
      $response = $data;
    } break;
    case 'variances':{
      $products = R::find('product_variance', "product_id=?", [$get->id]);
      $data = [];
      foreach ($products as $key => $product) {
        array_push($data, ['id'=>$product->id,'name'=>$product->particulars, 'image'=>$product->image, 'size'=>$product->size]);
      }
      $response['data'] = $data;
    } break;
    case 'variance_detail':{
      $variance = R::load('product_variance', $get->id);
      $data = [];
      $data['main'] = [
        'id'=>$variance->id,
        'name'=>$variance->particulars, 
        'image'=>$variance->image, 
        'size'=>$variance->size,
        'price'=>$variance->price,
        'unit'=>$variance->unit,
      ];
      $data['related'] = [];
      
      $variances = R::find('product_variance', 'product_id=? AND id <> ?', [$variance->product_id, $variance->id]);
      foreach ($variances as $key => $variance) {
        array_push($data['related'], [
          'id'=>$variance->id,
          'name'=>$variance->particulars, 
          'image'=>$variance->image, 
          'size'=>$variance->size,
          'price'=>$variance->price,
          'unit'=>$variance->unit,
        ]);
      }
      $response = $data;
    } break;
    
    default:
      $response = ['error'=>1,'message'=>'unknown request', 'data'=>[]];
      break;
  }
} else{
  $response = ['error'=>1,'message'=>'unknown request', 'data'=>[]];
}

print json_encode($response);

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