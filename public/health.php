<?php
/**
 * Health Check Endpoint for Docker/Load Balancer
 * Returns 200 OK if system is healthy, 503 if degraded
 */
require_once '../config/config.php';

header('Content-Type: application/json');

$checks = [];
$overall = 'healthy';

// 1. Database Connection
try {
    $pdo = getConnection();
    if ($pdo) {
        $checks['database'] = ['status' => 'healthy', 'message' => 'Connected'];
    } else {
        $checks['database'] = ['status' => 'unhealthy', 'message' => 'Connection failed'];
        $overall = 'degraded';
    }
} catch (Exception $e) {
    $checks['database'] = ['status' => 'unhealthy', 'message' => $e->getMessage()];
    $overall = 'degraded';
}

// 2. Required Tables Exist
if (isset($pdo)) {
    $tables = ['customers', 'biometric_data', 'auth_logs', 'secure_sessions', 'transaction_history'];
    $missing = [];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT 1 FROM $table LIMIT 1");
        } catch (Exception $e) {
            $missing[] = $table;
        }
    }
    if (empty($missing)) {
        $checks['tables'] = ['status' => 'healthy', 'message' => 'All tables present'];
    } else {
        $checks['tables'] = ['status' => 'unhealthy', 'message' => 'Missing: ' . implode(', ', $missing)];
        $overall = 'degraded';
    }
}

// 3. Python Biometric Service
$context = stream_context_create(['http' => ['timeout' => 2, 'ignore_errors' => true]]);
$response = @file_get_contents('http://127.0.0.1:5000/health', false, $context);
if ($response) {
    $data = json_decode($response, true);
    if (isset($data['status']) && $data['status'] === 'healthy') {
        $checks['biometric_service'] = ['status' => 'healthy', 'message' => 'Responding'];
    } else {
        $checks['biometric_service'] = ['status' => 'unhealthy', 'message' => 'Unexpected response'];
        $overall = 'degraded';
    }
} else {
    $checks['biometric_service'] = ['status' => 'unhealthy', 'message' => 'Not responding'];
    $overall = 'degraded';
}

// 4. Critical Config
$envVars = ['SHARED_SECRET', 'ENCRYPTION_KEY', 'DB_HOST', 'DB_NAME'];
$missingEnv = [];
foreach ($envVars as $var) {
    if (!getenv($var)) {
        $missingEnv[] = $var;
    }
}
if (empty($missingEnv)) {
    $checks['config'] = ['status' => 'healthy', 'message' => 'All required env vars set'];
} else {
    $checks['config'] = ['status' => 'unhealthy', 'message' => 'Missing: ' . implode(', ', $missingEnv)];
    $overall = 'degraded';
}

// 5. Write Permissions (for debug logs)
$writableDirs = ['../admin'];
$writable = true;
foreach ($writableDirs as $dir) {
    if (!is_writable($dir)) {
        $writable = false;
        break;
    }
}
$checks['permissions'] = ['status' => $writable ? 'healthy' : 'unhealthy', 'message' => $writable ? 'Writable' : 'Not writable'];
if (!$writable) $overall = 'degraded';

http_response_code($overall === 'healthy' ? 200 : 503);
echo json_encode([
    'status' => $overall,
    'service' => 'biometric-atm-web',
    'timestamp' => time(),
    'checks' => $checks
], JSON_PRETTY_PRINT);