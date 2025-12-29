<?php
/**
 * User Registration API Endpoint
 * Handles initial user creation with 'Pending' status
 */

require_once '../config/config.php';
require_once '../core/BiometricAuth.php';

// Headers
header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validation
    if (!$input) throw new Exception('Invalid input');
    
    $fullName = trim($input['full_name'] ?? '');
    $pin = $input['pin'] ?? '';
    // Generate an account number if not provided (Format: Year Random4 Random4 Random4)
    $accountNumber = date('Y') . ' ' . rand(1000,9999) . ' ' . rand(1000,9999) . ' ' . rand(1000,9999);
    
    if (strlen($fullName) < 3) throw new Exception('Full name is too short');
    if (!ctype_digit($pin) || strlen($pin) !== 4) throw new Exception('PIN must be 4 digits');

    $pdo = getConnection();
    
    // Check duplication (unlikely with random account, but good practice)
    $stmt = $pdo->prepare("SELECT user_id FROM customers WHERE account_number = ?");
    $stmt->execute([$accountNumber]);
    if ($stmt->fetch()) {
        throw new Exception('System busy, please try again (ID Collision)');
    }

    // Insert new user as PENDING
    // PIN hashing
    $pinHash = password_hash($pin, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO customers (account_number, full_name, pin_hash, status, current_balance) VALUES (?, ?, ?, 'pending', 0.00)");
    
    if ($stmt->execute([$accountNumber, $fullName, $pinHash])) {
        $userId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'user_id' => $userId,
            'account_number' => $accountNumber,
            'message' => 'Account created successfully. Proceeding to biometric enrollment.'
        ]);
    } else {
        throw new Exception('Database error during registration');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
