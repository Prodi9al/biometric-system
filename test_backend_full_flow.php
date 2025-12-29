<?php
// Backend Integration Test - Full Flow
require_once 'config/config.php';
require_once 'core/BiometricAuth.php';

echo "========================================\n";
echo "    BACKEND INTEGRATION TEST            \n";
echo "========================================\n";

function logStep($step, $msg) {
    echo "[$step] $msg\n";
}

try {
    $pdo = getConnection();
    
    // 1. Create Test User
    $testName = "TestUser_" . time();
    $pin = "1234";
    $accNum = "TEST" . time();
    
    logStep(1, "Creating User: $testName");
    $stmt = $pdo->prepare("INSERT INTO customers (full_name, account_number, pin_hash, status) VALUES (?, ?, ?, 'active')");
    $stmt->execute([$testName, $accNum, password_hash($pin, PASSWORD_DEFAULT)]);
    $userId = $pdo->lastInsertId();
    
    if (!$userId) throw new Exception("Failed to create user");
    logStep(1, "User Created. ID: $userId");
    
    // 2. Enroll Dummy Data
    // Use a small valid base64 image (1x1 pixel jpeg)
    $dummyImage = "/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAMCAgMCAgMDAwMEAwMEBQgFBQQEBQoHBwYIDAoMDAsKCwsNDhIQDQ4RDgsLEBYQERMUFRUVDA8XGBYUGBIUFRT/wAALCAABAAEBAREA/8QAABAAAAABAAAAAAAAAAAAAAAAAAgHB//EABgQAQEBAQEAAAAAAAAAAAAAAAABAgME/9oACAEBAAA/AKqAAB//2Q==";
    
    logStep(2, "Enrolling Biometric Data (Simulation)");
    // We insert directly into DB to test the schema/enrollment logic match
    $stmt = $pdo->prepare("INSERT INTO biometric_data (user_id, face_enc) VALUES (?, ?)");
    $stmt->execute([$userId, $dummyImage]);
    logStep(2, "Enrollment Complete.");
    
    // 3. Authenticate (Call BiometricAuth Class directly)
    logStep(3, "Testing Authentication Logic...");
    
    // Note: This will likely fail confidence check because 1x1 pixel has no face, 
    // BUT we want to see if the Python service *receives* it and *responds* correctly (even with failure).
    // We are testing the PIPELINE, not face recognition accuracy.
    
    $result = BiometricAuth::verifyBiometric($userId, $dummyImage, 'face');
    
    echo "\nResponse from BiometricAuth:\n";
    print_r($result);
    
    if (isset($result['success']) && $result['success'] === false) {
        logStep(3, "Pipeline OK (Functioned). Result: Failed (Expected for dummy image).");
        if (isset($result['error'])) {
             echo "Failure Reason: " . $result['error'] . "\n";
             if (stripos($result['error'], 'No face found') !== false || stripos($result['error'], 'feature') !== false) {
                 echo "SUCCESS: Python correctly identified invalid image!\n";
             }
        }
    } else {
        logStep(3, "Unexpected Success? (Did you disable security?)");
    }

    // 4. Cleanup
    logStep(4, "Cleaning up...");
    $pdo->prepare("DELETE FROM customers WHERE user_id = ?")->execute([$userId]);
    $pdo->prepare("DELETE FROM biometric_data WHERE user_id = ?")->execute([$userId]);
    logStep(4, "Done.");

} catch (Exception $e) {
    echo "\nCRITICAL ERROR: " . $e->getMessage() . "\n";
}
?>
