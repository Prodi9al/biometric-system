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
    
    // Fetch users with Biometric flags
    $sql = "SELECT 
                c.user_id, 
                c.full_name, 
                c.account_number, 
                c.current_balance, 
                c.created_at, 
                c.status,
                (SELECT COUNT(*) FROM biometric_data b WHERE b.user_id = c.user_id AND b.face_enc IS NOT NULL) as has_face,
                (SELECT COUNT(*) FROM biometric_data b WHERE b.user_id = c.user_id AND b.finger_enc IS NOT NULL) as has_finger
            FROM customers c 
            WHERE c.status IN ('active', 'locked', 'suspended') 
            ORDER BY c.full_name ASC";
            
    $stmt = $pdo->query($sql);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'users' => $users]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
