<?php
session_start();
// Ensure this is an AJAX request
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Direct access not allowed']);
    exit;
}

require_once("../env.php");
require_once("../core/config.php");
require_once("../core/f.inc.php");
$response = ['success' => false, 'message' => ''];

try {
    // Check if branch_id is provided
    if (!isset($_POST['branch_id']) || empty($_POST['branch_id'])) {
        throw new Exception('Branch ID is required');
    }

    $branch_id = (int)$_POST['branch_id'];
    
    // Get branch details
    $branch = R::load('branch', $branch_id);
    
    if (!$branch->id) {
        throw new Exception('Invalid branch selected');
    }
    
    // Update session with new branch info
    $_SESSION['branch_id'] = $branch->id;
    $_SESSION['branch_name'] = $branch->name;
    
    // Update user's last used branch if user is logged in
    if (isset($_SESSION['user_id'])) {
        $user = R::load('user', $_SESSION['user_id']);
        if ($user->id) {
            $user->last_branch_id = $branch->id;
            R::store($user);
        }
    }
    
    $response = [
        'success' => true,
        'message' => 'Branch switched successfully',
        'branch' => [
            'id' => $branch->id,
            'name' => $branch->name
        ]
    ];
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);