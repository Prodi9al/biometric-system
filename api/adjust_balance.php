<?php
// API to adjust customer balance (credit/debit)
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
    
    $userId = $data['user_id'] ?? null;
    $amount = $data['amount'] ?? 0;
    $type = $data['type'] ?? ''; // 'credit' or 'debit'
    
    if (!$userId || $amount <= 0 || !in_array($type, ['credit', 'debit'])) {
        throw new Exception('Invalid parameters');
    }
    
    $pdo = getConnection();
    
    // Get current balance
    $stmt = $pdo->prepare("SELECT current_balance, full_name FROM customers WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    $currentBalance = $user['current_balance'];
    $newBalance = $type === 'credit' 
        ? $currentBalance + $amount 
        : $currentBalance - $amount;
    
    // Don't allow negative balance
    if ($newBalance < 0) {
        throw new Exception('Insufficient funds for debit');
    }
    
    // Update balance
    $stmt = $pdo->prepare("UPDATE customers SET current_balance = ? WHERE user_id = ?");
    $stmt->execute([$newBalance, $userId]);
    
    // Log transaction
    $adminId = Session::get('user_id');
    $description = $type === 'credit' 
        ? "Admin credit: +" . CURRENCY . number_format($amount, 2)
        : "Admin debit: -" . CURRENCY . number_format($amount, 2);
    
    $stmt = $pdo->prepare("
        INSERT INTO transaction_history 
        (user_id, transaction_type, amount, balance_after, description, timestamp) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $userId,
        $type === 'credit' ? 'credit' : 'debit',
        $amount,
        $newBalance,
        $description
    ]);
    
    echo json_encode([
        'success' => true,
        'new_balance' => $newBalance,
        'message' => ucfirst($type) . ' successful'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
