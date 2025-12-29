<?php
// Main Configuration File
// Simple .env parsing (since we might not have composer/vlucas/phpdotenv)
if (!function_exists('loadEnv')) {
    function loadEnv($path) {
        if (!file_exists($path)) {
            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Load .env
loadEnv(__DIR__ . '/../.env');

// Main Configuration File
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'biometric_atm_db');

// Security Settings
// Security Settings
$envKey = getenv('ENCRYPTION_KEY');
if (!$envKey) {
    die('CRITICAL ERROR: ENCRYPTION_KEY is missing from environment. System refused to start.');
}
// NOTE: ENCRYPTION_KEY is for data-at-rest encryption (DB).
// SHARED_SECRET is for service-to-service HMAC signing (loaded directly in BiometricAuth).
define('ENCRYPTION_KEY', $envKey);
define('SESSION_TIMEOUT', 1800); // 30 minutes
define('MAX_LOGIN_ATTEMPTS', 3);
define('BIOMETRIC_THRESHOLD', 50.0);

// Application Settings
define('BANK_NAME', 'Ghana Secure Bank');
define('CURRENCY', 'GHS');
define('TIMEZONE', 'Africa/Accra');

date_default_timezone_set(TIMEZONE);

// Database Connection
function getConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return null;
    }
}
?>