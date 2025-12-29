<?php
require_once '../config/config.php';
require_once '../core/Session.php';

if (!Session::isLoggedIn() || Session::get('user_type') !== 'admin') {
    header('Location: login-modern.php');
    exit;
}

$success = '';
$error = '';

if ($_POST) {
    $full_name = $_POST['full_name'] ?? '';
    $staff_id = $_POST['staff_id'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'admin';
    
    if ($full_name && $staff_id && $password) {
        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("INSERT INTO admin_users (staff_id, full_name, email, password_hash, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$staff_id, $full_name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
            
            $success = 'Admin user created successfully!';
        } catch (Exception $e) {
            $error = 'Failed to create admin: ' . $e->getMessage();
        }
    } else {
        $error = 'Name, Staff ID and Password are required';
    }
}

// Generate new staff ID
$new_staff_id = 'ADM' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= BANK_NAME ?> - Admin Enrollment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../public/css/theme-overrides.css">
</head>
<body class="min-h-screen bg-slate-950 p-8 font-sans">
    <div class="max-w-2xl mx-auto">
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-8">
            <div class="flex items-center gap-3 mb-8">
                <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
                <div>
                    <h1 class="text-2xl text-white font-bold">New Admin Enrollment</h1>
                    <p class="text-slate-500 text-sm">Create new administrator account</p>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="bg-green-900/20 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg mb-6">
                    <?= htmlspecialchars($success) ?>
                    <div class="mt-2">
                        <a href="dashboard-modern.php" class="text-green-300 hover:text-green-200 underline">← Back to Dashboard</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-900/20 border border-red-500/30 text-red-400 px-4 py-3 rounded-lg mb-6">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="text-slate-300 text-sm font-medium block mb-2">Full Name</label>
                        <input type="text" name="full_name" placeholder="Administrator Name" 
                               value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                               class="w-full bg-slate-950 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-blue-500" required>
                    </div>

                    <div>
                        <label class="text-slate-300 text-sm font-medium block mb-2">Staff ID</label>
                        <div class="flex gap-2">
                            <input type="text" name="staff_id" value="<?= $_POST['staff_id'] ?? $new_staff_id ?>" 
                                   class="w-full bg-slate-950 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-blue-500" required>
                            <button type="button" onclick="generateStaffId()" class="p-3 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg border border-slate-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="text-slate-300 text-sm font-medium block mb-2">Email (Optional)</label>
                    <input type="email" name="email" placeholder="admin@example.com" 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           class="w-full bg-slate-950 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-blue-500">
                </div>

                <div>
                    <label class="text-slate-300 text-sm font-medium block mb-2">Role</label>
                    <select name="role" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-blue-500">
                        <option value="admin" <?= ($_POST['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrator</option>
                        <option value="manager" <?= ($_POST['role'] ?? '') === 'manager' ? 'selected' : '' ?>>Manager</option>
                        <option value="operator" <?= ($_POST['role'] ?? '') === 'operator' ? 'selected' : '' ?>>Operator</option>
                    </select>
                </div>

                <div>
                    <label class="text-slate-300 text-sm font-medium block mb-2">Password</label>
                    <input type="password" name="password" placeholder="Secure password" 
                           class="w-full bg-slate-950 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-blue-500" required>
                </div>

                <div class="flex gap-4 pt-4">
                    <a href="dashboard-modern.php" class="px-6 py-3 text-slate-400 hover:text-white transition-colors">Cancel</a>
                    <button type="submit" class="px-8 py-3 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-lg transition-colors">
                        Create Admin
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function generateStaffId() {
            const staffId = 'ADM' + String(Math.floor(Math.random() * 900) + 100).padStart(3, '0');
            document.querySelector('input[name="staff_id"]').value = staffId;
        }
    </script>
    <script src="../public/js/theme-system.js"></script>
</body>
</html>