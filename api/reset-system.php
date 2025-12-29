<?php
// Force Admin Session for this API
session_name('BANK_ADMIN_SESSION');
session_start();

require_once '../config/config.php';
require_once '../core/Session.php';

header('Content-Type: application/json');

if (!Session::isLoggedIn() || Session::get('user_type') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $pdo = getConnection();
    $pdo->beginTransaction();
    
    // Clear all customer data except admin users
    $pdo->exec("DELETE FROM transaction_history");
    $pdo->exec("DELETE FROM biometric_data");
    $pdo->exec("DELETE FROM customers");
    
    // Reset auto-increment counters
    $pdo->exec("ALTER TABLE customers AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE biometric_data AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE transaction_history AUTO_INCREMENT = 1");
    
    // Insert default test customer
    $stmt = $pdo->prepare("INSERT INTO customers (account_number, full_name, pin_hash, current_balance) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        '2425402655',
        'Lukeman Nana Yaw Quansah',
        password_hash('1234', PASSWORD_DEFAULT),
        5000.00
    ]);
    $user_id = $pdo->lastInsertId();
    
    // Insert default biometric data
    $stmt = $pdo->prepare("INSERT INTO biometric_data (user_id, face_enc, finger_enc, quality_score) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $user_id,
        'default_face_template_' . time(),
        'default_finger_template_' . time(),
        98.5
    ]);
    
    // Insert default transaction log
    $stmt = $pdo->prepare("INSERT INTO transaction_history (user_id, trans_type, amount, auth_status, match_confidence) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $user_id,
        'Auth_Success',
        0.00,
        'Success',
        98.5
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'System reset successfully',
        'reset_time' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>