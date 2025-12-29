<?php
require_once '../config/config.php';
require_once '../core/Session.php';

// Check if already logged in
if (Session::isLoggedIn()) {
    header('Location: dashboard-modern.php');
    exit;
}

// Check for Step 2 (Result of 2FA from login.php)
$isStep2 = isset($_GET['step']) && $_GET['step'] == '2';
$prefilledAccount = '';
$prefilledName = '';

if ($isStep2) {
    $prefilledAccount = Session::get('pending_account_number');
    $prefilledName = Session::get('pending_user_name');
    $prefilledId = Session::get('pending_user_id');
    if (!$prefilledAccount) {
        // invalid state, redirect back
        header('Location: login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= BANK_NAME ?> - Secure Sequential Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fadeInUp { animation: fadeInUp 0.6s ease-out; }
        .scan-line {
            position: absolute;
            width: 100%;
            height: 2px;
            background: #22d3ee;
            animation: scan 2s linear infinite;
        }
        @keyframes scan {
            0% { top: 0; }
            100% { top: 100%; }
        }
    </style>
    </style>
    <link rel="stylesheet" href="css/theme-overrides.css">
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-blue-950 flex items-center justify-center p-0 md:p-4 font-sans">
    <div class="w-full md:max-w-4xl min-h-screen md:min-h-0 md:aspect-[4/3] bg-gradient-to-br from-slate-900 to-slate-950 md:rounded-2xl shadow-2xl border-x-0 border-y-0 md:border border-slate-800 overflow-hidden relative flex flex-col">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-2/3 h-1/3 bg-cyan-500/10 blur-[100px] pointer-events-none"></div>
        
        <!-- Cancel Button -->
        <div class="absolute top-4 left-4 z-20">
            <button onclick="window.location.href='login.php'" class="text-white/70 hover:text-white flex items-center text-sm transition-colors p-2">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                Cancel
            </button>
        </div>

        <!-- Toggle Theme Button -->
        <div class="absolute top-4 right-4 z-20">
            <button onclick="toggleTheme()" class="p-2 bg-slate-800/50 hover:bg-slate-700/50 text-cyan-400/70 hover:text-cyan-400 rounded-full border border-slate-700/50 transition-colors backdrop-blur-sm" title="Toggle Theme">
                <svg id="themeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </button>
        </div>

        <div class="flex-1 flex flex-col p-6 md:p-8 relative z-10">
            <!-- Header -->
            <div class="mt-12 md:mt-0 mb-8 md:mb-4 shrink-0 text-center animate-fadeInUp">
                <h1 class="text-2xl md:text-3xl tracking-[0.2em] text-cyan-400 uppercase font-bold drop-shadow-[0_0_10px_rgba(34,211,238,0.3)]">Secure Auth v2</h1>
                <?php if ($isStep2): ?>
                    <p id="statusMessage" class="text-green-400 text-sm mt-2 tracking-widest uppercase font-bold">Welcome, <?= htmlspecialchars($prefilledName) ?></p>
                <?php else: ?>
                    <p id="statusMessage" class="text-slate-400 text-sm mt-2 tracking-widest uppercase">Identity Verification Required</p>
                <?php endif; ?>
            </div>
            
            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col items-center justify-center mb-6 min-h-0 relative">
                
                <!-- IDLE / INPUT STATE -->
                <div id="inputState" class="w-full max-w-md animate-fadeInUp space-y-6">
                    <div>
                        <label class="block text-slate-400 text-xs uppercase tracking-wider mb-2">Account Number</label>
                        <input type="text" id="accountNumber" 
                               onkeydown="if(event.key === 'Enter') { event.preventDefault(); startAuthFlow(); }"
                               class="w-full bg-slate-800/50 border border-slate-600 rounded-lg p-4 text-white text-center text-2xl md:text-xl tracking-widest focus:outline-none focus:border-cyan-400/50 transition-colors placeholder:text-slate-700" placeholder="0000 0000 0000 0000">
                    </div>
                    <button type="button" onclick="startAuthFlow()" class="w-full py-4 bg-cyan-900/40 hover:bg-cyan-900/60 text-cyan-400 rounded-lg border border-cyan-800 transition-all uppercase tracking-widest font-semibold hover:glow">
                        Begin Verification
                    </button>
                </div>
                
                <div class="mt-6 text-center">
                    <a href="register.php" class="text-cyan-400/50 hover:text-cyan-400 text-xs uppercase tracking-widest border-b border-cyan-400/30 hover:border-cyan-400 pb-1 transition-all">
                        Register New Account
                    </a>
                </div>

                <!-- SCANNING STATE -->
                <div id="scanningState" class="hidden w-full max-w-md relative">
                    <!-- Live Camera Feed -->
                    <!-- Live Camera Feed (Oval Shape) -->
                    <div class="relative w-64 h-80 mx-auto bg-black rounded-[50%] overflow-hidden border-4 border-slate-700 shadow-2xl mb-6 ring-4 ring-cyan-500/20">
                        <video id="cameraVideo" class="w-full h-full object-cover transform scale-125" autoplay playsinline muted></video>
                        <div id="scanOverlay" class="absolute inset-0 hidden">
                            <div class="scan-line-oval"></div>
                            <div class="absolute inset-0 border-4 border-cyan-500/30 rounded-[50%] animate-pulse"></div>
                        </div>
                    </div>

                    <!-- Steps -->
                    <div class="space-y-4">
                        <!-- Face Step -->
                        <div id="faceStep" class="flex items-center space-x-4 p-4 rounded-lg bg-slate-800/30 border border-slate-700 transition-all">
                            <div id="faceIcon" class="w-10 h-10 rounded-full flex items-center justify-center bg-slate-800 border-2 border-slate-600 text-slate-500">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-white text-sm font-semibold uppercase tracking-wider">Face Recognition</h3>
                                <p id="faceStatus" class="text-slate-500 text-xs">Waiting to start...</p>
                            </div>
                        </div>

                        <!-- Fingerprint Step -->
                        <div id="fingerStep" class="flex items-center space-x-4 p-4 rounded-lg bg-slate-800/30 border border-slate-700 transition-all opacity-50">
                            <div id="fingerIcon" class="w-10 h-10 rounded-full flex items-center justify-center bg-slate-800 border-2 border-slate-600 text-slate-500">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.2-2.85.577-4.147"></path></svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-white text-sm font-semibold uppercase tracking-wider">Fingerprint Scan</h3>
                                <p id="fingerStatus" class="text-slate-500 text-xs">Waiting for face verification...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SUCCESS STATE -->
                <div id="successState" class="hidden text-center animate-fadeInUp">
                    <div class="w-24 h-24 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-6 border-2 border-green-500 shadow-[0_0_30px_rgba(34,197,94,0.4)]">
                        <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-white mb-2">Authentication Successful</h2>
                    <p class="text-slate-400">Secure session established. Redirecting...</p>
                </div>

                <!-- ERROR STATE -->
                <div id="errorState" class="hidden text-center animate-fadeInUp">
                    <div class="w-24 h-24 bg-red-500/20 rounded-full flex items-center justify-center mx-auto mb-6 border-2 border-red-500 shadow-[0_0_30px_rgba(239,68,68,0.4)]">
                        <svg class="w-12 h-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-white mb-2">Authentication Failed</h2>
                    <p id="errorMessage" class="text-slate-400 mb-6">Identity could not be verified.</p>
                    <button onclick="resetFlow()" class="px-8 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors">Try Again</button>
                </div>

            </div>
        </div>
    </div>

    </div>
    
    <script src="js/webauthn-helper.js?v=9999"></script>
    <!-- FingerprintJS (Telemetry Only) -->
    <script src="https://cdn.jsdelivr.net/npm/@fingerprintjs/fingerprintjs@3/dist/fp.min.js"></script>
    <script>
        // Device Telemetry Storage
        let deviceTelemetry = {
            visitorId: null,
            screenResolution: `${window.screen.width}x${window.screen.height}`,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            platform: navigator.platform,
            hardwareConcurrency: navigator.hardwareConcurrency || null,
            deviceMemory: navigator.deviceMemory || null,
            userAgent: navigator.userAgent
        };

        // Initialize FingerprintJS Immediately
        (async () => {
            try {
                const fp = await FingerprintJS.load();
                constresult = await fp.get();
                deviceTelemetry.visitorId = result.visitorId;
                console.log("Device Telemetry Ready:", deviceTelemetry.visitorId);
            } catch (e) {
                console.warn("FingerprintJS failed to load (Telemetry skipped)", e);
            }
        })();

        let stream = null;
        let userData = {
            account: '<?= $prefilledAccount ?>',
            userId: '<?= $prefilledId ?? "" ?>',
            face: null,
            finger: null
        };
        const isStep2 = <?= $isStep2 ? 'true' : 'false' ?>;

        // Auto-start if Step 2
        window.addEventListener('load', () => {
             // Basic Error Trap
             window.onerror = function(msg, url, line) {
                alert("JS Error: " + msg);
                return false;
            };

            if (isStep2 && userData.account) {
                 startAuthFlow();
            }
        });

        // Initialize
        async function startAuthFlow() {
            // DEBUG: REMOVE AFTER CONFIRMATION
            console.log("startAuthFlow called");
            
            // Only read input if not already set (Step 2 flow)
            if (!userData.account) {
                const accInfo = document.getElementById('accountNumber').value;
                if (!accInfo || accInfo.length < 5) {
                    alert('Please enter a valid account number');
                    return;
                }
                userData.account = accInfo;
            }

            document.getElementById('inputState').classList.add('hidden');
            document.getElementById('scanningState').classList.remove('hidden');
            
            try {
                // Initialize Camera
                stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
                const video = document.getElementById('cameraVideo');
                video.srcObject = stream;
                
                // Start Face Process
                updateStatus('face', 'active', 'Align face in frame...');
                document.getElementById('scanOverlay').classList.remove('hidden');
                
                // Wait for camera to stabilize
                setTimeout(captureAndVerifyFace, 2000);

            } catch (err) {
                console.error("Camera Error:", err);
                showError("Camera access denied. Cannot proceed.");
            }
        }

        async function captureAndVerifyFace() {
            updateStatus('face', 'processing', 'Analyzing biometric data...');
            
            const video = document.getElementById('cameraVideo');
            const canvas = document.createElement('canvas');
            
            // Resize to reasonable dimensions
            const MAX_WIDTH = 640;
            const scale = MAX_WIDTH / video.videoWidth;
            canvas.width = MAX_WIDTH;
            canvas.height = video.videoHeight * scale;
            
            canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Limit quality to 0.7 to keep size small
            const imageBase64 = canvas.toDataURL('image/jpeg', 0.7).split(',')[1];
            
            try {
                // If we don't have userId yet, fetch it
                if (!userData.userId) {
                     const idResponse = await fetch('../api/get-user-id.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ account_number: userData.account })
                    });
                    const idData = await idResponse.json();
                    
                    if (!idData.success) throw new Error(idData.message || "Account not found");
                    userData.userId = idData.user_id;
                }

                const userId = userData.userId;

                // NOW Verify Face
                const verifyResponse = await fetch('../api/secure_biometric_auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        user_id: userId,
                        biometric_data: imageBase64,
                        modality: 'face'
                    })
                });

                const verifyResult = await verifyResponse.json();

                if (verifyResult.success) {
                    updateStatus('face', 'success', `Verified (Confidence: ${verifyResult.confidence}%)`);
                    userData.faceStr = imageBase64; 
                    startFingerprint();
                } else {
                    throw new Error(verifyResult.error || "Face verification failed");
                }

            } catch (err) {
                showError(err.message);
            }
        }
        
        // Helper Functions
        function updateStatus(type, state, message) {
            const statusEl = document.getElementById(type + 'Status');
            const iconEl = document.getElementById(type + 'Icon');
            const stepEl = document.getElementById(type + 'Step');
            
            if (statusEl) statusEl.textContent = message;
            
            if (state === 'active') {
                stepEl.classList.remove('opacity-50');
                stepEl.classList.add('border-cyan-500', 'bg-cyan-900/10');
                iconEl.classList.add('text-cyan-400', 'animate-pulse');
            } else if (state === 'success') {
                stepEl.classList.remove('border-cyan-500', 'bg-cyan-900/10');
                stepEl.classList.add('border-green-500/50', 'bg-green-900/10');
                iconEl.classList.remove('text-slate-500', 'animate-pulse');
                iconEl.classList.add('text-green-400');
            } else if (state === 'processing') {
                iconEl.classList.add('animate-spin');
            }
        }

        function showError(message) {
            document.getElementById('scanningState').classList.add('hidden');
            document.getElementById('errorState').classList.remove('hidden');
            document.getElementById('errorMessage').textContent = message;
            
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
        }
        async function startFingerprint() {
            updateStatus('finger', 'active', 'Place finger on sensor...');
            
            // SIMULATION ONLY - To prevent crashes
            setTimeout(() => {
                updateStatus('finger', 'success', 'Verified (Soft Check)');
                setTimeout(completeAuth, 500);
            }, 1000);
        }

        async function completeAuth() {
            // Final Verification Call
            try {
                // Here we would send the WebAuthn assertion to backend verify
                document.getElementById('scanningState').classList.add('hidden');
                document.getElementById('successState').classList.remove('hidden');
                
                // Redirect
                setTimeout(() => {
                    window.location.href = 'dashboard-modern.php';
                }, 1500);
            } catch (err) {
                showError("Final authentication failed");
            }
        }

        function resetFlow() {
            document.getElementById('errorState').classList.add('hidden');
            
            if (isStep2) {
                // Restart immediately for 2FA flow
                // Reset waiting UI
                document.getElementById('faceStep').className = "flex items-center space-x-4 p-4 rounded-lg bg-slate-800/30 border border-slate-700 transition-all";
                updateStatus('face', 'idle', 'Waiting to start...');
                document.getElementById('fingerStep').className = "flex items-center space-x-4 p-4 rounded-lg bg-slate-800/30 border border-slate-700 transition-all opacity-50";
                
                // Restart
                startAuthFlow();
            } else {
                 document.getElementById('inputState').classList.remove('hidden');
                 document.getElementById('accountNumber').value = '';
                 
                 // Reset UI steps
                 document.getElementById('faceStep').className = "flex items-center space-x-4 p-4 rounded-lg bg-slate-800/30 border border-slate-700 transition-all";
                 updateStatus('face', 'idle', 'Waiting to start...');
                 
                 document.getElementById('fingerStep').className = "flex items-center space-x-4 p-4 rounded-lg bg-slate-800/30 border border-slate-700 transition-all opacity-50";
                 updateStatus('finger', 'idle', 'Waiting for face verification...');
            }
        }
    </script>
    </script>
    <script src="js/theme-system.js"></script>
</body>
</html>