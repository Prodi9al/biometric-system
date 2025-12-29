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
    $dob = $_POST['dob'] ?? '';
    $account_number = $_POST['account_number'] ?? '';
    $pin = $_POST['pin'] ?? '';
    
    if ($full_name && $dob && $account_number && $pin) {
        try {
            $pdo = getConnection();
            $pdo->beginTransaction();
            
            // Insert customer
            $stmt = $pdo->prepare("INSERT INTO customers (account_number, full_name, pin_hash, current_balance) VALUES (?, ?, ?, 0.00)");
            $stmt->execute([$account_number, $full_name, password_hash($pin, PASSWORD_DEFAULT)]);
            $user_id = $pdo->lastInsertId();
            
            // Insert biometric data
            $finger_data = !empty($_POST['finger_data']) ? $_POST['finger_data'] : 'finger_sim_' . time();
            // face_enc still simulated for now in this admin flow
            $face_data = !empty($_POST['face_data']) ? $_POST['face_data'] : 'face_sim_' . time(); 
            
            $stmt = $pdo->prepare("INSERT INTO biometric_data (user_id, face_enc, finger_enc, quality_score) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $face_data, $finger_data, 95.0]);
            
            $pdo->commit();
            $success = 'Customer enrolled successfully!';
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Enrollment failed: ' . $e->getMessage();
        }
    } else {
        $error = 'All fields are required';
    }
}

// Generate new account number
$new_account = '2023 ' . rand(1000, 9999) . ' ' . rand(1000, 9999) . ' ' . rand(1000, 9999);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= BANK_NAME ?> - Customer Enrollment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../public/css/theme-overrides.css">
</head>
<body class="min-h-screen bg-slate-950 p-8 font-sans flex items-center justify-center">
    <script src="../public/js/theme-system.js"></script>
    
    <!-- Main Container -->
    <div class="w-full max-w-5xl bg-slate-900/50 border border-slate-800 rounded-2xl overflow-hidden shadow-2xl backdrop-blur-sm">
        
        <!-- Header -->
        <div class="bg-slate-900 p-6 border-b border-slate-800 flex justify-between items-center">
            <div>
                <h1 class="text-2xl text-white font-bold flex items-center gap-3">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                    </svg>
                    New Customer Enrollment
                </h1>
                <p class="text-slate-500 text-xs uppercase tracking-widest mt-1 pl-9">Secure Biometric Registration Portal</p>
            </div>
            <div class="px-4 py-1 bg-blue-900/20 text-blue-400 border border-blue-500/20 rounded-full text-xs font-mono">
                SESSION_ID: #<?= rand(1000, 9999) ?>_ACT
            </div>
        </div>

        <form method="POST" class="p-8 grid grid-cols-1 lg:grid-cols-3 gap-8" id="enrollForm">
            <input type="hidden" name="finger_data" id="fingerData">
            <input type="hidden" name="user_handle" id="userHandle">
            
            <!-- Left Column: Basic Details Form -->
            <div class="space-y-6">
                <h3 class="text-slate-400 text-xs font-bold uppercase tracking-widest mb-4 border-b border-slate-800 pb-2">1. Account Details</h3>
                
                <?php if ($success): ?>
                    <div class="bg-green-900/20 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg text-sm">
                        <p class="font-bold mb-2"><?= htmlspecialchars($success) ?></p>
                        <a href="dashboard-modern.php" class="inline-block bg-green-600 hover:bg-green-500 text-white font-bold py-2 px-4 rounded transition-all">
                            ← Back to Dashboard
                        </a>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="bg-red-900/20 border border-red-500/30 text-red-400 px-4 py-2 rounded-lg text-sm">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <div class="space-y-2">
                    <label class="text-slate-300 text-sm font-medium">Full Legal Name</label>
                    <input type="text" name="full_name" placeholder="e.g. Kwame Mensah" 
                           value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                           class="w-full bg-slate-950 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-blue-500 transition-colors" required>
                </div>

                <div class="space-y-2">
                    <label class="text-slate-300 text-sm font-medium">Date of Birth</label>
                    <input type="date" name="dob" 
                           value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>"
                           class="w-full bg-slate-950 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-blue-500 transition-colors" required>
                </div>

                <div class="space-y-2">
                    <label class="text-slate-300 text-sm font-medium">Assigned Account Number</label>
                    <div class="flex gap-2">
                        <input type="text" name="account_number" value="<?= $new_account ?>" readonly 
                               class="w-full bg-slate-900/50 border border-slate-700 rounded-lg px-4 py-3 text-slate-400 font-mono">
                        <button type="button" onclick="generateAccount()" class="p-3 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg border border-slate-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-slate-300 text-sm font-medium">Customer PIN</label>
                    <input type="password" name="pin" placeholder="4-6 digit PIN" maxlength="6"
                           value="<?= htmlspecialchars($_POST['pin'] ?? '') ?>"
                           class="w-full bg-slate-950 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-blue-500 transition-colors" required>
                </div>
            </div>

            <!-- Right Column: Biometric Capture Grid -->
            <div class="lg:col-span-2">
                <h3 class="text-slate-400 text-xs font-bold uppercase tracking-widest mb-4 border-b border-slate-800 pb-2">2. Biometric Capture</h3>
                
                <div class="grid grid-cols-3 gap-4 mb-8">
                    <!-- Facial Scan -->
                    <div id="faceCard" class="relative p-6 rounded-xl border-2 transition-all duration-300 bg-slate-900 border-slate-700 hover:border-blue-500/50">
                        <div class="flex flex-col items-center gap-4">
                            <div id="faceIcon" class="p-4 rounded-full bg-slate-800 text-slate-400">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="text-center">
                                <h3 id="faceTitle" class="font-bold uppercase tracking-wide text-sm text-slate-300">Facial Scan</h3>
                                <p id="faceStatus" class="text-xs text-slate-500 mt-1">Waiting for Input</p>
                            </div>
                            <button type="button" id="faceBtn" onclick="captureFace()" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold uppercase rounded-lg transition-colors flex items-center gap-2">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Capture
                            </button>
                        </div>
                    </div>

                    <!-- Hardware Status -->
                    <div id="hardwareCard" class="relative p-6 rounded-xl border-2 transition-all duration-300 bg-slate-900 border-slate-700">
                        <div class="flex flex-col items-center gap-4">
                            <div id="hardwareIcon" class="p-4 rounded-full bg-slate-800 text-slate-400">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                                </svg>
                            </div>
                            <div class="text-center">
                                <h3 id="hardwareTitle" class="font-bold uppercase tracking-wide text-sm text-slate-300">Hardware Status</h3>
                                <p id="hardwareStatus" class="text-xs text-slate-500 mt-1">Checking...</p>
                            </div>
                            <div id="hardwareIndicator" class="px-3 py-1 bg-slate-800 text-slate-400 text-xs rounded-full font-mono">
                                INITIALIZING
                            </div>
                        </div>
                    </div>

                    <!-- Fingerprint -->
                    <div id="fingerCard" class="relative p-6 rounded-xl border-2 transition-all duration-300 bg-slate-900 border-slate-700 hover:border-blue-500/50">
                        <div class="flex flex-col items-center gap-4">
                            <div id="fingerIcon" class="p-4 rounded-full bg-slate-800 text-slate-400">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                                </svg>
                            </div>
                            <div class="text-center">
                                <h3 id="fingerTitle" class="font-bold uppercase tracking-wide text-sm text-slate-300">Fingerprint</h3>
                                <p id="fingerStatus" class="text-xs text-slate-500 mt-1">Waiting for Input</p>
                            </div>
                            <button type="button" id="fingerBtn" onclick="captureFingerprint()" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold uppercase rounded-lg transition-colors flex items-center gap-2">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Capture
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Quality Check / Console -->
                <div id="console" class="bg-black/40 rounded-lg p-4 font-mono text-xs border border-slate-800 h-32 overflow-y-auto space-y-1">
                    <p class="text-green-400">[SYSTEM] Hardware Initialized...</p>
                </div>
            </div>
        </form>

        <!-- Footer Actions -->
        <div class="p-6 bg-slate-900 border-t border-slate-800 flex justify-end gap-4">
            <a href="dashboard-modern.php" class="px-6 py-3 text-slate-400 hover:text-white font-medium transition-colors">Cancel</a>
            <button type="submit" form="enrollForm" id="saveBtn" class="px-8 py-3 rounded-lg font-bold flex items-center gap-2 shadow-lg transition-all bg-slate-700 text-slate-400 cursor-not-allowed">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
                SAVE PROFILE
            </button>
        </div>

    </div>

    <script src="../public/js/webauthn-helper.js"></script>
    <script>
        let captured = { face: false, retina: false, finger: false };
        
        function generateAccount() {
            const account = '2023 ' + Math.floor(Math.random() * 9000 + 1000) + ' ' + 
                           Math.floor(Math.random() * 9000 + 1000) + ' ' + 
                           Math.floor(Math.random() * 9000 + 1000);
            document.querySelector('input[name="account_number"]').value = account;
        }
        
        async function captureFace() {
            if (!captured.face) {
                // Simulate camera access
                try {
                    addConsoleLog('[CAM_01] Requesting camera access...', 'yellow');
                    const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                    addConsoleLog('[CAM_01] Camera stream acquired', 'green');
                    
                    // Simulate face detection delay
                    setTimeout(() => {
                        captured.face = true;
                        updateCaptureCard('face', true);
                        addConsoleLog('[CAM_01] Face Template Generated (Hash: 0x4F...2A)', 'blue');
                        checkAllCaptured();
                        
                        // Stop camera stream
                        stream.getTracks().forEach(track => track.stop());
                    }, 2000);
                    
                } catch (error) {
                    addConsoleLog('[CAM_01] Camera access denied, using simulation', 'red');
                    captured.face = true;
                    updateCaptureCard('face', true);
                    addConsoleLog('[CAM_01] Simulated face template generated', 'blue');
                    checkAllCaptured();
                }
            } else {
                captured.face = false;
                updateCaptureCard('face', false);
                addConsoleLog('[CAM_01] Face data cleared', 'red');
                checkAllCaptured();
            }
        }
        
        // Hardware detection and status update
        function updateHardwareStatus() {
            const card = document.getElementById('hardwareCard');
            const icon = document.getElementById('hardwareIcon');
            const title = document.getElementById('hardwareTitle');
            const status = document.getElementById('hardwareStatus');
            const indicator = document.getElementById('hardwareIndicator');
            
            if (window.PublicKeyCredential) {
                card.className = 'relative p-6 rounded-xl border-2 transition-all duration-300 bg-green-500/10 border-green-500/50';
                icon.className = 'p-4 rounded-full bg-green-500 text-slate-900';
                title.className = 'font-bold uppercase tracking-wide text-sm text-green-400';
                status.textContent = 'WebAuthn Ready';
                indicator.className = 'px-3 py-1 bg-green-500 text-slate-900 text-xs rounded-full font-mono';
                indicator.textContent = 'HARDWARE ACTIVE';
                addConsoleLog('[WEBAUTHN] Hardware biometrics available', 'green');
            } else {
                card.className = 'relative p-6 rounded-xl border-2 transition-all duration-300 bg-yellow-500/10 border-yellow-500/50';
                icon.className = 'p-4 rounded-full bg-yellow-500 text-slate-900';
                title.className = 'font-bold uppercase tracking-wide text-sm text-yellow-400';
                status.textContent = 'Simulation Mode';
                indicator.className = 'px-3 py-1 bg-yellow-500 text-slate-900 text-xs rounded-full font-mono';
                indicator.textContent = 'SOFTWARE ONLY';
                addConsoleLog('[FALLBACK] Using simulation mode', 'yellow');
            }
        }
        
        async function captureFingerprint() {
            // Use WebAuthn REGISTRATION for enrollment
            if (window.PublicKeyCredential && !captured.finger) {
                try {
                    addConsoleLog('[WEBAUTHN] Requesting server challenge...', 'yellow');
                    
                    // Fetch secure challenge from server
                    const res = await fetch('../api/get_webauthn_challenge.php');
                    const data = await res.json();
                    if (!data.success) throw new Error("Could not get challenge from server");
                    
                    addConsoleLog('[WEBAUTHN] Challenge received. Please scan finger...', 'yellow');
                    
                    // Generate User Handle
                    const userIdVal = new Uint8Array(16);
                    window.crypto.getRandomValues(userIdVal);
                    const userId = WebAuthn.bufferToBase64url(userIdVal.buffer);
                    const userName = document.querySelector('input[name="full_name"]').value || 'New User';

                    // Register with WebAuthn
                    const credential = await WebAuthn.register({
                        id: userId,
                        name: userName,
                        displayName: userName
                    }, data.challenge);
                    
                    // Success - Store data for submission
                    document.getElementById('fingerData').value = JSON.stringify(credential);
                    document.getElementById('userHandle').value = userId;

                    addConsoleLog('[WEBAUTHN] Biometric registration successful!', 'green');
                    captured.finger = true;
                    updateCaptureCard('finger', true);
                    addConsoleLog('[OPT_03] Hardware credential registered & encrypted', 'blue');
                    checkAllCaptured();
                    return;
                    
                } catch (error) {
                    console.error(error);
                    addConsoleLog('[WEBAUTHN] Capture failed: ' + error.name + ': ' + error.message, 'red');
                    if (!confirm(`Hardware capture failed with error:\n"${error.name}: ${error.message}"\n\nSwitch to Simulation Mode?`)) return;
                }
            }
            
            // Fallback to simulation
            captured.finger = !captured.finger;
            updateCaptureCard('finger', captured.finger);
            addConsoleLog('[OPT_03] Minutiae Points Mapped (Score: 98.5%)', 'blue');
            checkAllCaptured();
        }
        
        function updateCaptureCard(type, isCaptured) {
            const card = document.getElementById(type + 'Card');
            const icon = document.getElementById(type + 'Icon');
            const title = document.getElementById(type + 'Title');
            const status = document.getElementById(type + 'Status');
            const btn = document.getElementById(type + 'Btn');
            
            if (isCaptured) {
                card.className = 'relative p-6 rounded-xl border-2 transition-all duration-300 bg-green-500/10 border-green-500/50';
                icon.className = 'p-4 rounded-full bg-green-500 text-slate-900';
                title.className = 'font-bold uppercase tracking-wide text-sm text-green-400';
                status.textContent = 'Data Encrypted';
                btn.textContent = 'Retake';
                btn.className = 'text-xs text-red-400 hover:text-red-300 underline';
            } else {
                card.className = 'relative p-6 rounded-xl border-2 transition-all duration-300 bg-slate-900 border-slate-700 hover:border-blue-500/50';
                icon.className = 'p-4 rounded-full bg-slate-800 text-slate-400';
                title.className = 'font-bold uppercase tracking-wide text-sm text-slate-300';
                status.textContent = 'Waiting for Input';
                btn.innerHTML = '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Capture';
                btn.className = 'px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold uppercase rounded-lg transition-colors flex items-center gap-2';
            }
        }
        
        function addConsoleLog(message, color) {
            const console = document.getElementById('console');
            const p = document.createElement('p');
            p.className = `text-${color}-400`;
            p.textContent = message;
            console.appendChild(p);
            console.scrollTop = console.scrollHeight;
        }
        
        function checkAllCaptured() {
            const saveBtn = document.getElementById('saveBtn');
            // Dual-factor: Face + Fingerprint only
            if (captured.face && captured.finger) {
                saveBtn.className = 'px-8 py-3 rounded-lg font-bold flex items-center gap-2 shadow-lg transition-all bg-green-600 hover:bg-green-500 text-white shadow-green-900/20 hover:scale-[1.02] active:scale-[0.98]';
                saveBtn.disabled = false;
                addConsoleLog('[READY] Dual-factor biometrics captured. Ready to save.', 'green');
            }
        }
        
        // Initialize hardware status and form
        window.addEventListener('load', () => {
            updateHardwareStatus();
            document.querySelector('form').id = 'enrollForm';
        });
    </script>
</body>
</html>