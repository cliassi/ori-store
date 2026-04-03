<?php
require_once '../../config.php';
require_once '../../app/init.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => ''
];

try {
    // Check if user is logged in and has permission
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized access');
    }

    // Get the division ID from POST data
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        throw new Exception('Invalid division ID');
    }

    // Check if the division exists
    $division = R::load('division', $id);
    if ($division->id === 0) {
        throw new Exception('Division not found');
    }

    // Check if there are any branches associated with this division
    $branchCount = R::count('branch', ' division_id = ?', [$id]);
    if ($branchCount > 0) {
        throw new Exception('Cannot delete division. There are branches associated with it.');
    }

    // Delete the division
    R::trash($division);
    
    $response['success'] = true;
    $response['message'] = 'Division deleted successfully';
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
