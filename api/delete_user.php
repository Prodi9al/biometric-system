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

if (!$userId) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid Request']);
    exit;
}

try {
    $pdo = getConnection();
    
    // Check if user exists first
    $stmt = $pdo->prepare("SELECT user_id FROM customers WHERE user_id = ?");
    $stmt->execute([$userId]);
    if (!$stmt->fetch()) {
        throw new Exception("User not found");
    }

    // Delete User
    // Use transaction to ensure cleanup
    $pdo->beginTransaction();

    // 1. Delete associated transactions
    $stmt = $pdo->prepare("DELETE FROM transaction_history WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    // 2. Delete biometric data
    $stmt = $pdo->prepare("DELETE FROM biometric_data WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    // 3. Delete customer record
    $stmt = $pdo->prepare("DELETE FROM customers WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    $pdo->commit();
    
    // Log Audit Trail
    require_once '../core/BiometricAuth.php';
    BiometricAuth::logAuthAttempt(
        Session::get('user_id'), 
        'success', 
        'user_deletion', 
        "Deleted user #$userId and all associated data", 
        'admin'
    );
    
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
