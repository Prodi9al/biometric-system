<?php
/**
 * Legacy Biometric Authentication API - Updated to use secure system
 * Maintains backward compatibility while using new security architecture
 */
require_once '../config/config.php';
require_once '../core/BiometricAuth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

$account_number = $data['account_number'] ?? '';
$biometric_data = $data['biometric_data'] ?? [];
$face_data = $data['face_data'] ?? $biometric_data['face'] ?? '';
$finger_data = $data['finger_data'] ?? $biometric_data['fingerprint'] ?? '';

if (!$account_number || (!$face_data && !$finger_data)) {
    echo json_encode(['success' => false, 'message' => 'Account number and biometric data required']);
    exit;
}

try {
    $pdo = getConnection();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Get user by account number
    $stmt = $pdo->prepare("SELECT user_id, full_name FROM customers WHERE account_number = ? AND status = 'active'");
    $stmt->execute([$account_number]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Account not found']);
        exit;
    }
    
    // Determine primary biometric modality and prepare data
    $modality = 'face';
    $biometric_template = '';
    
    if ($face_data) {
        $modality = 'face';
        $biometric_template = $face_data;
    } elseif ($finger_data) {
        $modality = 'fingerprint';
        $biometric_template = $finger_data;
    } else {
        echo json_encode(['success' => false, 'message' => 'No biometric data provided']);
        exit;
    }
    
    // Use new secure biometric authentication
    $result = BiometricAuth::verifyBiometric($user['user_id'], $biometric_template, $modality);
    
    if ($result['success']) {
        // Create legacy session for backward compatibility
        session_start();
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_type'] = 'customer';
        $_SESSION['account_number'] = $account_number;
        
        echo json_encode([
            'success' => true,
            'message' => 'Authentication successful',
            'match_score' => $result['confidence'],
            'confidence' => $result['confidence'],
            'session_id' => $result['session_id']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['error'],
            'match_score' => 0.0
        ]);
    }
    
} catch (Exception $e) {
    error_log("Biometric auth error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Authentication system error']);
}
?>