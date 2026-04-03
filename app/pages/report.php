<?php
// Parse query string manually since framework clears $_GET
if (!empty($_SERVER['QUERY_STRING'])) {
    parse_str($_SERVER['QUERY_STRING'], $_GET);
}

// Extract ID from URL path or query string (only if not already defined)
if (!defined('ID')) {
    $pathParts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
    $lastSegment = end($pathParts);

    if (is_numeric($lastSegment)) {
        define('ID', (int)$lastSegment);
    } elseif (isset($_GET['id']) && is_numeric($_GET['id'])) {
        define('ID', (int)$_GET['id']);
    }
}



require_once 'reports/' . METHOD . '.php';