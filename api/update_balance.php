<?php
// Force Admin Session for this API
session_name('BANK_ADMIN_SESSION');
session_start();

require_once '../config/config.php';
require_once '../core/Session.php';

header('Content-Type: application/json');

if (!Session::get('user_id') || Session::get('user_type') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userId = $input['user_id'] ?? 0;
$type = $input['type'] ?? ''; // credit, debit, set
$amount = floatval($input['amount'] ?? 0);

if (!$userId || $amount < 0 || !in_array($type, ['credit', 'debit', 'set'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid Request']);
    exit;
}

try {
    $pdo = getConnection();
    $pdo->beginTransaction();

    // Get current balance
    $stmt = $pdo->prepare("SELECT current_balance FROM customers WHERE user_id = ? FOR UPDATE");
    $stmt->execute([$userId]);
    $current = $stmt->fetchColumn();

    if ($current === false) throw new Exception('User not found');
    
    $newBalance = $current;
    
    if ($type === 'credit') {
        $newBalance += $amount;
        $desc = "Admin Credit";
    } elseif ($type === 'debit') {
        // Allow overdraft? Maybe not.
        if ($current < $amount) throw new Exception("Insufficient funds for debit");
        $newBalance -= $amount;
        $desc = "Admin Debit";
    } else {
        $newBalance = $amount;
        $desc = "Admin Balance Set";
    }
    
    // Update Balance
    $stmt = $pdo->prepare("UPDATE customers SET current_balance = ? WHERE user_id = ?");
    $stmt->execute([$newBalance, $userId]);
    
    // Log Transaction - using actual table columns
    $stmt = $pdo->prepare("
        INSERT INTO transaction_history 
        (user_id, trans_type, amount, recipient_account, auth_status, match_confidence, timestamp) 
        VALUES (?, ?, ?, NULL, 'Success', 100, NOW())
    ");
    $stmt->execute([$userId, $desc, $amount]);
    
    $pdo->commit();
    
    // Log Audit Trail
    require_once '../core/BiometricAuth.php';
    BiometricAuth::logAuthAttempt(
        Session::get('user_id'), 
        'success', 
        'balance_adjustment', 
        "$desc for User #$userId: " . CURRENCY . number_format($amount, 2), 
        'admin'
    );
    
    echo json_encode(['success' => true, 'new_balance' => $newBalance]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    // Return detailed error for debugging
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
