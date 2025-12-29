<?php
require_once '../config/config.php';
require_once '../core/Session.php';

// Force Admin Session Check
session_name('BANK_ADMIN_SESSION');
session_start();

header('Content-Type: application/json');

if (!Session::get('user_id') || Session::get('user_type') !== 'admin') {
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
    $input = json_decode(file_get_contents('php://input'), true);
    
    $targetUserId = $input['target_user_id'] ?? 0;
    $fingerData = $input['fingerprint_data'] ?? '';
    
    if (!$targetUserId || !$fingerData) {
        throw new Exception('Missing User ID or Fingerprint Data');
    }

    $pdo = getConnection();

    // Verify user exists
    $stmt = $pdo->prepare("SELECT user_id FROM customers WHERE user_id = ?");
    $stmt->execute([$targetUserId]);
    if (!$stmt->fetch()) {
        throw new Exception('Target user not found');
    }

    // Check if entry exists in biometric_data
    $stmt = $pdo->prepare("SELECT bio_id FROM biometric_data WHERE user_id = ?");
    $stmt->execute([$targetUserId]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Update existing record
        $stmt = $pdo->prepare("UPDATE biometric_data SET finger_enc = ? WHERE user_id = ?");
        $stmt->execute([$fingerData, $targetUserId]);
    } else {
        // Insert new record (initialize face_enc as null/empty if needed, or rely on defaults)
        $stmt = $pdo->prepare("INSERT INTO biometric_data (user_id, finger_enc) VALUES (?, ?)");
        $stmt->execute([$targetUserId, $fingerData]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Fingerprint enrolled successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
