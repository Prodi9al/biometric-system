<?php
require_once '../config/config.php';
require_once '../core/Session.php';
require_once '../core/BiometricAuth.php';

if (Session::get('user_type') === 'admin') {
    header('Location: dashboard-modern.php');
    exit;
}

$error = '';
if ($_POST) {
    $staff_id = $_POST['staff_id'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Extract device fingerprint
    $fingerprint = [];
    if (!empty($_POST['fingerprint_data'])) {
        $fingerprint = json_decode($_POST['fingerprint_data'], true) ?: [];
    }
    
    if ($staff_id && $password) {
        $pdo = getConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT admin_id, full_name, password_hash, role, is_active FROM admin_users WHERE staff_id = ?");
            $stmt->execute([$staff_id]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password_hash'])) {
                // Check if account is active (keeping this as it's good security)
                if (isset($admin['is_active']) && !$admin['is_active']) {
                    $error = 'Account has been deactivated';
                    $adminId = $admin['admin_id'];
                    BiometricAuth::logAuthAttempt($adminId, 'failure', 'password_token', "Deactivated account login attempt: $staff_id", 'admin', $fingerprint);
                } else {
                    Session::set('user_id', $admin['admin_id']);
                    Session::set('user_name', $admin['full_name']);
                    Session::set('user_type', 'admin');
                    // Removed admin_role
                    Session::set('staff_id', $staff_id);
                    
                    // Update last_login
                    $pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE admin_id = ?")->execute([$admin['admin_id']]);
                    
                    // Log Admin Login
                    BiometricAuth::logAuthAttempt($admin['admin_id'], 'success', 'password_token', 'Admin Access Granted', 'admin', $fingerprint);
                    
                    header('Location: dashboard-modern.php');
                    exit;
                }
            } else {
                $error = 'Invalid credentials';
                // Log Failed Admin Login
                // If we found an admin by ID, log that ID, otherwise 0
                $adminId = $admin ? $admin['admin_id'] : 0;
                BiometricAuth::logAuthAttempt($adminId, 'failure', 'password_token', "Failed admin login: $staff_id", 'admin', $fingerprint);
            }
        } else {
            $error = 'System unavailable';
        }
    } else {
        $error = 'All fields required';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= BANK_NAME ?> - Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../public/css/theme-overrides.css">
    <style>
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .animate-pulse { animation: pulse 2s infinite; }
    </style>
</head>
<body class="min-h-screen bg-slate-950 flex items-center justify-center p-4 font-sans">
    <script src="../public/js/theme-system.js"></script>
    <div class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-xl shadow-2xl overflow-hidden relative">
        <div class="h-1 w-full bg-gradient-to-r from-blue-600 via-cyan-500 to-blue-600"></div>
        
        <div class="p-8">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-900/20 mb-4 border border-blue-500/30">
                    <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <h1 class="text-xl text-white font-bold tracking-wide">System Administration</h1>
                <p class="text-slate-500 text-xs uppercase tracking-widest mt-2">Authorized Personnel Only</p>
            </div>
            
            <form method="POST" class="space-y-4">
                <?php if ($error): ?>
                    <div class="bg-red-900/20 border border-red-500/30 text-red-400 px-4 py-2 rounded-lg text-sm text-center">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <div class="relative group">
                    <div class="absolute left-4 top-3.5 text-slate-500 group-focus-within:text-blue-400 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <input type="text" name="staff_id" placeholder="Admin ID" 
                           value="<?= htmlspecialchars($_POST['staff_id'] ?? '') ?>"
                           class="w-full bg-slate-950 border border-slate-700 text-white pl-12 pr-4 py-3 rounded-lg focus:outline-none focus:border-blue-500 transition-colors placeholder:text-slate-600" required>
                </div>
                
                <div class="relative group">
                    <div class="absolute left-4 top-3.5 text-slate-500 group-focus-within:text-blue-400 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <input type="password" name="password" placeholder="Secure Token / Password" 
                           class="w-full bg-slate-950 border border-slate-700 text-white pl-12 pr-4 py-3 rounded-lg focus:outline-none focus:border-blue-500 transition-colors placeholder:text-slate-600" required>
                </div>
                
                <!-- Hidden field for device fingerprint -->
                <input type="hidden" name="fingerprint_data" id="fingerprintData">
                
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-lg shadow-lg shadow-blue-900/20 transition-all mt-2 hover:scale-[1.02] active:scale-[0.98]">
                    ACCESS CONTROL PANEL
                </button>
            </form>
            
            <div class="mt-8 pt-6 border-t border-slate-800 flex items-center justify-between text-xs text-slate-500">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                    <span>Server Online</span>
                </div>
                <div class="flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                    </svg>
                    <span>v.4.2.0 (Secure)</span>
                </div>
            </div>
            
            <div class="text-center mt-6">
                <a href="../public/login.php" class="text-slate-500 hover:text-slate-400 text-xs transition-colors">
                    ← Customer Login
                </a>
            </div>
        </div>
    </div>
    <script src="../public/js/device-fingerprint.js"></script>
    <script>
        // Collect fingerprint before form submission
        document.querySelector('form').addEventListener('submit', async function(e) {
            const fpField = document.getElementById('fingerprintData');
            
            if (!fpField.value) {
                e.preventDefault();
                
                try {
                    const fingerprint = await DeviceFingerprint.collect();
                    fpField.value = JSON.stringify(fingerprint);
                    this.submit();
                } catch (err) {
                    console.error('Fingerprint collection failed:', err);
                    this.submit();
                }
            }
        });
    </script>
</body>
</html>