<?php
require_once '../config/config.php';
require_once '../core/Session.php';

header('Content-Type: application/json');

if (!Session::get('user_id') || Session::get('user_type') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$query = $_GET['q'] ?? '';

try {
    $pdo = getConnection();
    // Search by Account Number OR Name
    $stmt = $pdo->prepare("SELECT user_id, full_name, account_number, current_balance FROM customers WHERE account_number LIKE ? OR full_name LIKE ? LIMIT 1");
    $stmt->execute(["%$query%", "%$query%"]);
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        echo json_encode(['success' => false, 'error' => 'User not found']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
