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

// Verify user can only access their own transactions
if ($user_id != Session::get('user_id') && Session::get('user_type') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

try {
    $pdo = getConnection();
    
    $stmt = $pdo->prepare("
        SELECT trans_id, trans_type, amount, timestamp, auth_status
        FROM transaction_history 
        WHERE user_id = ? 
        ORDER BY timestamp DESC 
        LIMIT 10
    ");
    
    $stmt->execute([$user_id]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($transactions);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>