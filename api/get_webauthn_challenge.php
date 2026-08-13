<?php
require_once '../config/config.php';
require_once '../core/Session.php';

header('Content-Type: application/json');

try {
    // Generate 32 bytes of random data
    $challengeBytes = random_bytes(32);
    
    // Base64url encode
    $challenge = rtrim(strtr(base64_encode($challengeBytes), '+/', '-_'), '=');
    
    // Store in session for verification later
    Session::set('webauthn_challenge', $challenge);
    
    // Get user_id from request (for retrieving allowed credentials)
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['user_id'] ?? null;
    $credentialIds = [];
    
    if ($userId) {
        $pdo = getConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT finger_enc FROM biometric_data WHERE user_id = ? AND finger_enc IS NOT NULL");
            $stmt->execute([$userId]);
            $record = $stmt->fetch();
            if ($record && $record['finger_enc']) {
                // The finger_enc stores the credential ID
                $credentialIds = [$record['finger_enc']];
            }
        }
    }
    
    echo json_encode([
        'success' => true, 
        'challenge' => $challenge,
        'credential_ids' => $credentialIds
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
