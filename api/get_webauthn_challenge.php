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
    
    echo json_encode(['success' => true, 'challenge' => $challenge]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
