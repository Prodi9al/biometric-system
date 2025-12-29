<?php
require_once '../config/config.php';
require_once '../core/Session.php';

header('Content-Type: application/json');

// Complete the login process by moving pending data to active session
if (Session::get('pending_user_id')) {
    Session::set('user_id', Session::get('pending_user_id'));
    Session::set('user_name', Session::get('pending_user_name'));
    Session::set('account_number', Session::get('pending_account_number'));
    Session::set('balance', Session::get('pending_balance'));
    
    // Clear pending data
    Session::set('pending_user_id', null);
    Session::set('pending_user_name', null);
    Session::set('pending_account_number', null);
    Session::set('pending_balance', null);
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'No pending login']);
}
?>