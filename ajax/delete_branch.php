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

    // Get the branch ID from POST data
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        throw new Exception('Invalid branch ID');
    }

    // Check if the branch exists
    $branch = R::load('branch', $id);
    if ($branch->id === 0) {
        throw new Exception('Branch not found');
    }

    // TODO: Add any additional checks here (e.g., if there are related records that would prevent deletion)
    // For example, check if there are employees assigned to this branch
    // $employeeCount = R::count('employee', ' branch_id = ?', [$id]);
    // if ($employeeCount > 0) {
    //     throw new Exception('Cannot delete branch. There are employees assigned to it.');
    // }

    // Delete the branch
    R::trash($branch);
    
    $response['success'] = true;
    $response['message'] = 'Branch deleted successfully';
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
