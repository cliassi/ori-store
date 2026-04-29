
<?php

require_once ('env.php');
require_once ('config.php');
require_once ('functions.php');

header('Content-Type: application/json');

$count = 0;
if ($c) {
    $sql = "SELECT COUNT(*) c FROM customer_order WHERE LOWER(IFNULL(status,'')) <> 'approved'";
    $res = mysqli_query($c, $sql);
    if ($res) {
        $obj = mysqli_fetch_object($res);
        $count = $obj && isset($obj->c) ? (int)$obj->c : 0;
    }
}

echo json_encode(['count' => $count]);
exit;

