<?php
require_once 'config/config.php';

try {
    $pdo = getConnection();
    
    // Reset admin password to 'admin123'
    $new_password = 'admin123';
    $hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("UPDATE admin_users SET password_hash = ? WHERE staff_id = 'ADM001'");
    $stmt->execute([$hash]);
    
    echo "✅ Admin password reset successfully!<br>";
    echo "Staff ID: ADM001<br>";
    echo "Password: admin123<br>";
    echo "<a href='admin/login-modern.php'>Go to Admin Login</a>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>