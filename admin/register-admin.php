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
    $email = $_POST['email'] ?? '';
    $staff_id = $_POST['staff_id'] ?? '';
    $role = $_POST['role'] ?? 'manager';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $master_key = $_POST['master_key'] ?? '';
    
    if ($full_name && $email && $staff_id && $password && $confirm_password && $master_key) {
        if ($password !== $confirm_password) {
            $error = 'Passwords do not match';
        } elseif ($master_key !== 'MASTER2023') {
            $error = 'Invalid master authorization key';
        } else {
            try {
                $pdo = getConnection();
                $stmt = $pdo->prepare("INSERT INTO admin_users (staff_id, full_name, email, password_hash, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$staff_id, $full_name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
                $success = 'Administrator account created successfully!';
            } catch (Exception $e) {
                $error = 'Registration failed: ' . $e->getMessage();
            }
        }
    } else {
        $error = 'All fields are required';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= BANK_NAME ?> - Admin Registration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../public/css/theme-overrides.css">
</head>
<body class="min-h-screen bg-slate-950 flex items-center justify-center p-4 font-sans">
    <script src="../public/js/theme-system.js"></script>
    
    <!-- Registration Card -->
    <div class="w-full max-w-2xl bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden">
        
        <!-- Header Section -->
        <div class="bg-slate-900 p-8 border-b border-slate-800 flex items-start justify-between relative overflow-hidden">
            <!-- Background Decor -->
            <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/10 blur-[50px] rounded-full pointer-events-none"></div>
            
            <div class="relative z-10">
                <h1 class="text-2xl text-white font-bold flex items-center gap-3">
                    <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Register Administrator
                </h1>
                <p class="text-slate-400 text-sm mt-2 ml-11">Create a new high-privilege system account.</p>
            </div>
            
            <div class="flex flex-col items-end">
                <span class="px-3 py-1 bg-red-500/10 text-red-400 border border-red-500/20 rounded-full text-[10px] font-bold uppercase tracking-widest flex items-center gap-2">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    Restricted Access
                </span>
            </div>
        </div>

        <!-- Form Section -->
        <div class="p-8">
            <?php if ($success): ?>
                <div class="mb-6 bg-green-900/20 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg text-sm">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="mb-6 bg-red-900/20 border border-red-500/30 text-red-400 px-4 py-3 rounded-lg text-sm">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <!-- COLUMN 1: Personal Info -->
                <div class="space-y-5">
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">Personnel Details</h3>
                    
                    <!-- Full Name -->
                    <div class="space-y-1">
                        <label class="text-sm text-slate-300 font-medium">Full Legal Name</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-3 w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <input type="text" name="full_name" placeholder="John Doe" 
                                   value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                                   class="w-full bg-slate-950 border border-slate-700 rounded-lg py-2.5 pl-10 text-slate-200 focus:outline-none focus:border-blue-500 transition-colors" required>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="space-y-1">
                        <label class="text-sm text-slate-300 font-medium">Bank Email Address</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-3 w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <input type="email" name="email" placeholder="admin@bank-secure.com" 
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                   class="w-full bg-slate-950 border border-slate-700 rounded-lg py-2.5 pl-10 text-slate-200 focus:outline-none focus:border-blue-500 transition-colors" required>
                        </div>
                    </div>

                    <!-- Staff ID -->
                    <div class="space-y-1">
                        <label class="text-sm text-slate-300 font-medium">Staff ID Number</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-3 w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                            </svg>
                            <input type="text" name="staff_id" placeholder="STF-2023-XXX" 
                                   value="<?= htmlspecialchars($_POST['staff_id'] ?? '') ?>"
                                   class="w-full bg-slate-950 border border-slate-700 rounded-lg py-2.5 pl-10 text-slate-200 focus:outline-none focus:border-blue-500 transition-colors" required>
                        </div>
                    </div>
                </div>

                <!-- COLUMN 2: Security & Role -->
                <div class="space-y-5">
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">Security Clearance</h3>

                    <!-- Role Selection -->
                    <div class="space-y-1">
                        <label class="text-sm text-slate-300 font-medium">System Role</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="cursor-pointer">
                                <input type="radio" name="role" value="manager" checked class="sr-only">
                                <div id="managerBtn" class="px-3 py-2 text-xs font-bold rounded-lg border transition-all bg-blue-600/20 border-blue-500 text-blue-400 text-center">
                                    Manager
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="role" value="admin" class="sr-only">
                                <div id="adminBtn" class="px-3 py-2 text-xs font-bold rounded-lg border transition-all bg-slate-950 border-slate-700 text-slate-500 text-center">
                                    Super Admin
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="space-y-1">
                        <label class="text-sm text-slate-300 font-medium">Set Password</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-3 w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            <input type="password" name="password" placeholder="••••••••" 
                                   class="w-full bg-slate-950 border border-slate-700 rounded-lg py-2.5 pl-10 text-slate-200 focus:outline-none focus:border-blue-500 transition-colors" required>
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="space-y-1">
                        <label class="text-sm text-slate-300 font-medium">Confirm Password</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-3 w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            <input type="password" name="confirm_password" placeholder="••••••••" 
                                   class="w-full bg-slate-950 border border-slate-700 rounded-lg py-2.5 pl-10 text-slate-200 focus:outline-none focus:border-blue-500 transition-colors" required>
                        </div>
                    </div>
                </div>

                <!-- FULL WIDTH: Master Key -->
                <div class="md:col-span-2 mt-2 pt-6 border-t border-slate-800">
                    <div class="bg-red-500/5 border border-red-500/20 p-4 rounded-xl flex items-center gap-4">
                        <div class="p-3 bg-red-500/10 rounded-full text-red-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <label class="text-xs font-bold text-red-400 uppercase tracking-widest block mb-1">Master Authorization Key</label>
                            <input type="password" name="master_key" placeholder="Enter Physical Token Code to authorize creation" 
                                   class="w-full bg-transparent border-none text-white placeholder:text-red-900/50 focus:ring-0 p-0 text-sm" required>
                        </div>
                    </div>
                    <p class="text-[10px] text-slate-500 mt-2 text-center">
                        * This action will be logged in the Immutable Audit Trail (Ledger #04)
                    </p>
                </div>

            </form>

            <!-- Footer Actions -->
            <div class="mt-8 flex items-center justify-end gap-4">
                <a href="dashboard-modern.php" class="text-slate-400 text-sm hover:text-white transition-colors">Cancel</a>
                <button type="submit" form="registerForm" class="bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-500 hover:to-blue-400 text-white font-bold py-3 px-8 rounded-lg shadow-lg shadow-blue-900/20 flex items-center gap-2 hover:scale-[1.02] active:scale-[0.98] transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Create Account
                </button>
            </div>
        </div>
    </div>

    <script>
        // Add form ID
        document.querySelector('form').id = 'registerForm';
        
        // Role selection
        document.querySelectorAll('input[name="role"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const managerBtn = document.getElementById('managerBtn');
                const adminBtn = document.getElementById('adminBtn');
                
                if (this.value === 'manager') {
                    managerBtn.className = 'px-3 py-2 text-xs font-bold rounded-lg border transition-all bg-blue-600/20 border-blue-500 text-blue-400 text-center';
                    adminBtn.className = 'px-3 py-2 text-xs font-bold rounded-lg border transition-all bg-slate-950 border-slate-700 text-slate-500 text-center';
                } else {
                    managerBtn.className = 'px-3 py-2 text-xs font-bold rounded-lg border transition-all bg-slate-950 border-slate-700 text-slate-500 text-center';
                    adminBtn.className = 'px-3 py-2 text-xs font-bold rounded-lg border transition-all bg-purple-600/20 border-purple-500 text-purple-400 text-center';
                }
            });
        });
        
        // Click handlers for role buttons
        document.getElementById('managerBtn').onclick = () => {
            document.querySelector('input[value="manager"]').checked = true;
            document.querySelector('input[value="manager"]').dispatchEvent(new Event('change'));
        };
        
        document.getElementById('adminBtn').onclick = () => {
            document.querySelector('input[value="admin"]').checked = true;
            document.querySelector('input[value="admin"]').dispatchEvent(new Event('change'));
        };
    </script>
</body>
</html>