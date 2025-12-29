<?php
require_once '../config/config.php';
require_once '../core/Session.php';

header('Content-Type: application/json');

// FAIL-SECURE: Admin Only
session_name('BANK_ADMIN_SESSION');
Session::start();
if (!Session::isLoggedIn() || Session::get('user_type') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $pdo = getConnection();
    
    // TRUNCATE is fast and resets auto-increment
    $stmt = $pdo->prepare("TRUNCATE TABLE auth_logs");
    $stmt->execute();
    
    // Log this action for security (using error_log since we just deleted the DB logs!)
    error_log("SECURITY AUDIT: All audit logs deleted by Admin ID " . Session::get('user_id'));
    
    echo json_encode(['success' => true, 'message' => 'All logs deleted successfully']);

} catch (Exception $e) {
    error_log("Delete logs error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
