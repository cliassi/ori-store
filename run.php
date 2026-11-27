<?php

// Stop the loopback request if this URL points back to this file.
if (isset($_SERVER['HTTP_X_ORI_RUN_CURL'])) {
    http_response_code(204);
    exit;
}

if (!function_exists('curl_init')) {
    http_response_code(500);
    exit('PHP cURL extension is not available');
}

$curl = curl_init('http://localhost:88/run.php');
curl_setopt_array($curl, [
    CURLOPT_HTTPGET => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_HTTPHEADER => ['X-Ori-Run-Curl: 1'],
]);

$response = curl_exec($curl);
$curlError = curl_error($curl);
$statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($response === false) {
    http_response_code(502);
    exit('Request failed: ' . $curlError);
}

if ($statusCode > 0) {
    http_response_code($statusCode);
}

echo $response;
