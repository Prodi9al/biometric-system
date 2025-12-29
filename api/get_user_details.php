<?php
// Force Admin Session for this API
session_name('BANK_ADMIN_SESSION');
session_start();

require_once '../config/config.php';
require_once '../core/Session.php';

header('Content-Type: application/json');

if (!Session::get('user_id') || Session::get('user_type') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = $_GET['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'error' => 'User ID required']);
    exit;
}

try {
    $pdo = getConnection();
    
    // 1. Fetch Basic Info + Biometric Counts
    $stmt = $pdo->prepare("SELECT 
                c.*, 
                (SELECT COUNT(*) FROM biometric_data b WHERE b.user_id = c.user_id AND LENGTH(b.face_enc) > 0) as has_face,
                (SELECT COUNT(*) FROM biometric_data b WHERE b.user_id = c.user_id AND LENGTH(b.finger_enc) > 0) as has_finger,
                (SELECT face_enc FROM biometric_data b WHERE b.user_id = c.user_id AND LENGTH(b.face_enc) > 100 LIMIT 1) as face_image
            FROM customers c WHERE c.user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception("User not found");
    }
    
    // Remove sensitive data
    unset($user['pin_hash']);
    
    // Ensure Integers
    $user['has_face'] = (int)$user['has_face'];
    $user['has_finger'] = (int)$user['has_finger'];

    // 2. Fetch Recent Transactions (Last 10)
    $stmt = $pdo->prepare("SELECT * FROM transaction_history WHERE user_id = ? ORDER BY timestamp DESC LIMIT 10");
    $stmt->execute([$userId]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Fetch Recent Auth Logs (Last 5)
    $stmt = $pdo->prepare("SELECT * FROM auth_logs WHERE user_id = ? ORDER BY timestamp DESC LIMIT 5");
    $stmt->execute([$userId]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true, 
        'user' => $user,
        'transactions' => $transactions,
        'logs' => $logs
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
