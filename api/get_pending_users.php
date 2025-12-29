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

try {
    $pdo = getConnection();
    $stmt = $pdo->query("SELECT user_id, full_name, account_number, created_at, 
                        (SELECT face_enc FROM biometric_data WHERE biometric_data.user_id = customers.user_id LIMIT 1) as face_image 
                        FROM customers WHERE status = 'pending' ORDER BY created_at DESC");
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($users);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
