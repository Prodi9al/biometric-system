<?php
require_once '../config/config.php';
require_once '../core/Session.php';

header('Content-Type: application/json');

if (!Session::isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = Session::get('user_id');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    $type = $data['type'] ?? '';
    $amount = floatval($data['amount'] ?? 0);
    
    if (!$type || $amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid transaction data']);
        exit;
    }
    
    try {
        $pdo = getConnection();
        $pdo->beginTransaction();
        
        // Get current balance
        $stmt = $pdo->prepare("SELECT current_balance FROM customers WHERE user_id = ? FOR UPDATE");
        $stmt->execute([$user_id]);
        $current_balance = $stmt->fetchColumn();
        
        $new_balance = $current_balance;
        
        switch ($type) {
            case 'withdrawal':
                if ($amount > $current_balance) {
                    throw new Exception('Insufficient funds');
                }
                $new_balance = $current_balance - $amount;
                break;
                
            case 'deposit':
                $new_balance = $current_balance + $amount;
                break;
                
            case 'transfer':
                $recipient = $data['recipient'] ?? '';
                if (!$recipient) {
                    throw new Exception('Recipient account required');
                }
                if ($amount > $current_balance) {
                    throw new Exception('Insufficient funds');
                }
                
                // Check if recipient exists
                $stmt = $pdo->prepare("SELECT user_id FROM customers WHERE account_number = ?");
                $stmt->execute([$recipient]);
                if (!$stmt->fetchColumn()) {
                    throw new Exception('Recipient account not found');
                }
                
                $new_balance = $current_balance - $amount;
                break;
                
            default:
                throw new Exception('Invalid transaction type');
        }
        
        // Update balance
        $stmt = $pdo->prepare("UPDATE customers SET current_balance = ? WHERE user_id = ?");
        $stmt->execute([$new_balance, $user_id]);
        
        // Record transaction
        $stmt = $pdo->prepare("
            INSERT INTO transaction_history (user_id, trans_type, amount, auth_status, timestamp) 
            VALUES (?, ?, ?, 'Success', NOW())
        ");
        $stmt->execute([$user_id, ucfirst($type), $amount]);
        
        // Update session balance
        Session::set('balance', $new_balance);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Transaction successful',
            'new_balance' => $new_balance
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
} else {
    // GET request - return transaction history
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("
            SELECT trans_type, amount, auth_status, timestamp 
            FROM transaction_history 
            WHERE user_id = ? 
            ORDER BY timestamp DESC 
            LIMIT 20
        ");
        $stmt->execute([$user_id]);
        $transactions = $stmt->fetchAll();
        
        echo json_encode($transactions);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to load transactions']);
    }
}
?>