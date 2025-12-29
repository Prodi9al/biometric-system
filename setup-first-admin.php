<?php
require_once 'config/config.php';

$success = '';
$error = '';

if ($_POST) {
    $staff_id = $_POST['staff_id'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($staff_id && $full_name && $password) {
        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("INSERT INTO admin_users (staff_id, full_name, password_hash, role) VALUES (?, ?, ?, 'admin')");
            $stmt->execute([$staff_id, $full_name, password_hash($password, PASSWORD_DEFAULT)]);
            $success = "First admin created! You can now login at admin/login-modern.php";
        } catch (Exception $e) {
            $error = 'Failed: ' . $e->getMessage();
        }
    } else {
        $error = 'All fields required';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Setup First Admin</title>
    <style>
        body { font-family: Arial; max-width: 400px; margin: 50px auto; padding: 20px; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; }
        button { width: 100%; padding: 15px; background: #007cba; color: white; border: none; }
        .success { color: green; padding: 10px; background: #f0fff0; }
        .error { color: red; padding: 10px; background: #fff0f0; }
    </style>
</head>
<body>
    <h2>🔧 Setup First Admin</h2>
    
    <?php if ($success): ?>
        <div class="success"><?= $success ?></div>
        <p><a href="admin/login-modern.php">Go to Admin Login</a></p>
    <?php else: ?>
        
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="text" name="staff_id" placeholder="Staff ID (e.g. ADM001)" required>
            <input type="text" name="full_name" placeholder="Full Name" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Create First Admin</button>
        </form>
        
    <?php endif; ?>
</body>
</html>