<?php
// Master System Health Check
require_once 'config/config.php';
header('Content-Type: text/plain');

echo "========================================\n";
echo "    BIOMETRIC SYSTEM HEALTH CHECK       \n";
echo "========================================\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Database Connection
echo "[1] Database Connection... ";
try {
    $pdo = getConnection();
    if ($pdo) {
        echo "OK\n";
    } else {
        echo "FAIL (Connection Returned Null)\n";
        exit;
    }
} catch (Exception $e) {
    echo "FAIL (" . $e->getMessage() . ")\n";
    exit;
}

// 2. Table Integrity
echo "\n[2] Table Integrity Check:\n";
$tables = ['customers', 'biometric_data', 'auth_logs', 'transaction_history'];
foreach ($tables as $table) {
    echo " - Table '$table'... ";
    try {
        if ($pdo) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "OK (Rows: $count)\n";
        } else {
            echo "SKIP (DB Connection Failed)\n";
        }
    } catch (Exception $e) {
        echo "FAIL (Missing or Corrupt)\n";
    }
}

// 3. Schema Check (Crucial for Truncation)
echo "\n[3] Biometric Schema Check:\n";
if ($pdo) {
    try {
        $stmt = $pdo->query("DESCRIBE biometric_data");
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $col) {
            if (in_array($col['Field'], ['face_enc', 'finger_enc'])) {
                echo " - Column '{$col['Field']}' Type: {$col['Type']}... ";
                if (stripos($col['Type'], 'longtext') !== false) {
                    echo "OK (LONGTEXT)\n";
                } else {
                    echo "WARNING (Attempts to fix: " . strtoupper($col['Type']) . ")\n";
                }
            }
        }
    } catch (Exception $e) {
        echo "FAIL (Schema Check Error: " . $e->getMessage() . ")\n";
    }
} else {
    echo " - SKIP (DB Connection Failed)\n";
}

// 4. Directory Permissions
echo "\n[4] Directory Permissions:\n";
$dirs = ['admin', 'api', 'core', 'config', 'python'];
foreach ($dirs as $dir) {
    echo " - Dir '$dir'... ";
    if (is_readable(__DIR__ . "/$dir")) {
        echo "OK\n";
    } else {
        echo "FAIL (Not Readable)\n";
    }
}

// 5. Python Integration
echo "\n[5] Python Service Check:\n";
$serviceUrl = "http://127.0.0.1:5000/health";
echo " - Pinging $serviceUrl... ";
$context = stream_context_create(['http' => ['timeout' => 2]]); // Short timeout
$response = @file_get_contents($serviceUrl, false, $context);
if ($response) {
    $data = json_decode($response, true);
    if (isset($data['status']) && $data['status'] === 'healthy') {
        echo "OK (Active)\n";
    } else {
        echo "WARNING (Response: $response)\n";
    }
} else {
    echo "FAIL (Not Running - Check start_biometric_service.bat)\n";
}

// 6. Config Constants
echo "\n[6] Configuration Check:\n";
echo " - BIOMETRIC_THRESHOLD: " . (defined('BIOMETRIC_THRESHOLD') ? BIOMETRIC_THRESHOLD : 'NOT DEFINED') . "\n";
echo " - DB_HOST: " . getenv('DB_HOST') . "\n";
echo " - SHARED_SECRET: " . (getenv('SHARED_SECRET') ? 'Set' : 'Using Default') . "\n";

echo "\n========================================\n";
echo "Scan Complete.\n";
?>
