<?php
$logFile = __DIR__ . '/login_issue.log';
if (file_exists($logFile) && (time() - filemtime($logFile)) > 21600) {
    file_put_contents($logFile, '');
}
file_put_contents($logFile, json_encode(array(
    'time' => date('Y-m-d H:i:s'),
    'file' => 'store/ajax/test.php',
    'method' => isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'N/A',
    'uri' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'N/A',
    'remote_addr' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'N/A',
    'origin' => isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'N/A',
    'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 120) : 'N/A',
)) . "\n", FILE_APPEND);

header('Content-Type: application/json');
echo json_encode(array(
    'status' => 'ok',
    'message' => 'Server reachable',
    'time' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
));
