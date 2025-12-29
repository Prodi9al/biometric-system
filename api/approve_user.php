<?php
// Force Admin Session for this API
session_name('BANK_ADMIN_SESSION');
session_start();

require_once '../config/config.php';
require_once '../core/Session.php';

header('Content-Type: application/json');

if (!Session::get('user_id') || Session::get('user_type') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userId = $input['user_id'] ?? 0;
$action = $input['action'] ?? '';

if (!$userId || !in_array($action, ['approve', 'reject'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid Request']);
    exit;
}

try {
    $pdo = getConnection();
    
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE customers SET status = 'active' WHERE user_id = ?");
        $stmt->execute([$userId]);
    } else {
        // Reject implies deletion for now, or could set to 'suspended'
        // Let's delete to cleanup
        $stmt = $pdo->prepare("DELETE FROM customers WHERE user_id = ?");
        $stmt->execute([$userId]);
        // Foreign keys cascade delete biometric_data
    }
    
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
