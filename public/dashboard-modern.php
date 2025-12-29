<?php
require_once '../config/config.php';
require_once '../core/Session.php';

if (!Session::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = Session::get('user_id');
$user_name = Session::get('user_name');
$account_number = Session::get('account_number');

$pdo = getConnection();
$stmt = $pdo->prepare("SELECT current_balance FROM customers WHERE user_id = ?");
$stmt->execute([$user_id]);
$balance = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= BANK_NAME ?> - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .animate-fadeInUp { animation: fadeInUp 0.6s ease-out; }
        .animate-pulse { animation: pulse 2s infinite; }
        .action-btn {
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        .action-btn:hover {
            transform: scale(1.05);
            background: rgba(30, 41, 59, 0.8);
        }
        .action-btn:active {
            transform: scale(0.95);
        }
    </style>
    <link rel="stylesheet" href="css/theme-overrides.css">
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-blue-950 flex items-center justify-center p-4 font-sans">
    <div class="w-full max-w-4xl aspect-[4/3] bg-gradient-to-br from-slate-900 to-slate-950 rounded-2xl shadow-2xl border border-slate-800 overflow-hidden relative flex flex-col">
        <div class="absolute top-[-10%] right-[-10%] w-[500px] h-[500px] bg-blue-500/5 rounded-full blur-[100px] pointer-events-none"></div>
        
        <!-- Header -->
        <div class="p-8 border-b border-slate-800/50 flex justify-between items-center bg-slate-900/50 backdrop-blur-md">
            <div class="animate-fadeInUp">
                <p class="text-slate-400 text-xs uppercase tracking-widest mb-1">Current Session</p>
                <h1 class="text-2xl text-white font-bold tracking-wide">Welcome, <span class="text-cyan-400"><?= htmlspecialchars($user_name) ?></span></h1>
            </div>
            <div class="flex items-center gap-4 animate-fadeInUp" style="animation-delay: 0.2s;">
                <button onclick="toggleTheme()" class="p-2 bg-slate-800 hover:bg-slate-700 rounded-full border border-slate-700 transition-colors" title="Toggle Theme">
                    <svg id="themeIcon" class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </button>
                <div class="px-6 py-2 bg-slate-800 rounded-full border border-slate-700 flex items-center gap-3">
                    <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                    <span class="text-slate-300 text-sm font-mono">SECURE CONNECTION</span>
                </div>
            </div>
        </div>

        <!-- Grid -->
        <div class="flex-1 p-8 grid grid-cols-3 gap-6">
            <button onclick="withdraw(50)" class="action-btn flex flex-col items-center justify-center p-6 bg-slate-800/40 border border-slate-700 rounded-2xl hover:border-cyan-400/50 transition-all group shadow-lg backdrop-blur-sm animate-fadeInUp" style="animation-delay: 0.3s;">
                <div class="p-4 rounded-full bg-slate-900/80 group-hover:bg-cyan-500/10 mb-4 border border-slate-700 group-hover:border-cyan-500/50 transition-colors">
                    <svg class="w-8 h-8 text-cyan-400 group-hover:text-cyan-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <span class="text-slate-200 font-medium tracking-wide uppercase text-sm group-hover:text-cyan-100">Withdraw Cash</span>
            </button>
            
            <button onclick="checkBalance()" class="action-btn flex flex-col items-center justify-center p-6 bg-slate-800/40 border border-slate-700 rounded-2xl hover:border-cyan-400/50 transition-all group shadow-lg backdrop-blur-sm animate-fadeInUp" style="animation-delay: 0.4s;">
                <div class="p-4 rounded-full bg-slate-900/80 group-hover:bg-cyan-500/10 mb-4 border border-slate-700 group-hover:border-cyan-500/50 transition-colors">
                    <svg class="w-8 h-8 text-cyan-400 group-hover:text-cyan-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                </div>
                <span class="text-slate-200 font-medium tracking-wide uppercase text-sm group-hover:text-cyan-100">Check Balance</span>
            </button>
            
            <button onclick="showDeposit()" class="action-btn flex flex-col items-center justify-center p-6 bg-slate-800/40 border border-slate-700 rounded-2xl hover:border-cyan-400/50 transition-all group shadow-lg backdrop-blur-sm animate-fadeInUp" style="animation-delay: 0.5s;">
                <div class="p-4 rounded-full bg-slate-900/80 group-hover:bg-cyan-500/10 mb-4 border border-slate-700 group-hover:border-cyan-500/50 transition-colors">
                    <svg class="w-8 h-8 text-cyan-400 group-hover:text-cyan-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </div>
                <span class="text-slate-200 font-medium tracking-wide uppercase text-sm group-hover:text-cyan-100">Deposit Funds</span>
            </button>
            
            <button onclick="showTransfer()" class="action-btn flex flex-col items-center justify-center p-6 bg-slate-800/40 border border-slate-700 rounded-2xl hover:border-cyan-400/50 transition-all group shadow-lg backdrop-blur-sm animate-fadeInUp" style="animation-delay: 0.6s;">
                <div class="p-4 rounded-full bg-slate-900/80 group-hover:bg-cyan-500/10 mb-4 border border-slate-700 group-hover:border-cyan-500/50 transition-colors">
                    <svg class="w-8 h-8 text-cyan-400 group-hover:text-cyan-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                    </svg>
                </div>
                <span class="text-slate-200 font-medium tracking-wide uppercase text-sm group-hover:text-cyan-100">Transfer</span>
            </button>
            
            <button onclick="showTransactions()" class="action-btn flex flex-col items-center justify-center p-6 bg-slate-800/40 border border-slate-700 rounded-2xl hover:border-cyan-400/50 transition-all group shadow-lg backdrop-blur-sm animate-fadeInUp" style="animation-delay: 0.7s;">
                <div class="p-4 rounded-full bg-slate-900/80 group-hover:bg-cyan-500/10 mb-4 border border-slate-700 group-hover:border-cyan-500/50 transition-colors">
                    <svg class="w-8 h-8 text-cyan-400 group-hover:text-cyan-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <span class="text-slate-200 font-medium tracking-wide uppercase text-sm group-hover:text-cyan-100">History</span>
            </button>
            
            <button onclick="withdraw(50)" class="action-btn flex flex-col items-center justify-center p-6 bg-gradient-to-br from-cyan-900/20 to-blue-900/20 border border-cyan-500/30 rounded-2xl hover:border-cyan-400 transition-all group shadow-lg animate-fadeInUp" style="animation-delay: 0.8s;">
                <span class="text-3xl font-bold text-cyan-400 mb-2"><?= CURRENCY ?>50</span>
                <span class="text-cyan-200/70 font-medium tracking-wide uppercase text-sm">Quick Cash</span>
            </button>
        </div>

        <!-- Footer -->
        <div class="p-8 border-t border-slate-800/50 flex justify-between items-center bg-slate-900/30">
            <p class="text-slate-500 text-xs">Account: <?= htmlspecialchars($account_number) ?> | Balance: <?= CURRENCY ?> <?= number_format($balance, 2) ?></p>
            <button onclick="logout()" class="flex items-center gap-3 px-8 py-3 bg-red-500/10 border border-red-500/30 rounded-full text-red-400 hover:text-red-300 transition-all uppercase text-sm font-bold tracking-wider hover:scale-105 active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                Log Out
            </button>
        </div>
    </div>
    
    <!-- Transaction Modal -->
    <div id="transactionModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-slate-900 border border-slate-800 rounded-xl w-full max-w-md overflow-hidden">
                <div class="p-6 border-b border-slate-800">
                    <h3 id="modalTitle" class="text-white font-bold text-lg">Transaction</h3>
                </div>
                <div class="p-6">
                    <div id="modalContent">
                        <!-- Content will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const userId = <?= $user_id ?>;
        let currentBalance = <?= $balance ?>;
        
        function showModal(title, content) {
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalContent').innerHTML = content;
            document.getElementById('transactionModal').classList.remove('hidden');
        }
        
        function hideModal() {
            document.getElementById('transactionModal').classList.add('hidden');
        }
        
        function checkBalance() {
            showModal('Account Balance', `
                <div class="text-center py-6">
                    <div class="text-4xl font-bold text-cyan-400 mb-2"><?= CURRENCY ?> ${currentBalance.toFixed(2)}</div>
                    <p class="text-slate-400 text-sm mb-6">Available Balance</p>
                    <button onclick="hideModal()" class="px-6 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-lg transition-colors">
                        Close
                    </button>
                </div>
            `);
        }
        
        function withdraw(amount) {
            if (!amount) {
                showModal('Withdraw Cash', `
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <button onclick="processWithdraw(50)" class="p-4 bg-slate-800 hover:bg-slate-700 rounded-lg text-white font-bold transition-colors"><?= CURRENCY ?>50</button>
                            <button onclick="processWithdraw(100)" class="p-4 bg-slate-800 hover:bg-slate-700 rounded-lg text-white font-bold transition-colors"><?= CURRENCY ?>100</button>
                            <button onclick="processWithdraw(200)" class="p-4 bg-slate-800 hover:bg-slate-700 rounded-lg text-white font-bold transition-colors"><?= CURRENCY ?>200</button>
                            <button onclick="processWithdraw(500)" class="p-4 bg-slate-800 hover:bg-slate-700 rounded-lg text-white font-bold transition-colors"><?= CURRENCY ?>500</button>
                        </div>
                        <div class="pt-4 border-t border-slate-700">
                            <input type="number" id="customAmount" placeholder="Enter custom amount" class="w-full p-3 bg-slate-800 border border-slate-600 rounded-lg text-white mb-3">
                            <div class="flex gap-3">
                                <button onclick="hideModal()" class="flex-1 px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors">Cancel</button>
                                <button onclick="processCustomWithdraw()" class="flex-1 px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg transition-colors">Withdraw</button>
                            </div>
                        </div>
                    </div>
                `);
            } else {
                processWithdraw(amount);
            }
        }
        
        async function processWithdraw(amount) {
            if (amount > currentBalance) {
                showNotification('Insufficient funds', 'error');
                return;
            }
            
            try {
                const response = await fetch('../api/transaction.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        type: 'withdrawal',
                        amount: amount,
                        user_id: userId
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    currentBalance -= amount;
                    updateBalanceDisplay();
                    hideModal();
                    showNotification(`Withdrawal successful! ${amount} dispensed.`, 'success');
                } else {
                    showNotification('Transaction failed: ' + result.message, 'error');
                }
            } catch (error) {
                showNotification('Connection error. Please try again.', 'error');
            }
        }
        
        function processCustomWithdraw() {
            const amount = parseFloat(document.getElementById('customAmount').value);
            if (amount && amount > 0) {
                processWithdraw(amount);
            } else {
                showNotification('Please enter a valid amount', 'error');
            }
        }
        
        function showDeposit() {
            showModal('Deposit Funds', `
                <div class="space-y-4">
                    <div class="text-center text-slate-400 text-sm mb-4">
                        Insert cash into the deposit slot
                    </div>
                    <input type="number" id="depositAmount" placeholder="Enter deposit amount" class="w-full p-3 bg-slate-800 border border-slate-600 rounded-lg text-white">
                    <div class="flex gap-3">
                        <button onclick="hideModal()" class="flex-1 px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors">Cancel</button>
                        <button onclick="processDeposit()" class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">Deposit</button>
                    </div>
                </div>
            `);
        }
        
        async function processDeposit() {
            const amount = parseFloat(document.getElementById('depositAmount').value);
            if (!amount || amount <= 0) {
                showNotification('Please enter a valid amount', 'error');
                return;
            }
            
            try {
                const response = await fetch('../api/transaction.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        type: 'deposit',
                        amount: amount,
                        user_id: userId
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    currentBalance += amount;
                    updateBalanceDisplay();
                    hideModal();
                    showNotification(`Deposit successful! ${amount} added to account.`, 'success');
                } else {
                    showNotification('Transaction failed: ' + result.message, 'error');
                }
            } catch (error) {
                showNotification('Connection error. Please try again.', 'error');
            }
        }
        
        function showTransfer() {
            showModal('Transfer Funds', `
                <div class="space-y-4">
                    <input type="text" id="recipientAccount" placeholder="Recipient account number" class="w-full p-3 bg-slate-800 border border-slate-600 rounded-lg text-white">
                    <input type="number" id="transferAmount" placeholder="Transfer amount" class="w-full p-3 bg-slate-800 border border-slate-600 rounded-lg text-white">
                    <div class="flex gap-3">
                        <button onclick="hideModal()" class="flex-1 px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors">Cancel</button>
                        <button onclick="processTransfer()" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">Transfer</button>
                    </div>
                </div>
            `);
        }
        
        async function processTransfer() {
            const recipient = document.getElementById('recipientAccount').value;
            const amount = parseFloat(document.getElementById('transferAmount').value);
            
            if (!recipient || !amount || amount <= 0) {
                showNotification('Please fill all fields with valid data', 'error');
                return;
            }
            
            if (amount > currentBalance) {
                showNotification('Insufficient funds', 'error');
                return;
            }
            
            try {
                const response = await fetch('../api/transaction.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        type: 'transfer',
                        amount: amount,
                        recipient: recipient,
                        user_id: userId
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    currentBalance -= amount;
                    updateBalanceDisplay();
                    hideModal();
                    showNotification(`Transfer successful! ${amount} sent to ${recipient}.`, 'success');
                } else {
                    showNotification('Transfer failed: ' + result.message, 'error');
                }
            } catch (error) {
                showNotification('Connection error. Please try again.', 'error');
            }
        }
        
        async function showTransactions() {
            try {
                const response = await fetch(`../api/get-transactions.php?user_id=${userId}`);
                const transactions = await response.json();
                
                const transactionList = transactions.map(t => `
                    <div class="flex justify-between items-center p-3 bg-slate-800/50 rounded-lg border border-slate-700 mb-2">
                        <div>
                            <p class="text-white text-sm font-medium">${t.trans_type}</p>
                            <p class="text-slate-400 text-xs">${new Date(t.timestamp).toLocaleDateString()}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-white font-bold"><?= CURRENCY ?> ${parseFloat(t.amount).toFixed(2)}</p>
                            <p class="text-xs ${t.trans_type === 'Withdrawal' ? 'text-red-400' : 'text-green-400'}">
                                ${t.trans_type === 'Withdrawal' ? '-' : '+'}
                            </p>
                        </div>
                    </div>
                `).join('');
                
                showModal('Transaction History', `
                    <div class="max-h-64 overflow-y-auto">
                        ${transactionList || '<p class="text-slate-400 text-center py-4">No transactions found</p>'}
                    </div>
                    <div class="pt-4 border-t border-slate-700">
                        <button onclick="hideModal()" class="w-full px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-lg transition-colors">Close</button>
                    </div>
                `);
            } catch (error) {
                showNotification('Unable to load transaction history', 'error');
            }
        }
        
        function updateBalanceDisplay() {
            const balanceElement = document.querySelector('p:contains("Balance:")');
            if (balanceElement) {
                balanceElement.innerHTML = balanceElement.innerHTML.replace(/Balance: [^|]+/, `Balance: <?= CURRENCY ?> ${currentBalance.toFixed(2)}`);
            }
        }
        
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 ${
                type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'
            }`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        function logout() {
            showModal('Confirm Logout', `
                <div class="text-center py-4">
                    <p class="text-slate-300 mb-6">Are you sure you want to end your session?</p>
                    <div class="flex gap-3">
                        <button onclick="hideModal()" class="flex-1 px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors">Cancel</button>
                        <button onclick="window.location.href='logout.php'" class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">Logout</button>
                    </div>
                </div>
            `);
        }
        
        // Close modal when clicking outside
        document.getElementById('transactionModal').addEventListener('click', (e) => {
            if (e.target === e.currentTarget) {
                hideModal();
            }
        });
        

        
        // Enhanced transaction processing with Windows Hello verification
        async function verifyTransactionWithBiometrics(amount, type) {
            if (window.PublicKeyCredential) {
                try {
                    showNotification('Please verify with biometrics to complete transaction', 'info');
                    
                    const challenge = new Uint8Array(32);
                    window.crypto.getRandomValues(challenge);
                    
                    await navigator.credentials.get({
                        publicKey: {
                            challenge: challenge,
                            rpId: window.location.hostname,
                            userVerification: 'required',
                            timeout: 30000,
                        }
                    });
                    
                    return true;
                } catch (error) {
                    showNotification('Biometric verification failed', 'error');
                    return false;
                }
            }
            return true; // Fallback to allow transaction
        }
        
        // Override processWithdraw to include biometric verification
        const originalProcessWithdraw = processWithdraw;
        processWithdraw = async function(amount) {
            if (amount > currentBalance) {
                showNotification('Insufficient funds', 'error');
                return;
            }
            
            // Require biometric verification for amounts over 200
            if (amount > 200) {
                const verified = await verifyTransactionWithBiometrics(amount, 'withdrawal');
                if (!verified) return;
            }
            
            return originalProcessWithdraw(amount);
        };
        
        // Auto-refresh balance every 30 seconds
        setInterval(async () => {
            try {
                const response = await fetch(`../api/get-balance.php?user_id=${userId}`);
                const data = await response.json();
                if (data.balance !== undefined) {
                    currentBalance = parseFloat(data.balance);
                    updateBalanceDisplay();
                }
            } catch (error) {
                console.log('Balance refresh failed');
            }
        }, 30000);
    </script>
    <script src="js/theme-system.js"></script>
</body>
</html>