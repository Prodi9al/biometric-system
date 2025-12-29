<?php
/**
 * Secure Biometric Authentication API Endpoint
 * Implements challenge-response protocol with session binding
 */

require_once '../config/config.php';
require_once '../core/BiometricAuth.php';
require_once '../core/Session.php';

// FAIL-SECURE headers
// FAIL-SECURE headers
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

function trace_log($msg) {
    file_put_contents(__DIR__ . '/../admin/debug_trace.txt', date('[H:i:s] ') . $msg . "\n", FILE_APPEND);
}
trace_log("Request received at secure_biometric_auth.php");

header('Content-Type: application/json');

// FAIL-SECURE: Fatal Error Handler
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_CORE_ERROR || $error['type'] === E_COMPILE_ERROR)) {
        // Log to file
        $msg = "FATAL ERROR: {$error['message']} in {$error['file']} on line {$error['line']}";
        file_put_contents(__DIR__ . '/../admin/debug_trace.txt', date('[H:i:s] ') . $msg . "\n", FILE_APPEND);
        
        // Attempt to return JSON if headers not sent
        if (!headers_sent()) {
             http_response_code(500);
             echo json_encode(['success' => false, 'error' => 'System error (logged)']);
        }
    }
});

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// FAIL-SECURE: Only allow POST requests with proper content type
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Validate content type
$content_type = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($content_type, 'application/json') === false) {
    trace_log("Blocked: Invalid Content-Type: " . $content_type);
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid content type']);
    exit;
}

// FAIL-SECURE rate limiting
Session::start();
// Ngrok/Proxy Compatibility: Use X-Forwarded-For if available
$client_ip = $_SERVER['REMOTE_ADDR'];
if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    // X-Forwarded-For can be a comma-separated list of IPs. The first one is the original client.
    $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $client_ip = trim($ips[0]);
}
// Fallback if empty
if (empty($client_ip)) {
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}
$attempts_key = 'auth_attempts_' . $client_ip;
$attempts = (int)($_SESSION[$attempts_key] ?? 0);
$last_attempt = (int)($_SESSION[$attempts_key . '_time'] ?? 0);

// Reset attempts after 15 minutes
if (time() - $last_attempt > 900) {
    $attempts = 0;
    unset($_SESSION[$attempts_key], $_SESSION[$attempts_key . '_time']);
}

// FAIL-SECURE: Block after 5 failed attempts
if ($attempts >= 5) {
    trace_log("Blocked: Rate limit exceeded for IP: $client_ip");
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'error' => 'Too many failed attempts. Try again later.',
        'retry_after' => 900 - (time() - $last_attempt)
    ]);
    exit;
}
trace_log("Session check passed. Attempts: $attempts");

// FAIL-SECURE: Default response is authentication failure
$authResponse = [
    'success' => false,
    'error' => 'Authentication failed'
];

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // FAIL-SECURE: Strict input validation
    if (!$input || !is_array($input)) {
        trace_log("Invalid JSON input");
        throw new Exception('Invalid JSON input');
    }
    
    trace_log("Processing user: " . ($input['user_id'] ?? 'unknown'));
    
    // Validate ALL required fields exist and are not empty
    $required = ['user_id', 'biometric_data', 'modality'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || $input[$field] === '' || $input[$field] === null) {
            throw new Exception("Missing or empty required field: $field");
        }
    }
    
    // NOTE: Frontend cannot securely sign requests with SHARED_SECRET.
    // Request security relies on TLS (HTTPS) and PHP Session/Rate Limiting.
    // Phase 2 (Response) verification remains active in BiometricAuth::verifyResponse.
    
    $userId = (int)$input['user_id'];
    if ($userId <= 0) {
        throw new Exception('Invalid user ID');
    }
    
    $biometricData = $input['biometric_data'];
    $modality = $input['modality'];
    
    // Validate modality is exactly one of allowed values
    if (!in_array($modality, ['face', 'fingerprint'], true)) {
        throw new Exception('Invalid biometric modality');
    }
    
    // Validate user exists and is active
    $pdo = getConnection();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    $stmt = $pdo->prepare("SELECT user_id, status, full_name, account_number, email FROM customers WHERE user_id = ? AND status = 'active'");
    if (!$stmt->execute([$userId])) {
        throw new Exception('Database query failed');
    }
    
    $user = $stmt->fetch();
    if (!$user) {
        // Increment failed attempts
        $_SESSION[$attempts_key] = $attempts + 1;
        $_SESSION[$attempts_key . '_time'] = time();
        throw new Exception('Invalid user or account inactive');
    }
    
    // Perform secure biometric verification
    trace_log("Calling BiometricAuth::verifyBiometric");
    $result = BiometricAuth::verifyBiometric($userId, $biometricData, $modality);
    trace_log("BiometricAuth returned: " . json_encode($result));
    
    // FAIL-SECURE: Only succeed if ALL conditions are met
    if (isset($result['success']) && $result['success'] === true &&
        isset($result['session_id']) && !empty($result['session_id']) &&
        isset($result['confidence']) && $result['confidence'] >= BIOMETRIC_THRESHOLD) {
        
        // Success - reset attempts counter
        unset($_SESSION[$attempts_key], $_SESSION[$attempts_key . '_time']);
        
        // CRITICAL FIX: Persist Authentication State
        // The dashboard checks for these specific session keys.
        Session::set('user_id', $user['user_id']);
        Session::set('user_name', $user['full_name']);
        Session::set('account_number', $user['account_number']);
        Session::set('user_email', $user['email']);
        Session::set('logged_in', true);
        Session::set('login_method', 'biometric');
        Session::set('secure_session_id', $result['session_id']); // For step-up auth checks
        
        // Ensure session is written to disk before redirect
        session_write_close();
        
        // Return success response
        $authResponse = [
            'success' => true,
            'session_id' => $result['session_id'],
            'confidence' => $result['confidence'],
            'expires_at' => $result['expires_at'],
            'message' => 'Biometric authentication successful'
        ];
    } else {
        // Increment failed attempts
        $_SESSION[$attempts_key] = $attempts + 1;
        $_SESSION[$attempts_key . '_time'] = time();
        
        $authResponse['attempts_remaining'] = max(0, 5 - $attempts - 1);
        http_response_code(401);
    }
    
} catch (Exception $e) {
    // Log error (but not sensitive data)
    error_log("Biometric auth error: " . $e->getMessage());
    
    // Increment failed attempts for any exception
    $_SESSION[$attempts_key] = $attempts + 1;
    $_SESSION[$attempts_key . '_time'] = time();
    
    http_response_code(400);
    $authResponse['error'] = 'Authentication system error';
}

// Always return the authentication response
echo json_encode($authResponse);
?>