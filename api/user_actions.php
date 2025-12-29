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

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['user_id'] ?? null;
    $action = $input['action'] ?? null; // 'lock', 'unlock', 'reset_pin'
    
    if (!$userId || !$action) {
        throw new Exception("Missing user_id or action");
    }

    $pdo = getConnection();
    
    switch ($action) {
        case 'lock':
            $stmt = $pdo->prepare("UPDATE customers SET status = 'locked' WHERE user_id = ?");
            $stmt->execute([$userId]);
            $msg = "Account locked successfully";
            break;
            
        case 'unlock':
            $stmt = $pdo->prepare("UPDATE customers SET status = 'active' WHERE user_id = ?");
            $stmt->execute([$userId]);
            $msg = "Account unlocked successfully";
            break;
            
        case 'reset_pin':
            // Reset to default '1234'
            $defaultHash = password_hash('1234', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE customers SET pin_hash = ? WHERE user_id = ?");
            $stmt->execute([$defaultHash, $userId]);
            $msg = "PIN reset to '1234'";
            break;
            
        default:
            throw new Exception("Invalid action");
    }

    // Log Audit Trail
    require_once '../core/BiometricAuth.php';
    BiometricAuth::logAuthAttempt(
        Session::get('user_id'), 
        'success', 
        'user_action', 
        "$msg for User #$userId", 
        'admin'
    );

    echo json_encode(['success' => true, 'message' => $msg]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
