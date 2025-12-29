<?php
require_once '../config/config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$accountNumber = $input['account_number'] ?? '';

if (!$accountNumber) {
    echo json_encode(['success' => false, 'error' => 'Account number required']);
    exit;
}

try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT user_id FROM customers WHERE account_number = ? AND status = 'active'");
    $stmt->execute([$accountNumber]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo json_encode(['success' => true, 'user_id' => $user['user_id']]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Account not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'System error']);
}
?>