<?php
require_once '../config/config.php';
require_once '../core/Session.php';

if (!Session::isLoggedIn() || Session::get('user_type') !== 'admin') {
    header('Location: login-modern.php');
    exit;
}

$admin_name = Session::get('user_name');
$staff_id = Session::get('staff_id');

$pdo = getConnection();

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) FROM customers WHERE status = 'active'");
$total_customers = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM transaction_history WHERE DATE(timestamp) = CURDATE()");
$today_transactions = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT SUM(current_balance) FROM customers WHERE status = 'active'");
$total_deposits = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM biometric_data");
$biometric_enrolled = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM customers WHERE status = 'pending'");
$pending_count = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= BANK_NAME ?> - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes progressBar {
            from { width: 0%; }
            to { width: var(--progress); }
        }
        .progress-bar {
            animation: progressBar 1s ease-out;
        }
    </style>
    <link rel="stylesheet" href="../public/css/theme-overrides.css">
</head>
<body class="min-h-screen bg-slate-950 p-6 font-sans text-slate-200">
    <header class="flex justify-between items-center mb-8 pb-6 border-b border-slate-800">
        <div>
            <h1 class="text-2xl font-bold text-white">System Dashboard</h1>
            <p class="text-slate-500 text-xs uppercase tracking-widest">Biometric ATM Network / Terminal #042</p>
        </div>
        <div class="flex items-center gap-4">
            <button onclick="resetSystem()" class="p-2 bg-red-800 hover:bg-red-700 rounded-full border border-red-700 transition-colors" title="Reset System">
                <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
            <button onclick="toggleTheme()" class="p-2 bg-slate-800 hover:bg-slate-700 rounded-full border border-slate-700 transition-colors" title="Toggle Theme">
                <svg id="themeIcon" class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </button>
            <span class="px-3 py-1 bg-green-500/10 text-green-400 border border-green-500/20 rounded-full text-xs font-bold">SYSTEM HEALTHY</span>
            <div class="w-10 h-10 bg-slate-800 rounded-full flex items-center justify-center border border-slate-700">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </div>
            <a href="logout.php" class="text-slate-400 hover:text-white transition-colors text-sm">Logout</a>
        </div>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Total Users -->
        <div class="bg-slate-900 border border-slate-800 p-6 rounded-xl relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-10 text-blue-500">
                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                </svg>
            </div>
            <div class="relative z-10">
                <p class="text-slate-400 text-xs uppercase tracking-widest mb-1">Total Users</p>
                <h3 class="text-2xl font-bold text-white mb-1"><?= number_format($total_customers) ?></h3>
                <p class="text-xs text-blue-500 text-opacity-80">+<?= rand(5, 25) ?> this week</p>
            </div>
        </div>

        <!-- Biometric Accuracy -->
        <div class="bg-slate-900 border border-slate-800 p-6 rounded-xl relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-10 text-green-500">
                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
            <div class="relative z-10">
                <p class="text-slate-400 text-xs uppercase tracking-widest mb-1">Biometric Accuracy</p>
                <h3 class="text-2xl font-bold text-white mb-1">99.9%</h3>
                <p class="text-xs text-green-500 text-opacity-80">0.1% FRR</p>
            </div>
        </div>

        <!-- Total Cash -->
        <div class="bg-slate-900 border border-slate-800 p-6 rounded-xl relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-10 text-cyan-500">
                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
            <div class="relative z-10">
                <p class="text-slate-400 text-xs uppercase tracking-widest mb-1">Total Cash</p>
                <h3 class="text-2xl font-bold text-white mb-1"><?= CURRENCY ?> <?= number_format($total_deposits, 0) ?></h3>
                <p class="text-xs text-cyan-500 text-opacity-80">82% Capacity</p>
            </div>
        </div>

        <!-- Security Alerts -->
        <div class="bg-slate-900 border border-slate-800 p-6 rounded-xl relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-10 text-red-500">
                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <div class="relative z-10">
                <p class="text-slate-400 text-xs uppercase tracking-widest mb-1">Security Alerts</p>
                <h3 class="text-2xl font-bold text-white mb-1">0</h3>
                <p class="text-xs text-red-500 text-opacity-80">System Secure</p>
            </div>
        </div>

        <!-- Pending Approvals Widget -->
        <div class="bg-slate-900 border border-slate-800 p-6 rounded-xl relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-10 text-yellow-500">
                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="relative z-10">
                <p class="text-slate-400 text-xs uppercase tracking-widest mb-1">Pending Approvals</p>
                <h3 id="pendingCount" class="text-2xl font-bold text-white mb-1"><?= number_format($pending_count) ?></h3>
                <button onclick="showPendingModal()" class="text-xs text-yellow-500 text-opacity-80 hover:text-yellow-400 underline">Review Requests</button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-6">
                <h3 class="text-white font-bold mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                    </svg>
                    Administrative Actions
                </h3>
                <div class="grid grid-cols-2 gap-4">
                    <button onclick="enrollCustomer()" class="p-4 bg-blue-600/10 border border-blue-500/30 hover:bg-blue-600/20 rounded-lg text-left transition-colors group">
                        <span class="block text-blue-400 font-bold mb-1 group-hover:text-blue-300">Enroll New Customer</span>
                        <span class="text-xs text-slate-500">Capture Face, Retina, Fingerprint</span>
                    </button>
                    <button onclick="enrollAdmin()" class="p-4 bg-green-600/10 border border-green-500/30 hover:bg-green-600/20 rounded-lg text-left transition-colors group">
                        <span class="block text-green-400 font-bold mb-1 group-hover:text-green-300">Enroll New Admin</span>
                        <span class="text-xs text-slate-500">Create administrator account</span>
                    </button>
                    <button onclick="viewAuditLogs()" class="p-4 bg-slate-800/50 border border-slate-700 hover:bg-slate-800 rounded-lg text-left transition-colors">
                        <span class="block text-white font-bold mb-1">View Audit Logs</span>
                        <span class="text-xs text-slate-500">Check recent access attempts</span>
                    </button>
                    <button onclick="openBalanceSearch()" class="p-4 bg-purple-600/10 border border-purple-500/30 hover:bg-purple-600/20 rounded-lg text-left transition-colors group">
                        <span class="block text-purple-400 font-bold mb-1 group-hover:text-purple-300">User Management</span>
                        <span class="text-xs text-slate-500">Balances, Deletion, Details</span>
                    </button>
                </div>
            </div>
        </div>

            <div class="bg-slate-900 border border-slate-800 rounded-xl p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-white font-bold flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    Cash Levels
                </h3>
                <button onclick="refreshCashLevels()" class="p-2 hover:bg-slate-800 rounded-full transition-colors">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </button>
            </div>
            <div class="space-y-6">
                <!-- Cassette A -->
                <div class="mb-4">
                    <div class="flex justify-between text-xs text-slate-400 mb-1">
                        <span>Cassette A (<?= CURRENCY ?> 50 Notes)</span>
                        <span><?= CURRENCY ?> 120,000</span>
                    </div>
                    <div class="h-2 w-full bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full rounded-full bg-green-500 progress-bar" style="--progress: 85%; width: 85%;"></div>
                    </div>
                </div>

                <!-- Cassette B -->
                <div class="mb-4">
                    <div class="flex justify-between text-xs text-slate-400 mb-1">
                        <span>Cassette B (<?= CURRENCY ?> 20 Notes)</span>
                        <span><?= CURRENCY ?> 80,000</span>
                    </div>
                    <div class="h-2 w-full bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full rounded-full bg-green-500 progress-bar" style="--progress: 60%; width: 60%;"></div>
                    </div>
                </div>

                <!-- Cassette C -->
                <div class="mb-4">
                    <div class="flex justify-between text-xs text-slate-400 mb-1">
                        <span>Cassette C (<?= CURRENCY ?> 10 Notes)</span>
                        <span><?= CURRENCY ?> 20,000</span>
                    </div>
                    <div class="h-2 w-full bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full rounded-full bg-red-500 progress-bar" style="--progress: 30%; width: 30%;"></div>
                    </div>
                </div>

                <!-- Cassette D -->
                <div class="mb-4">
                    <div class="flex justify-between text-xs text-slate-400 mb-1">
                        <span>Cassette D (<?= CURRENCY ?> 200 Notes)</span>
                        <span><?= CURRENCY ?> 230,000</span>
                    </div>
                    <div class="h-2 w-full bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full rounded-full bg-green-500 progress-bar" style="--progress: 95%; width: 95%;"></div>
                    </div>
                </div>
            </div>
        </div>

    <!-- PENDING APPROVALS MODAL -->
    <div id="pendingModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-slate-900 border border-slate-800 rounded-xl w-full max-w-4xl max-h-[80vh] overflow-hidden">
                <div class="p-6 border-b border-slate-800 flex justify-between items-center">
                    <h3 class="text-white font-bold text-lg">Pending Registrations</h3>
                    <button onclick="closePendingModal()" class="text-slate-400 hover:text-white">✕</button>
                </div>
                <div class="p-6 overflow-y-auto max-h-96">
                    <div id="pendingContent" class="space-y-3">
                        <!-- Content Loaded via JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- OLD MODAL REMOVED -->

    <!-- AUDIT LOG CONSOLE (SPLIT VIEW) -->
    <div id="auditModal" class="fixed inset-0 bg-black/80 backdrop-blur-md z-50 hidden flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-7xl h-[85vh] flex overflow-hidden shadow-2xl relative">
            
            <!-- LEFT PANEL: LOG DIRECTORY -->
            <div class="w-full md:w-1/3 lg:w-1/4 border-r border-slate-800 flex flex-col bg-slate-900/50">
                <div class="p-4 border-b border-slate-800">
                    <h3 class="text-white font-bold text-lg mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Audit Logs
                    </h3>
                    <div class="relative">
                        <svg class="w-4 h-4 absolute left-3 top-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        <input type="text" id="auditLogFilter" onkeyup="filterAuditLogs()" placeholder="Search logs..." class="w-full bg-slate-800 text-white pl-10 p-2.5 rounded-lg border border-slate-700 focus:border-cyan-500 outline-none transition-colors text-sm">
                    </div>
                    
                    <!-- NEW CONTROLS -->
                    <div class="mt-4 flex gap-2">
                        <select id="userFilterSelect" onchange="filterLogsByUser(this.value)" class="flex-1 bg-slate-800 text-white p-2 rounded-lg border border-slate-700 text-xs outline-none focus:border-cyan-500">
                             <option value="">All Users</option>
                             <!-- Populated via JS -->
                        </select>
                        <button onclick="deleteAllLogs()" class="px-3 py-2 bg-red-800/20 text-red-400 border border-red-800/50 hover:bg-red-800 hover:text-white rounded-lg transition-colors text-xs font-bold" title="Delete ALL History">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>
                </div>
                <div id="auditLogList" class="flex-1 overflow-y-auto p-2 space-y-1">
                    <!-- List loaded via JS -->
                    <div class="text-center text-slate-500 text-sm py-10">Loading logs...</div>
                </div>
            </div>

            <!-- RIGHT PANEL: LOG DETAILS -->
            <div class="flex-1 flex flex-col bg-slate-950/50 relative min-w-0">
                <button onclick="closeAuditModal()" class="absolute top-4 right-4 text-slate-500 hover:text-white z-20 p-2 rounded-full hover:bg-slate-800 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
                
                <!-- EMPTY STATE -->
                <div id="auditEmptyState" class="flex-1 flex flex-col items-center justify-center text-slate-500">
                    <div class="w-20 h-20 bg-slate-900 rounded-full flex items-center justify-center mb-4 border border-slate-800">
                        <svg class="w-10 h-10 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <p class="text-lg font-medium text-slate-400">Select a log entry</p>
                    <p class="text-sm">View authentication attempts and forensic details</p>
                </div>

                <!-- DETAIL CONTENT -->
                <div id="auditDetailContent" class="hidden flex-col h-full">
                    <!-- Header -->
                    <div class="px-8 pt-8 pb-0 border-b border-slate-800 bg-slate-900/30 shrink-0">
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <div class="flex items-center gap-4 mb-2">
                                    <div id="detailStatusIcon" class="w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center border-2 border-green-500">
                                        <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </div>
                                    <div>
                                        <h2 id="detailLogUser" class="text-2xl font-bold text-white tracking-tight">User Name</h2>
                                        <div id="detailLogStatus" class="mt-1"></div>
                                        <div id="detailLogLocation" class="mt-2 flex items-center gap-1 text-sm text-slate-400"></div>
                                    </div>
                                </div>
                                <p id="detailLogType" class="text-slate-400 text-lg">Auth Type</p>
                            </div>
                            <div class="flex flex-col gap-2 items-end">
                                <div class="text-right bg-slate-900/50 p-4 rounded-xl border border-slate-800/50">
                                    <p class="text-slate-400 text-xs uppercase tracking-widest mb-1">Timestamp</p>
                                    <p id="detailLogTimestamp" class="text-xl font-bold text-white">--</p>
                                </div>
                                <button onclick="deleteCurrentLog()" class="px-4 py-2 bg-red-600/20 hover:bg-red-600 border border-red-500/30 hover:border-red-500 text-red-400 hover:text-white rounded-lg text-sm font-medium transition-all flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    Delete Log
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Details Panels -->
                    <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
                        <div class="space-y-6">
                            <!-- Forensic Data Panel -->
                            <div class="bg-slate-900/50 p-6 rounded-xl border border-slate-800">
                                <h4 class="text-slate-500 text-xs uppercase tracking-widest mb-4 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                    Network Forensics
                                </h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-slate-500 text-xs mb-1">IP Address</label>
                                        <p id="infoIP" class="text-cyan-400 font-mono text-sm">--</p>
                                    </div>
                                    <div>
                                        <label class="block text-slate-500 text-xs mb-1">Proxy/VPN Detection</label>
                                        <p id="infoForwarded" class="text-yellow-400 font-mono text-sm">--</p>
                                    </div>
                                    <div class="col-span-2">
                                        <label class="block text-slate-500 text-xs mb-1">Browser/Device</label>
                                        <p id="infoUserAgent" class="text-slate-300 text-sm break-all">--</p>
                                    </div>
                                    <div>
                                        <label class="block text-slate-500 text-xs mb-1">Language</label>
                                        <p id="infoLanguage" class="text-slate-300 text-sm">--</p>
                                    </div>
                                    <div>
                                        <label class="block text-slate-500 text-xs mb-1">Request URI</label>
                                        <p id="infoRequestURI" class="text-blue-400 text-sm break-all">--</p>
                                    </div>
                                    <div class="col-span-2" id="infoRefererContainer" style="display:none">
                                        <label class="block text-slate-500 text-xs mb-1">Referer</label>
                                        <p id="infoReferer" class="text-blue-400 text-sm break-all">--</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Device Fingerprint Panel -->
                            <div class="bg-gradient-to-br from-purple-900/20 to-blue-900/20 p-6 rounded-xl border border-purple-500/30">
                                <h4 class="text-purple-400 text-xs uppercase tracking-widest mb-4 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.2-2.85.577-4.147"></path></svg>
                                    Device Fingerprint
                                </h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-slate-500 text-xs mb-1">Canvas Hash (Unique ID)</label>
                                        <p id="infoCanvasFingerprint" class="text-purple-300 font-mono text-xs break-all">--</p>
                                    </div>
                                    <div>
                                        <label class="block text-slate-500 text-xs mb-1">Screen Resolution</label>
                                        <p id="infoScreenResolution" class="text-slate-300 text-sm">--</p>
                                    </div>
                                    <div>
                                        <label class="block text-slate-500 text-xs mb-1">Timezone</label>
                                        <p id="infoTimezone" class="text-slate-300 text-sm">--</p>
                                    </div>
                                    <div>
                                        <label class="block text-slate-500 text-xs mb-1">Platform/OS</label>
                                        <p id="infoPlatform" class="text-slate-300 text-sm">--</p>
                                    </div>
                                    <div>
                                        <label class="block text-slate-500 text-xs mb-1">CPU Cores</label>
                                        <p id="infoHardwareConcurrency" class="text-slate-300 text-sm">--</p>
                                    </div>
                                    <div>
                                        <label class="block text-slate-500 text-xs mb-1">RAM (GB)</label>
                                        <p id="infoDeviceMemory" class="text-slate-300 text-sm">--</p>
                                    </div>
                                    <div>
                                        <label class="block text-slate-500 text-xs mb-1">Color Depth</label>
                                        <p id="infoColorDepth" class="text-slate-300 text-sm">--</p>
                                    </div>
                                    <div>
                                        <label class="block text-slate-500 text-xs mb-1">Pixel Ratio</label>
                                        <p id="infoPixelRatio" class="text-slate-300 text-sm">--</p>
                                    </div>
                                    <div>
                                        <label class="block text-slate-500 text-xs mb-1">Touch Device</label>
                                        <p id="infoTouchSupport" class="text-slate-300 text-sm">--</p>
                                    </div>
                                    <div>
                                        <label class="block text-slate-500 text-xs mb-1">Do Not Track</label>
                                        <p id="infoDoNotTrack" class="text-slate-300 text-sm">--</p>
                                    </div>
                                    <div class="col-span-2">
                                        <label class="block text-slate-500 text-xs mb-1">WebGL GPU</label>
                                        <p id="infoWebGLVendor" class="text-slate-300 text-sm break-all">--</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Event Details Panel -->
                            <div class="bg-slate-900/50 p-6 rounded-xl border border-slate-800">
                                <h4 class="text-slate-500 text-xs uppercase tracking-widest mb-4">Event Details</h4>
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-slate-500 text-xs mb-1">Log ID</label>
                                        <p id="infoLogID" class="text-white font-mono">#0</p>
                                    </div>
                                    <div>
                                        <label class="block text-slate-500 text-xs mb-1">User ID</label>
                                        <p id="infoUserID" class="text-white font-mono">#0</p>
                                    </div>
                                    <div>
                                        <label class="block text-slate-500 text-xs mb-1">User Type</label>
                                        <p id="infoUserType" class="text-white">--</p>
                                    </div>
                                    <div>
                                        <label class="block text-slate-500 text-xs mb-1">Additional Details</label>
                                        <p id="infoDetails" class="text-slate-300">--</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>




    <!-- PENDING APPROVALS MODAL -->
    <div id="pendingModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-slate-900 border border-slate-800 rounded-xl w-full max-w-4xl max-h-[80vh] overflow-hidden">
                <div class="p-6 border-b border-slate-800 flex justify-between items-center">
                    <h3 class="text-white font-bold text-lg">Pending Registrations</h3>
                    <button onclick="closePendingModal()" class="text-slate-400 hover:text-white">✕</button>
                </div>
                <div class="p-6 overflow-y-auto max-h-96">
                    <div id="pendingContent" class="space-y-3">
                        <!-- Content Loaded via JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- USER MANAGEMENT CONSOLE (SPLIT VIEW) -->
    <div id="balanceModal" class="fixed inset-0 bg-black/80 backdrop-blur-md z-50 hidden flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-7xl h-[85vh] flex overflow-hidden shadow-2xl relative">
            
            <!-- LEFT PANEL: DIRECTORY -->
            <div class="w-full md:w-1/3 lg:w-1/4 border-r border-slate-800 flex flex-col bg-slate-900/50">
                <div class="p-4 border-b border-slate-800">
                    <h3 class="text-white font-bold text-lg mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        User Directory
                    </h3>
                    <div class="relative">
                        <svg class="w-4 h-4 absolute left-3 top-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        <input type="text" id="userListFilter" onkeyup="filterUserList()" placeholder="Search users..." class="w-full bg-slate-800 text-white pl-10 p-2.5 rounded-lg border border-slate-700 focus:border-cyan-500 outline-none transition-colors text-sm">
                    </div>
                </div>
                <div id="activeUsersList" class="flex-1 overflow-y-auto p-2 space-y-1">
                    <!-- List loaded via JS -->
                    <div class="text-center text-slate-500 text-sm py-10">Loading directory...</div>
                </div>
            </div>

            <!-- RIGHT PANEL: DETAILS -->
            <div class="flex-1 flex flex-col bg-slate-950/50 relative min-w-0">
                <button onclick="closeBalanceModal()" class="absolute top-4 right-4 text-slate-500 hover:text-white z-20 p-2 rounded-full hover:bg-slate-800 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
                
                <!-- EMPTY STATE -->
                <div id="userEmptyState" class="flex-1 flex flex-col items-center justify-center text-slate-500">
                    <div class="w-20 h-20 bg-slate-900 rounded-full flex items-center justify-center mb-4 border border-slate-800">
                        <svg class="w-10 h-10 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </div>
                    <p class="text-lg font-medium text-slate-400">Select a user to manage</p>
                    <p class="text-sm">View details, transactions, and security settings</p>
                </div>

                <!-- DETAIL CONTENT -->
                <div id="userDetailsContent" class="hidden flex-col h-full">
                    <!-- Header -->
                    <div class="px-8 pt-8 pb-0 border-b border-slate-800 bg-slate-900/30 shrink-0">
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <div class="flex items-center gap-4 mb-2">
                                    <div class="w-16 h-16 rounded-full bg-slate-800 flex items-center justify-center overflow-hidden border-2 border-slate-700 shadow-lg relative cursor-pointer group">
                                         <img id="detailImage" src="" class="w-full h-full object-cover hidden">
                                         <div id="detailPlaceholder" class="text-slate-500">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                         </div>
                                         <div class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                             <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                         </div>
                                    </div>
                                    <div>
                                        <h2 id="detailName" class="text-3xl font-bold text-white tracking-tight">User Name</h2>
                                        <div id="detailStatusBadge" class="mt-1"></div>
                                    </div>
                                </div>
                                <p id="detailAccount" class="text-slate-400 font-mono text-lg tracking-wider">0000 0000</p>
                            </div>
                            <div class="text-right bg-slate-900/50 p-4 rounded-xl border border-slate-800/50">
                                <p class="text-slate-400 text-xs uppercase tracking-widest mb-1">Available Balance</p>
                                <p class="text-3xl font-bold text-white"><?= CURRENCY ?> <span id="detailBalance">0.00</span></p>
                            </div>
                        </div>
                        
                        <!-- Tabs -->
                        <div class="flex gap-8">
                            <button onclick="switchTab('overview')" id="tab-overview" class="pb-4 px-2 text-cyan-400 border-b-2 border-cyan-400 font-bold transition-all text-sm uppercase tracking-wide">Overview</button>
                            <button onclick="switchTab('transactions')" id="tab-transactions" class="pb-4 px-2 text-slate-400 border-b-2 border-transparent hover:text-slate-200 font-medium transition-all text-sm uppercase tracking-wide">Transactions</button>
                            <button onclick="switchTab('security')" id="tab-security" class="pb-4 px-2 text-slate-400 border-b-2 border-transparent hover:text-slate-200 font-medium transition-all text-sm uppercase tracking-wide">Security & Actions</button>
                        </div>
                    </div>

                    <!-- Tab Panels -->
                    <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
                        <!-- OVERVIEW TAB -->
                        <div id="panel-overview" class="space-y-6 animate-fadeIn">
                             <div class="grid grid-cols-2 gap-6">
                                 <div class="bg-slate-900/50 p-6 rounded-xl border border-slate-800">
                                     <h4 class="text-slate-500 text-xs uppercase tracking-widest mb-4">Profile Information</h4>
                                     <div class="space-y-4">
                                         <div>
                                             <label class="block text-slate-500 text-xs mb-1">User ID</label>
                                             <p id="infoUserId" class="text-white font-mono">#0</p>
                                         </div>
                                          <div>
                                             <label class="block text-slate-500 text-xs mb-1">Joined Date</label>
                                             <p id="infoJoined" class="text-white">Jan 01, 2024</p>
                                         </div>
                                     </div>
                                 </div>
                                 <div class="bg-slate-900/50 p-6 rounded-xl border border-slate-800">
                                     <h4 class="text-slate-500 text-xs uppercase tracking-widest mb-4">Quick Balance Adjustment</h4>
                                      <input type="number" id="quickAmount" placeholder="Amount" class="w-full bg-slate-950 text-white p-3 rounded border border-slate-700 mb-3 focus:border-cyan-500 outline-none">
                                      <div class="grid grid-cols-2 gap-2">
                                          <button onclick="adjustBalance('credit')" class="bg-green-600/20 hover:bg-green-600/30 text-green-400 border border-green-600/30 py-2 rounded font-bold transition-colors">Credit (+)</button>
                                          <button onclick="adjustBalance('debit')" class="bg-red-600/20 hover:bg-red-600/30 text-red-400 border border-red-600/30 py-2 rounded font-bold transition-colors">Debit (-)</button>
                                      </div>
                                 </div>
                             </div>
                        </div>
                        
                        <!-- TRANSACTIONS TAB -->
                        <div id="panel-transactions" class="hidden space-y-4 animate-fadeIn">
                            <h4 class="text-slate-500 text-xs uppercase tracking-widest mb-2">Recent Activity</h4>
                            <div id="transactionList" class="space-y-2">
                                <!-- Loaded via JS -->
                            </div>
                        </div>
                        
                        <!-- SECURITY TAB -->
                        <div id="panel-security" class="hidden space-y-8 animate-fadeIn">
                             <div class="bg-slate-900/50 p-6 rounded-xl border border-slate-800">
                                 <h4 class="text-slate-500 text-xs uppercase tracking-widest mb-6">Biometric Enrollment Status</h4>
                                 <div class="flex gap-8">
                                     <div id="statusFace" class="flex items-center gap-3 opacity-50">
                                         <div class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center">
                                             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                         </div>
                                         <span class="font-bold text-slate-400">Face ID</span>
                                     </div>
                                      <div id="statusFinger" class="flex items-center gap-3 opacity-50">
                                         <div class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center">
                                             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.2-2.85.577-4.147"></path></svg>
                                         </div>
                                         <span class="font-bold text-slate-400">Fingerprint</span>
                                     </div>
                                 </div>
                             </div>

                             <div class="bg-red-900/10 p-6 rounded-xl border border-red-900/30">
                                 <h4 class="text-red-400 text-xs uppercase tracking-widest mb-4">Danger Zone</h4>
                                 <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                     <button onclick="performUserAction('lock')" id="btnLock" class="p-3 bg-slate-800 border border-slate-700 hover:bg-slate-700 text-white rounded font-bold transition-colors">
                                         Lock Account
                                     </button>
                                     <button onclick="performUserAction('reset_pin')" class="p-3 bg-slate-800 border border-slate-700 hover:bg-slate-700 text-white rounded font-bold transition-colors">
                                         Reset PIN to 1234
                                     </button>
                                     <button onclick="deleteActiveUser()" class="p-3 bg-red-600 hover:bg-red-500 text-white border border-red-500 rounded font-bold transition-colors">
                                         Delete Account
                                     </button>
                                 </div>
                             </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const CURRENCY = '<?= defined("CURRENCY") ? CURRENCY : "GHS" ?>';
        let auditLogInterval;

        // PENDING APPROVALS LOGIC
        function showPendingModal() {
            document.getElementById('pendingModal').classList.remove('hidden');
            loadPendingUsers();
        }

        function closePendingModal() {
            document.getElementById('pendingModal').classList.add('hidden');
        }

        async function loadPendingUsers() {
            const container = document.getElementById('pendingContent');
            container.innerHTML = '<div class="text-center text-slate-500">Loading...</div>';
            
            try {
                const res = await fetch('../api/get_pending_users.php');
                const users = await res.json();
                
                document.getElementById('pendingCount').textContent = users.length;
                
                if (users.length === 0) {
                    container.innerHTML = '<div class="text-center text-slate-500 py-8">No pending registrations.</div>';
                    return;
                }
                
                container.innerHTML = users.map(user => `
                    <div class="bg-slate-800 p-4 rounded-lg border border-slate-700 flex justify-between items-center">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-slate-700 rounded-full flex items-center justify-center overflow-hidden">
                                ${user.face_image 
                                    ? `<img src="data:image/jpeg;base64,${user.face_image}" class="w-full h-full object-cover">`
                                    : `<span class="text-slate-500 text-xs">No Photo</span>`
                                }
                            </div>
                            <div>
                                <h4 class="text-white font-bold">${user.full_name}</h4>
                                <p class="text-cyan-400 text-xs font-mono">${user.account_number}</p>
                                <p class="text-slate-500 text-xs">Registered: ${new Date(user.created_at).toLocaleString()}</p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                             <button onclick="approveUser(${user.user_id})" class="px-3 py-1 bg-green-600 hover:bg-green-500 text-white text-xs rounded font-bold">Approve</button>
                             <button onclick="rejectUser(${user.user_id})" class="px-3 py-1 bg-red-600 hover:bg-red-500 text-white text-xs rounded font-bold">Reject</button>
                        </div>
                    </div>
                `).join('');
                
            } catch (error) {
                container.innerHTML = '<div class="text-red-400 text-center">Failed to load users</div>';
            }
        }

        async function approveUser(userId) {
            if(!confirm('Approve this user?')) return;
            try {
                await fetch('../api/approve_user.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ user_id: userId, action: 'approve' })
                });
                showNotification('User Approved', 'success');
                loadPendingUsers();
            } catch(e) { showNotification('Error approving user', 'error'); }
        }

        async function rejectUser(userId) {
            if(!confirm('Reject (Delete) this user?')) return;
            try {
                await fetch('../api/approve_user.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ user_id: userId, action: 'reject' })
                });
                showNotification('User Rejected', 'success');
                loadPendingUsers();
            } catch(e) { showNotification('Error rejecting user', 'error'); }
        }

        // USER MANAGEMENT LOGIC
        let currentBalanceUser = null;
        let allActiveUsers = [];

        function openBalanceSearch() {
            document.getElementById('balanceModal').classList.remove('hidden');
            loadActiveUsers();
            
            // Reset UI
            document.getElementById('userEmptyState').classList.remove('hidden');
            document.getElementById('userDetailsContent').classList.add('hidden');
        }

        function closeBalanceModal() {
            document.getElementById('balanceModal').classList.add('hidden');
        }
        
        async function loadActiveUsers() {
            const listContainer = document.getElementById('activeUsersList');
            listContainer.innerHTML = '<div class="text-center text-slate-500 text-sm py-4">Loading directory...</div>';
            try {
                const res = await fetch('../api/get_active_users.php');
                const data = await res.json();
                
                if (data.success) {
                    allActiveUsers = data.users;
                    renderUserList(allActiveUsers);
                } else {
                    listContainer.innerHTML = '<p class="text-red-400 text-sm text-center">Failed to load directory</p>';
                }
            } catch (e) {
                listContainer.innerHTML = '<p class="text-red-400 text-sm text-center">Connection error</p>';
            }
        }

        function renderUserList(users) {
            const listContainer = document.getElementById('activeUsersList');
            if (users.length === 0) {
                listContainer.innerHTML = '<p class="text-slate-500 text-sm text-center py-4">No users found.</p>';
                return;
            }
            
            listContainer.innerHTML = users.map(user => `
                <div onclick="selectUserForManagement('${user.user_id}')" class="p-3 bg-slate-800/50 hover:bg-slate-800 rounded-lg cursor-pointer border border-transparent hover:border-cyan-500/50 transition-all group mb-1 flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-white group-hover:text-cyan-400 transition-colors text-sm">${user.full_name}</span>
                            ${user.status === 'locked' ? '<span class="w-2 h-2 rounded-full bg-red-500"></span>' : '<span class="w-2 h-2 rounded-full bg-green-500"></span>'}
                        </div>
                        <span class="text-xs font-mono text-slate-500">${user.account_number}</span>
                    </div>
                </div>
            `).join('');
        }

        function filterUserList() {
            const query = document.getElementById('userListFilter').value.toLowerCase();
            const filtered = allActiveUsers.filter(user => 
                user.full_name.toLowerCase().includes(query) || 
                user.account_number.includes(query)
            );
            renderUserList(filtered);
        }

        async function selectUserForManagement(userId) {
            // Show loading state implies checking details
            // For now, load from local list first
            const basicUser = allActiveUsers.find(u => u.user_id == userId);
            if (!basicUser) return;
            
            // Immediate Context Set to prevent race conditions or API failure blocking actions
            currentBalanceUser = basicUser;

            // UI Transition
            document.getElementById('userEmptyState').classList.add('hidden');
            document.getElementById('userDetailsContent').classList.remove('hidden');
            document.getElementById('userDetailsContent').classList.add('flex');
            
            // Populate Basic Header Info
            document.getElementById('detailName').textContent = basicUser.full_name;
            document.getElementById('detailAccount').textContent = basicUser.account_number;
            document.getElementById('detailBalance').textContent = parseFloat(basicUser.current_balance).toFixed(2);
            
            // Status Badge
            const badge = document.getElementById('detailStatusBadge');
            if (basicUser.status === 'active') {
                badge.innerHTML = '<span class="px-2 py-1 rounded text-xs font-bold bg-green-500/20 text-green-400 border border-green-500/30">ACTIVE</span>';
                document.getElementById('btnLock').textContent = 'Lock Account';
                document.getElementById('btnLock').onclick = () => performUserAction('lock');
            } else {
                badge.innerHTML = '<span class="px-2 py-1 rounded text-xs font-bold bg-red-500/20 text-red-400 border border-red-500/30">LOCKED</span>';
                document.getElementById('btnLock').textContent = 'Unlock Account';
                document.getElementById('btnLock').onclick = () => performUserAction('unlock');
            }

            // Fetch Deep Details
            try {
                // Add timestamp to prevent caching of biometric status
                const res = await fetch(`../api/get_user_details.php?user_id=${userId}&_t=${Date.now()}`);
                const data = await res.json();
                
                if (data.success) {
                    currentBalanceUser = data.user; // Update global with full details
                    
                    // Update Image
                    const imgEl = document.getElementById('detailImage');
                    const phEl = document.getElementById('detailPlaceholder');
                    
                    if (data.user.face_image) {
                        imgEl.src = 'data:image/jpeg;base64,' + data.user.face_image;
                        imgEl.classList.remove('hidden');
                        phEl.classList.add('hidden');
                        // Add click-to-zoom (optional but nice)
                        imgEl.parentElement.onclick = () => {
                             // Simple zoom trick or modal, for now just log
                             console.log("View image");
                        };
                    } else {
                        imgEl.classList.add('hidden');
                        phEl.classList.remove('hidden');
                    }

                    // Update Overview Info
                    document.getElementById('detailName').textContent = data.user.full_name;
                    document.getElementById('detailAccount').textContent = data.user.account_number;
                    document.getElementById('detailBalance').textContent = parseFloat(data.user.current_balance).toFixed(2);
                    document.getElementById('infoUserId').textContent = '#' + data.user.user_id;
                    document.getElementById('infoJoined').textContent = new Date(data.user.created_at).toLocaleDateString();
                    
                    // Update Transactions List
                    const txContainer = document.getElementById('transactionList');
                    if (data.transactions.length > 0) {
                        txContainer.innerHTML = data.transactions.map(tx => `
                            <div class="flex justify-between items-center p-3 bg-slate-900/50 rounded border border-slate-800">
                                <div>
                                    <p class="text-white text-sm font-medium">${tx.trans_type}</p>
                                    <p class="text-slate-500 text-xs">${new Date(tx.timestamp).toLocaleString()}</p>
                                </div>
                                <p class="${tx.trans_type.includes('Deposit') || tx.trans_type.includes('Credit') ? 'text-green-400' : 'text-slate-200'} font-mono text-sm">
                                    ${CURRENCY} ${tx.amount}
                                </p>
                            </div>
                        `).join('');
                    } else {
                        txContainer.innerHTML = '<p class="text-slate-500 text-xs italic">No recent transactions</p>';
                    }

                    // Update Security Icons
                    // Update Security Icons - Robust Status Check
                    const faceEl = document.getElementById('statusFace');
                    const fingerEl = document.getElementById('statusFinger');
                    const hasFace = parseInt(data.user.has_face) || 0;
                    const hasFinger = parseInt(data.user.has_finger) || 0;
                    
                    if (hasFace > 0) {
                        faceEl.style.opacity = '1';
                        faceEl.classList.remove('opacity-50'); 
                        faceEl.classList.add('text-green-400');
                        faceEl.querySelector('span').classList.remove('text-slate-400');
                        faceEl.querySelector('span').classList.add('text-green-400');
                    } else {
                        faceEl.style.opacity = '0.5';
                        faceEl.classList.add('opacity-50');
                        faceEl.classList.remove('text-green-400');
                        faceEl.querySelector('span').classList.add('text-slate-400');
                        faceEl.querySelector('span').classList.remove('text-green-400');
                    }
                    
                    if (hasFinger > 0) {
                        fingerEl.style.setProperty('opacity', '1', 'important');
                        fingerEl.classList.remove('opacity-50');
                        fingerEl.classList.add('text-green-400');
                        fingerEl.querySelector('span').classList.remove('text-slate-400');
                        fingerEl.querySelector('span').classList.add('text-green-400');
                        // Remove enroll button if exists
                        const enrollBtn = document.getElementById('btnEnrollFinger');
                        if (enrollBtn) enrollBtn.remove();
                    } else {
                        fingerEl.style.setProperty('opacity', '0.5', 'important');
                        fingerEl.classList.add('opacity-50');
                        fingerEl.classList.remove('text-green-400');
                        fingerEl.querySelector('span').classList.add('text-slate-400');
                        fingerEl.querySelector('span').classList.remove('text-green-400');
                        
                        // Add Enroll Button
                        if (!document.getElementById('btnEnrollFinger') && currentBalanceUser) {
                            const btn = document.createElement('button');
                            btn.id = 'btnEnrollFinger';
                            btn.className = 'ml-4 px-3 py-1 bg-blue-600 hover:bg-blue-500 text-white text-xs rounded font-bold transition-colors';
                            btn.textContent = '+ Enroll';
                            btn.onclick = () => enrollUserFingerprint(currentBalanceUser.user_id, currentBalanceUser.full_name);
                            fingerEl.parentElement.appendChild(btn);
                        }
                    }

                }
            } catch (e) {
                console.error("Failed to fetch details", e);
            }

            // Enable Actions - (No legacy controls to specificially enable)
            // document.getElementById('balanceControls').classList.remove('opacity-50', 'pointer-events-none');
            // Default to Overview tab
            switchTab('overview');
        }
        
        async function enrollUserFingerprint(userId, sentimentName) {
            if (!confirm(`Register fingerprint for ${sentimentName}?\n\nPlease place the user's finger on the scanner connected to THIS computer.`)) return;
            
            try {
                // 1. Get Challenge
                const res = await fetch('../api/get_webauthn_challenge.php');
                const data = await res.json();
                if (!data.success) throw new Error("Could not get challenge");

                // 2. WebAuthn Register
                // We use a random ID for the handle to avoid leaking the DB ID, but associate it on backend
                const userHandleVal = new Uint8Array(16);
                window.crypto.getRandomValues(userHandleVal);
                const userHandle = WebAuthn.bufferToBase64url(userHandleVal.buffer);
                
                const credential = await WebAuthn.register({
                    id: userHandle, 
                    name: sentimentName,
                    displayName: sentimentName
                }, data.challenge);
                
                // 3. Send to Admin API
                const saveRes = await fetch('../api/register_fingerprint.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        target_user_id: userId,
                        fingerprint_data: JSON.stringify(credential)
                    })
                });
                
                const saveResult = await saveRes.json();
                if (saveResult.success) {
                    showNotification('Fingerprint enrolled successfully!', 'success');
                    selectUserForManagement(userId); // Refresh UI
                } else {
                    throw new Error(saveResult.error);
                }
                
            } catch (e) {
                console.error(e);
                showNotification('Enrollment Failed: ' + e.message, 'error');
            }
        }

        function switchTab(tabName) {
            // Hide all panels
            ['overview', 'transactions', 'security'].forEach(t => {
                document.getElementById(`panel-${t}`).classList.add('hidden');
                document.getElementById(`tab-${t}`).classList.remove('text-cyan-400', 'border-cyan-400');
                document.getElementById(`tab-${t}`).classList.add('text-slate-400', 'border-transparent');
            });

            // Show target
            document.getElementById(`panel-${tabName}`).classList.remove('hidden');
            document.getElementById(`tab-${tabName}`).classList.add('text-cyan-400', 'border-cyan-400');
            document.getElementById(`tab-${tabName}`).classList.remove('text-slate-400', 'border-transparent');
        }
        
        function resetBalanceUI() {
           // Not really used in new design, but kept for compatibility logic
        }

        async function adjustBalance(type) {
             const inputVal = parseFloat(document.getElementById('quickAmount').value);

             if (!inputVal || inputVal < 0) {
                 showNotification('Invalid amount', 'error');
                 return;
             }
             if (!currentBalanceUser) {
                 showNotification("Please select a user first.", 'error');
                 return;
             }
             
             try {
                  const res = await fetch('../api/update_balance.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ 
                        user_id: currentBalanceUser.user_id, 
                        type: type, 
                        amount: inputVal 
                    })
                 });
                 const result = await res.json();
                  if (result.success) {
                      const action = type === 'credit' ? 'credited' : 'debited';
                      const newBalance = parseFloat(result.new_balance).toFixed(2);
                      showNotification(`<?= CURRENCY ?> ${inputVal.toFixed(2)} ${action}. New balance: <?= CURRENCY ?> ${newBalance}`, 'success');
                      document.getElementById('quickAmount').value = '';
                      // Refresh details immediately (without calling refreshCashLevels to avoid duplicate notifications)
                      selectUserForManagement(currentBalanceUser.user_id);
                  } else {
                      showNotification('Update failed: ' + result.error, 'error');
                  }
             } catch(e) { 
                 console.error('Balance update error:', e);
                 showNotification('Update Error: ' + e.message, 'error'); 
             }
        }

        async function performUserAction(action) {
            if (!currentBalanceUser) return;
            
            let confirmMsg = "";
            if (action === 'lock') confirmMsg = "Freeze this account? User will not be able to login.";
            if (action === 'unlock') confirmMsg = "Unfreeze this account? User access will be restored.";
            if (action === 'reset_pin') confirmMsg = "Reset PIN to default '1234'?";
            
            if (!confirm(confirmMsg)) return;

            try {
                const res = await fetch('../api/user_actions.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ user_id: currentBalanceUser.user_id, action: action })
                });
                const result = await res.json();
                
                if (result.success) {
                    showNotification(result.message, 'success');
                    // Refresh
                    selectUserForManagement(currentBalanceUser.user_id);
                } else {
                    showNotification('Action failed: ' + result.error, 'error');
                }
            } catch(e) { showNotification('Error performing action', 'error'); }
        }

        async function deleteActiveUser() {
            if (!currentBalanceUser) return;
            
            const confirmMsg = "⚠️ WARNING ⚠️\n\nAre you sure you want to PERMANENTLY DELETE current user:\n" + 
                             currentBalanceUser.full_name + " (" + currentBalanceUser.account_number + ")?\n\n" +
                             "This action cannot be undone. All data will be lost.";
                             
            if (!confirm(confirmMsg)) return;
            if (!confirm("Are you absolutely sure?")) return;
            
            try {
                const res = await fetch('../api/delete_user.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ user_id: currentBalanceUser.user_id })
                });
                
                const result = await res.json();
                
                if (result.success) {
                    showNotification('User Account Deleted', 'success');
                    // Refresh List instead of reload
                    loadActiveUsers();
                    document.getElementById('userEmptyState').classList.remove('hidden');
                    document.getElementById('userDetailsContent').classList.add('hidden');
                } else {
                    showNotification('Delete failed: ' + result.error, 'error');
                }
            } catch(e) { showNotification('Delete Error', 'error'); }
        }

        function enrollCustomer() {
            const btn = event.target.closest('button');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="animate-pulse">Loading...</span>';
            btn.disabled = true;
            
            setTimeout(() => {
                window.location.href = 'enroll-customer.php';
            }, 500);
        }
        
        function enrollAdmin() {
            const btn = event.target.closest('button');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="animate-pulse">Loading...</span>';
            btn.disabled = true;
            
            setTimeout(() => {
                window.location.href = 'enroll-admin.php';
            }, 500);
        }
        
        function viewAuditLogs() {
            document.getElementById('auditModal').classList.remove('hidden');
            loadAuditLogs(false); // Initial load (show spinner)
            
            if (auditLogInterval) clearInterval(auditLogInterval);
            auditLogInterval = setInterval(() => loadAuditLogs(true), 5000); // Silent refresh
        }
        
        function closeAuditModal() {
            document.getElementById('auditModal').classList.add('hidden');
            if (auditLogInterval) {
                clearInterval(auditLogInterval);
            }
        }
        
        
        let allAuditLogs = [];
        let currentSelectedLog = null;

        async function loadAuditLogs() {
            try {
                const response = await fetch('../api/get_auth_logs.php');
                const logs = await response.json();
                
                allAuditLogs = logs;
                renderAuditLogList(logs);
            } catch (error) {
                console.error(error);
                document.getElementById('auditLogList').innerHTML = '<p class="text-red-400 text-sm text-center py-4">Failed to load logs</p>';
            }
        }

        function renderAuditLogList(logs) {
            const listContainer = document.getElementById('auditLogList');
            if (logs.length === 0) {
                listContainer.innerHTML = '<p class="text-slate-500 text-sm text-center py-4">No logs found.</p>';
                return;
            }
            
            // Group by device (canvas fingerprint)
            const grouped = {};
            logs.forEach(log => {
                const deviceKey = log.canvas_fingerprint || 'unknown_device';
                if (!grouped[deviceKey]) {
                    grouped[deviceKey] = [];
                }
                grouped[deviceKey].push(log);
            });
            
            let html = '';
            Object.keys(grouped).forEach(deviceKey => {
                const deviceLogs = grouped[deviceKey];
                const firstLog = deviceLogs[0];
                const deviceLabel = deviceKey === 'unknown_device' ? 'Unknown Device' : 'Device: ' + deviceKey.substring(0, 12) + '...';
                
                // Device group header
                html += `<div class="mb-3">
                    <div class="text-xs text-slate-500 font-semibold uppercase tracking-wide px-2 py-1 mb-1 flex items-center gap-2">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        ${deviceLabel}
                        <span class="text-cyan-400">(${deviceLogs.length})</span>
                    </div>`;
                
                // Logs for this device
                deviceLogs.forEach(log => {
                    const date = new Date(log.timestamp);
                    const dateStr = date.toLocaleDateString();
                    const timeStr = date.toLocaleTimeString();
                    
                    html += `
                    <div onclick="selectLogForDetail('${log.id}')" class="p-3 bg-slate-800/50 hover:bg-slate-800 rounded-lg cursor-pointer border border-transparent hover:border-cyan-500/50 transition-all group mb-1">
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full ${log.status === 'success' ? 'bg-green-500' : 'bg-red-500'}"></div>
                                <div>
                                    <p class="text-white text-sm font-medium group-hover:text-cyan-400 transition-colors">${log.user_name || 'User #' + log.user_id}</p>
                                    <p class="text-xs text-slate-500">${log.auth_type}</p>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-1 ml-4">
                            <span class="px-1.5 py-0.5 bg-slate-900/50 rounded text-xs text-slate-400">${timeStr}</span>
                            ${log.location && log.location !== 'Unknown' ? `<span class="px-1.5 py-0.5 bg-blue-900/30 rounded text-xs text-blue-300 flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>${log.location.split(',')[0]}</span>` : ''}
                        </div>
                    </div>`;
                });
                
                html += '</div>';
            });
            
            listContainer.innerHTML = html;
        }

        function filterAuditLogs() {
            const query = document.getElementById('auditLogFilter').value.toLowerCase();
            const filtered = allAuditLogs.filter(log => 
                (log.user_name && log.user_name.toLowerCase().includes(query)) ||
                log.auth_type.toLowerCase().includes(query) ||
                (log.ip && log.ip.includes(query)) ||
                (log.details && log.details.toLowerCase().includes(query))
            );
            renderAuditLogList(filtered);
        }

        function selectLogForDetail(logId) {
            const log = allAuditLogs.find(l => l.id == logId);
            if (!log) return;
            
            currentSelectedLog = log;
            
            // Show detail panel, hide empty state
            document.getElementById('auditEmptyState').classList.add('hidden');
            document.getElementById('auditDetailContent').classList.remove('hidden');
            document.getElementById('auditDetailContent').classList.add('flex');
            
            // Populate header
            document.getElementById('detailLogUser').textContent = log.user_name || 'User #' + log.user_id;
            document.getElementById('detailLogType').textContent = log.auth_type;
            document.getElementById('detailLogTimestamp').textContent = new Date(log.timestamp).toLocaleString();
            
            // Location display
            const locationEl = document.getElementById('detailLogLocation');
            if (log.location && log.location !== 'Unknown') {
                locationEl.innerHTML = `
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    <span>${log.location}</span>
                `;
            } else {
                locationEl.innerHTML = '';
            }
            
            // Status badge and icon
            const statusIcon = document.getElementById('detailStatusIcon');
            const statusBadge = document.getElementById('detailLogStatus');
            if (log.status === 'success') {
                statusIcon.className = 'w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center border-2 border-green-500';
                statusIcon.innerHTML = '<svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                statusBadge.innerHTML = '<span class="px-2 py-1 rounded text-xs font-bold bg-green-500/20 text-green-400 border border-green-500/30">SUCCESS</span>';
            } else {
                statusIcon.className = 'w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center border-2 border-red-500';
                statusIcon.innerHTML = '<svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
                statusBadge.innerHTML = '<span class="px-2 py-1 rounded text-xs font-bold bg-red-500/20 text-red-400 border border-red-500/30">FAILED</span>';
            }
            
            
            // Forensic data
            document.getElementById('infoIP').textContent = log.ip || 'N/A';
            document.getElementById('infoForwarded').textContent = log.x_forwarded_for || 'None detected';
            document.getElementById('infoUserAgent').textContent = log.user_agent || 'N/A';
            document.getElementById('infoLanguage').textContent = log.language || 'N/A';
            document.getElementById('infoRequestURI').textContent = log.request_uri || 'N/A';
            
            // Referer (conditional)
            if (log.referer) {
                document.getElementById('infoRefererContainer').style.display = 'block';
                document.getElementById('infoReferer').textContent = log.referer;
            } else {
                document.getElementById('infoRefererContainer').style.display = 'none';
            }
            
            // Device Fingerprint data
            document.getElementById('infoCanvasFingerprint').textContent = log.canvas_fingerprint || 'Not collected';
            document.getElementById('infoScreenResolution').textContent = log.screen_resolution || 'N/A';
            document.getElementById('infoTimezone').textContent = log.timezone || 'N/A';
            document.getElementById('infoPlatform').textContent = log.platform || 'N/A';
            document.getElementById('infoHardwareConcurrency').textContent = log.hardware_concurrency ? log.hardware_concurrency + ' cores' : 'N/A';
            document.getElementById('infoDeviceMemory').textContent = log.device_memory ? log.device_memory + ' GB' : 'N/A';
            document.getElementById('infoColorDepth').textContent = log.color_depth ? log.color_depth + '-bit' : 'N/A';
            document.getElementById('infoPixelRatio').textContent = log.pixel_ratio ? log.pixel_ratio + 'x' : 'N/A';
            document.getElementById('infoTouchSupport').textContent = log.touch_support ? 'Yes' : 'No';
            document.getElementById('infoDoNotTrack').textContent = log.do_not_track || 'Unspecified';
            document.getElementById('infoWebGLVendor').textContent = log.webgl_vendor || 'Not collected';
            
            // Event details
            document.getElementById('infoLogID').textContent = '#' + log.id;
            document.getElementById('infoUserID').textContent = '#' + log.user_id;
            document.getElementById('infoUserType').textContent = log.user_type || 'N/A';
            document.getElementById('infoDetails').textContent = log.details || 'No additional details';
        }
        
        async function deleteCurrentLog() {
            if (!currentSelectedLog) return;
            
            if (!confirm(`Delete this log entry?\n\nUser: ${currentSelectedLog.user_name}\nTime: ${new Date(currentSelectedLog.timestamp).toLocaleString()}\n\nThis action cannot be undone.`)) {
                return;
            }
            
            try {
                const response = await fetch('../api/delete_audit_log.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({log_id: currentSelectedLog.id})
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Log deleted successfully', 'success');
                    
                    // Reload logs
                    await loadAuditLogs();
                    
                    // Hide detail panel
                    document.getElementById('auditEmptyState').classList.remove('hidden');
                    document.getElementById('auditDetailContent').classList.add('hidden');
                    currentSelectedLog = null;
                } else {
                    showNotification('Failed to delete log: ' + result.error, 'error');
                }
            } catch (error) {
                showNotification('Error deleting log', 'error');
                console.error(error);
            }
        }
        
        function refreshCashLevels() {
            // event might not exist if called programmatically
            let btn = null;
            if (typeof event !== 'undefined' && event && event.target) {
                btn = event.target.closest('button');
                if(btn) btn.classList.add('animate-spin');
            }
            
            setTimeout(() => {
                const progressBars = document.querySelectorAll('.progress-bar');
                progressBars.forEach(bar => {
                    const currentWidth = parseInt(bar.style.getPropertyValue('--progress'));
                    const variation = Math.random() * 10 - 5; 
                    const newWidth = Math.max(10, Math.min(100, currentWidth + variation));
                    
                    bar.style.setProperty('--progress', newWidth + '%');
                    bar.style.width = newWidth + '%';
                    
                    if (newWidth < 40) bar.className = 'h-full rounded-full bg-red-500 progress-bar';
                    else if (newWidth < 70) bar.className = 'h-full rounded-full bg-yellow-500 progress-bar';
                    else bar.className = 'h-full rounded-full bg-green-500 progress-bar';
                });
                
                if(btn) btn.classList.remove('animate-spin');
            }, 1000);
        }
        
        function showNotification(message, type) {
            // Get existing notifications to calculate offset
            const existingNotifications = document.querySelectorAll('.notification-toast');
            let topOffset = 16; // Start at top-4 (1rem = 16px)
            
            existingNotifications.forEach(notif => {
                topOffset += notif.offsetHeight + 8; // Add height + gap
            });
            
            const notification = document.createElement('div');
            notification.className = `notification-toast fixed right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 ${
                type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'
            }`;
            notification.style.top = topOffset + 'px';
            notification.textContent = message;
            document.body.appendChild(notification);
            
            // Slide in from right
            setTimeout(() => {
                notification.style.opacity = '1';
                notification.style.transform = 'translateX(0)';
            }, 10);
            
            // Remove after 3 seconds
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    notification.remove();
                    // Reposition remaining notifications
                    repositionNotifications();
                }, 300);
            }, 3000);
        }
        
        function repositionNotifications() {
            const notifications = document.querySelectorAll('.notification-toast');
            let topOffset = 16;
            notifications.forEach(notif => {
                notif.style.top = topOffset + 'px';
                topOffset += notif.offsetHeight + 8;
            });
        }
        

        
        // System reset functionality
        async function resetSystem() {
            if (confirm('Are you sure you want to wipe all enrolled users and transaction logs? This cannot be undone.')) {
                try {
                    const response = await fetch('../api/reset-system.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'}
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        showNotification('System reset successfully. All data cleared.', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        showNotification('Reset failed: ' + result.message, 'error');
                    }
                } catch (error) {
                    showNotification('Reset failed: Connection error', 'error');
                }
            }
        }
        

        
        // Auto-refresh dashboard stats every 30 seconds
        setInterval(() => {
            // Simulate real-time stat updates
            const statElements = document.querySelectorAll('h3');
            statElements.forEach(el => {
                if (el.textContent.includes('%')) {
                    // Update accuracy percentages slightly
                    const current = parseFloat(el.textContent);
                    const variation = (Math.random() - 0.5) * 0.2; // ±0.1%
                    const newValue = Math.max(95, Math.min(100, current + variation));
                    el.textContent = newValue.toFixed(1) + '%';
                }
            });
        }, 30000);
        
        // DELETE ALL LOGS
        async function deleteAllLogs() {
            if (!confirm('WARNING: Are you sure you want to delete ALL audit logs? This action cannot be undone and will clear the entire history.')) return;
            
            try {
                const response = await fetch('../api/delete_logs.php', {
                    method: 'POST'
                });
                const result = await response.json();
                
                if (result.success) {
                    showNotification('All logs deleted successfully', 'success');
                    await loadAuditLogs(); // Refresh list
                    // Reset UI
                    document.getElementById('auditEmptyState').classList.remove('hidden');
                    document.getElementById('auditDetailContent').classList.add('hidden');
                } else {
                    showNotification('Error: ' + result.error, 'error');
                }
            } catch (err) {
                showNotification('Connection error while deleting logs', 'error');
            }
        }

        // LOAD USER FILTER
        async function loadUserFilter() {
            try {
                // Reuse existing get_active_users API or create lightweight one
                const res = await fetch('../api/get_active_users.php');
                const data = await res.json();
                
                if (data.success) {
                    const select = document.getElementById('userFilterSelect');
                    // Keep first option (All Users)
                    select.innerHTML = '<option value="">All Users</option>';
                    
                    data.users.forEach(user => {
                        const opt = document.createElement('option');
                        opt.value = user.user_id;
                        opt.textContent = `${user.full_name} (${user.account_number})`;
                        select.appendChild(opt);
                    });
                }
            } catch (e) {
                console.error("Failed to load user filter", e);
            }
        }

        // FILTER LOGS BY USER
        let currentUserIdFilter = '';
        async function filterLogsByUser(userId) {
            currentUserIdFilter = userId;
            await loadAuditLogs();
        }

        // UPDATE loadAuditLogs to use filter
        async function loadAuditLogs(silent = false) {
            const listContainer = document.getElementById('auditLogList');
            if (!silent) {
                listContainer.innerHTML = '<div class="text-center text-slate-500 text-sm py-10">Loading logs...</div>';
            }
            
            try {
                let url = '../api/get_auth_logs.php';
                if (currentUserIdFilter) {
                    url += `?user_id=${currentUserIdFilter}`;
                }
                
                const res = await fetch(url);
                const data = await res.json();
                
                if (data.error) {
                    throw new Error(data.error);
                }
                
                allAuditLogs = data; // Update global store
                
                // Re-apply text filter if exists, otherwise render all
                const currentSearch = document.getElementById('auditLogFilter').value;
                if (currentSearch) {
                    filterAuditLogs();
                } else {
                    renderAuditLogList(allAuditLogs);
                }
                
            } catch (error) {
                console.error(error);
                if (!silent) {
                    listContainer.innerHTML = `<div class="text-red-400 text-center py-10">
                        <p class="font-bold mb-2">Failed to load audit logs</p>
                        <p class="text-sm font-mono bg-slate-950 inline-block p-2 rounded">${error.message || 'Unknown Error'}</p>
                    </div>`;
                }
            }
        }

        // Initial Load
        loadUserFilter();

        // Close modal when clicking outside
        document.getElementById('auditModal').addEventListener('click', (e) => {
            if (e.target === e.currentTarget) {
                closeAuditModal();
            }
        });


        // Initialize
    </script>
    <script src="../public/js/webauthn-helper.js"></script>
    <script src="../public/js/theme-system.js"></script>
</body>
</html>