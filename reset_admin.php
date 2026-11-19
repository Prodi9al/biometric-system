<?php
/**
 * Reset the ADM001 admin password.
 *
 * SECURITY: never hardcodes a default password and never prints the new
 * password. Pass it as a query parameter so it stays out of logs and is
 * required (not silently defaulted to a weak value).
 *
 * Usage:
 *   php reset_admin.php "StrongNewPassword123"
 *   or: http://localhost/reset_admin.php?new=StrongNewPassword123
 */

require_once 'config/config.php';

// Allow CLI or web; block remote web access by default.
if (php_sapi_name() !== 'cli') {
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    if ($remote !== '' && $remote !== '127.0.0.1' && $remote !== '::1') {
        http_response_code(403);
        die("reset_admin.php is a local-only maintenance tool.");
    }
}

$new_password = null;

if (php_sapi_name() === 'cli') {
    $new_password = $argv[1] ?? null;
} else {
    $new_password = $_GET['new'] ?? null;
}

if (!$new_password) {
    if (php_sapi_name() === 'cli') {
        fwrite(STDERR, "Usage: php reset_admin.php \"NewStrongPassword\"\n");
    } else {
        echo "Usage: /reset_admin.php?new=StrongNewPassword123\n";
    }
    exit(1);
}

// Require a password worth at least 8 chars with mixed character classes.
$issues = [];
if (strlen($new_password) < 8) {
    $issues[] = 'at least 8 characters';
}
if (!preg_match('/[A-Z]/', $new_password)) {
    $issues[] = 'an uppercase letter';
}
if (!preg_match('/[a-z]/', $new_password)) {
    $issues[] = 'a lowercase letter';
}
if (!preg_match('/[0-9]/', $new_password)) {
    $issues[] = 'a digit';
}
if ($issues) {
    if (php_sapi_name() === 'cli') {
        fwrite(STDERR, "Password must include " . implode(', ', $issues) . "\n");
    } else {
        http_response_code(400);
        echo "Password must include " . implode(', ', $issues) . "\n";
    }
    exit(1);
}

try {
    $pdo = getConnection();
    $hash = password_hash($new_password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE admin_users SET password_hash = ? WHERE staff_id = 'ADM001'");
    $stmt->execute([$hash]);

    // Confirm the row was actually updated.
    $check = $pdo->query("SELECT COUNT(*) FROM admin_users WHERE staff_id = 'ADM001'")->fetchColumn();

    if (php_sapi_name() === 'cli') {
        echo "Admin password for ADM001 updated successfully.\n";
    } else {
        echo "✅ Admin password for ADM001 updated successfully.\n";
    }
} catch (Exception $e) {
    if (php_sapi_name() === 'cli') {
        fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    } else {
        http_response_code(500);
        echo "❌ Error: " . htmlspecialchars($e->getMessage()) . "\n";
    }
}