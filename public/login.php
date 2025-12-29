<?php
require_once '../config/config.php';
require_once '../core/Session.php';
require_once '../core/BiometricAuth.php';

if (Session::isLoggedIn()) {
    header('Location: dashboard-modern.php');
    exit;
}

$error = '';
if ($_POST) {
    $account = $_POST['account'] ?? '';
    $pin = $_POST['pin'] ?? '';
    
    // Extract device fingerprint
    $fingerprint = [];
    if (!empty($_POST['fingerprint_data'])) {
        $fingerprint = json_decode($_POST['fingerprint_data'], true) ?: [];
    }
    
    if ($account && $pin) {
        $pdo = getConnection();
        if ($pdo) {
            // Remove spaces from input for flexible matching
            $cleanAccount = str_replace(' ', '', $account);
            
            // Match against DB by stripping spaces from stored value too
            $stmt = $pdo->prepare("SELECT user_id, full_name, pin_hash, current_balance FROM customers WHERE REPLACE(account_number, ' ', '') = ? AND status = 'active'");
            $stmt->execute([$cleanAccount]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($pin, $user['pin_hash'])) {
                // Store user data in session for biometric verification
                Session::set('pending_user_id', $user['user_id']);
                Session::set('pending_user_name', $user['full_name']);
                Session::set('pending_account_number', $account);
                Session::set('pending_balance', $user['current_balance']);
                Session::set('pending_balance', $user['current_balance']);
                
                // Log pending login success
                BiometricAuth::logAuthAttempt($user['user_id'], 'success', 'password_pin', 'Step 1: Account/PIN verified', 'customer', $fingerprint);
                
                header('Location: atm-login-sequential.php?step=2');
                exit;
            } else {
                $error = 'Invalid account number or PIN';
                // Log failed attempt (if user found, log their ID, otherwise 0 or null)
                $failedId = $user ? $user['user_id'] : 0;
                // Be careful not to log the actual PIN, just the account number
                BiometricAuth::logAuthAttempt($failedId, 'failure', 'password_pin', "Failed login for account: $account", 'customer', $fingerprint);
            }
        } else {
            $error = 'System temporarily unavailable';
        }
    } else {
        $error = 'Please enter both account number and PIN';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= BANK_NAME ?> - Customer Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/theme-overrides.css">
</head>
<body class="min-h-screen bg-slate-950 flex items-center justify-center p-4 font-sans">
    
    <!-- Toggle Theme Button -->
    <div class="absolute top-4 right-4 z-20">
        <button onclick="toggleTheme()" class="p-2 bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white rounded-full border border-slate-700 transition-colors" title="Toggle Theme">
            <svg id="themeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
        </button>
    </div>

    <div class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-xl shadow-2xl overflow-hidden relative">
        <div class="h-1 w-full bg-gradient-to-r from-green-600 via-cyan-500 to-green-600"></div>
        
        <div class="p-8">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-900/20 mb-4 border border-green-500/30">
                    <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                </div>
                <h1 class="text-xl text-white font-bold tracking-wide"><?= BANK_NAME ?></h1>
                <p class="text-slate-500 text-xs uppercase tracking-widest mt-2">Customer Banking Portal</p>
            </div>
            
            <form method="POST" class="space-y-4">
                <?php if ($error): ?>
                    <div class="bg-red-900/20 border border-red-500/30 text-red-400 px-4 py-2 rounded-lg text-sm text-center">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <div class="relative group">
                    <div class="absolute left-4 top-3.5 text-slate-500 group-focus-within:text-green-400 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <input type="text" name="account" placeholder="Account Number" 
                           value="<?= htmlspecialchars($_POST['account'] ?? '') ?>"
                           class="w-full bg-slate-950 border border-slate-700 text-white pl-12 pr-4 py-3 rounded-lg focus:outline-none focus:border-green-500 transition-colors placeholder:text-slate-600" required>
                </div>
                
                <div class="relative group">
                    <div class="absolute left-4 top-3.5 text-slate-500 group-focus-within:text-green-400 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <input type="password" name="pin" placeholder="Enter PIN" 
                           class="w-full bg-slate-950 border border-slate-700 text-white pl-12 pr-4 py-3 rounded-lg focus:outline-none focus:border-green-500 transition-colors placeholder:text-slate-600" required maxlength="6">
                </div>
                
                <!-- Hidden field for device fingerprint -->
                <input type="hidden" name="fingerprint_data" id="fingerprintData">
                
                <button type="submit" class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-3 rounded-lg shadow-lg shadow-green-900/20 transition-all mt-2 hover:scale-[1.02] active:scale-[0.98]">
                    🔐 SECURE LOGIN
                </button>
            </form>
            

            <div class="text-center mt-6">
                <p class="text-slate-500 text-xs mb-1">New Customer?</p>
                <a href="register.php" class="text-green-400 hover:text-green-300 text-sm font-bold uppercase tracking-wider hover:underline transition-colors">
                    Create New Account
                </a>
            </div>
            
            <div class="text-center mt-6">
                <a href="../admin/login-modern.php" class="text-slate-500 hover:text-slate-400 text-xs transition-colors">
                    Admin Portal →
                </a>
            </div>
        </div>
    </div>
    <script src="js/theme-system.js"></script>
    <script src="js/device-fingerprint.js"></script>
    <script>
        // Collect fingerprint before form submission
        document.querySelector('form').addEventListener('submit', async function(e) {
            const fpField = document.getElementById('fingerprintData');
            
            // Only collect if not already collected
            if (!fpField.value) {
                e.preventDefault();
                
                try {
                    const fingerprint = await DeviceFingerprint.collect();
                    fpField.value = JSON.stringify(fingerprint);
                    // Re-submit the form
                    this.submit();
                } catch (err) {
                    console.error('Fingerprint collection failed:', err);
                    // Submit anyway without fingerprint
                    this.submit();
                }
            }
        });
    </script>
</body>
</html>