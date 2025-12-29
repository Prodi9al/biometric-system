<?php
require_once '../config/config.php';
require_once '../core/Session.php';

header('Content-Type: application/json');

if (!Session::isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_GET['user_id'] ?? Session::get('user_id');

// Verify user can only access their own balance
if ($user_id != Session::get('user_id') && Session::get('user_type') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

try {
    $pdo = getConnection();
    
    $stmt = $pdo->prepare("SELECT current_balance FROM customers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $balance = $stmt->fetchColumn();
    
    if ($balance !== false) {
        echo json_encode(['balance' => floatval($balance)]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>