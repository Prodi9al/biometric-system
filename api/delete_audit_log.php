<?php
// API to delete audit log entry
session_name('BANK_ADMIN_SESSION');
session_start();

require_once '../config/config.php';
require_once '../core/Session.php';

header('Content-Type: application/json');

if (!Session::isLoggedIn() || Session::get('user_type') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $logId = $data['log_id'] ?? null;
    
    if (!$logId) {
        throw new Exception('Log ID required');
    }
    
    $pdo = getConnection();
    $stmt = $pdo->prepare("DELETE FROM auth_logs WHERE log_id = ?");
    $stmt->execute([$logId]);
    
    require_once '../core/BiometricAuth.php';
    BiometricAuth::logAuthAttempt(
        Session::get('user_id'), 
        'success', 
        'log_deletion', 
        "Deleted audit log entry #$logId", 
        'admin'
    );
    
    echo json_encode(['success' => true, 'message' => 'Log deleted successfully']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
