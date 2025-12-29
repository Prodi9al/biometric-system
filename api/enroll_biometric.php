<?php
/**
 * Biometric Enrollment API Endpoint
 * Stores face/finger templates for a user
 */

require_once '../config/config.php';

// Headers
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
    exit;
}

function trace_log($msg) {
    file_put_contents(__DIR__ . '/../admin/debug_trace.txt', date('[H:i:s] [ENROLL] ') . $msg . "\n", FILE_APPEND);
}

try {
    trace_log("Enrollment request received.");
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validation
    if (!$input) throw new Exception('Invalid input');
    
    $userId = $input['user_id'] ?? 0;
    $biometricData = $input['face_data'] ?? ''; // Currently only face
    $modality = $input['modality'] ?? 'face';
    
    if (!$userId || !$biometricData) throw new Exception('Missing user ID or biometric data');

    $pdo = getConnection();
    
    // Check if user exists (Pending or Active)
    $stmt = $pdo->prepare("SELECT user_id FROM customers WHERE user_id = ?");
    $stmt->execute([$userId]);
    if (!$stmt->fetch()) {
        throw new Exception('User not found');
    }

    // Call Python Service to EXTRACT features (Secure Enrollment)
    // We reuse the verify endpoint concept but for extraction? 
    // Or we just store the raw data for now and let the service process it later?
    // BETTER: Store the base64 data in `biometric_data` table now.
    // The Python service `biometric_service.py` currently pulls from this table or loads random templates.
    // We need to update `biometric_service.py` to actually READ this table.
    
    // Check if entry exists
    $stmt = $pdo->prepare("SELECT bio_id FROM biometric_data WHERE user_id = ?");
    $stmt->execute([$userId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update
        if ($modality == 'face') {
            $sql = "UPDATE biometric_data SET face_enc = ? WHERE user_id = ?";
        } else {
             $sql = "UPDATE biometric_data SET finger_enc = ? WHERE user_id = ?";
        }
    } else {
        // Insert
        if ($modality == 'face') {
            $sql = "INSERT INTO biometric_data (face_enc, user_id) VALUES (?, ?)";
        } else {
             $sql = "INSERT INTO biometric_data (finger_enc, user_id) VALUES (?, ?)";
        }
    }
    
    // For now, we store the Raw Base64 Image. 
    // Ideally, we'd store the Feature Vector (Float Array), but that requires Python interaction here.
    // Given the architecture, let's store the Image string. The Python service will need to be updated to
    // decode this Image string -> Extract Features -> Compare.
    $stmt = $pdo->prepare($sql);
    $dataLen = strlen($biometricData);
    if ($stmt->execute([$biometricData, $userId])) {
        trace_log("Enrollment SUCCESS for User $userId. Modality: $modality. Data Size: $dataLen bytes.");
        echo json_encode([
            'success' => true,
            'message' => 'Biometric data enrolled successfully.'
        ]);
    } else {
        trace_log("Enrollment FAILED for User $userId. DB Error.");
        throw new Exception('Database error during enrollment');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
