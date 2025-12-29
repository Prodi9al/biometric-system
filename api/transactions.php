<?php
require_once '../config/config.php';
require_once '../core/Session.php';

header('Content-Type: application/json');

if (!Session::isLoggedIn()) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user_id = $_GET['user_id'] ?? Session::get('user_id');

try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("
        SELECT trans_type, amount, auth_status, timestamp 
        FROM transaction_history 
        WHERE user_id = ? 
        ORDER BY timestamp DESC 
        LIMIT 20
    ");
    $stmt->execute([$user_id]);
    $transactions = $stmt->fetchAll();
    
    echo json_encode($transactions);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to load transactions']);
}
?>