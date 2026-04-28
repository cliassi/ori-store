<?php
// Salary payment logging function
function salary_log($message) {
    $logFile = 'salary_log.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

// Function to read salary logs
function read_salary_logs($lines = 50) {
    $logFile = 'salary_log.log';
    if (file_exists($logFile)) {
        $logLines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return array_slice($logLines, -$lines);
    }
    return [];
}

// Function to clear salary logs
function clear_salary_log() {
    $logFile = 'salary_log.log';
    if (file_exists($logFile)) {
        file_put_contents($logFile, '');
        return true;
    }
    return false;
}
?>
