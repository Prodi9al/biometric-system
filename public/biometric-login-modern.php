<?php
require_once '../config/config.php';
require_once '../core/Session.php';

if (Session::isLoggedIn()) {
    header('Location: dashboard-modern.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= BANK_NAME ?> - Biometric Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes scanLine {
            0%, 100% { top: 10%; }
            50% { top: 90%; }
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .animate-fadeInUp { animation: fadeInUp 0.6s ease-out; }
        .animate-fadeInDown { animation: fadeInDown 0.6s ease-out; }
        .animate-scanLine { animation: scanLine 4s linear infinite; }
        .animate-pulse { animation: pulse 2s infinite; }
        .grid-bg {
            background-image: linear-gradient(rgba(6,182,212,0.3) 1px, transparent 1px), linear-gradient(90deg, rgba(6,182,212,0.3) 1px, transparent 1px);
            background-size: 40px 40px;
        }
    </style>
    </style>
    <link rel="stylesheet" href="css/theme-overrides.css">
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-blue-950 flex items-center justify-center p-4 font-sans">
    <div class="w-full max-w-4xl aspect-[4/3] bg-gradient-to-br from-slate-900 to-slate-950 rounded-2xl shadow-2xl border border-slate-800 overflow-hidden relative">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-2/3 h-1/3 bg-cyan-500/10 blur-[100px] pointer-events-none"></div>

        <!-- Toggle Theme Button -->
        <div class="absolute top-4 right-4 z-20">
            <button onclick="toggleTheme()" class="p-2 bg-slate-800/50 hover:bg-slate-700/50 text-slate-400 hover:text-white rounded-full border border-slate-700/50 transition-colors backdrop-blur-sm" title="Toggle Theme">
                <svg id="themeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </button>
        </div>
        
        <div class="h-full flex flex-col p-8 relative z-10">
            <!-- Header -->
            <div class="mb-4 shrink-0">
                <div class="text-center animate-fadeInDown">
                    <h1 class="text-3xl tracking-[0.2em] text-cyan-400 uppercase font-bold drop-shadow-[0_0_10px_rgba(34,211,238,0.3)]">Biometric Authentication</h1>
                    <p class="text-slate-400 text-sm mt-2 tracking-widest uppercase">Align your face within the frame</p>
                </div>
            </div>
            
            <!-- Camera Frame -->
            <div class="flex-1 flex items-center justify-center mb-6 min-h-0 animate-fadeInUp" style="animation-delay: 0.2s;">
                <div class="h-full aspect-square max-h-[380px] w-full">
                    <div class="relative w-full h-full bg-slate-950 rounded-lg border-2 border-slate-700 overflow-hidden shadow-[0_0_30px_rgba(6,182,212,0.15)]">
                        <!-- Live Camera Video -->
                        <video id="cameraVideo" class="absolute inset-0 w-full h-full object-cover" autoplay muted playsinline style="display: none;"></video>
                        
                        <!-- Grid Background (shown when no camera) -->
                        <div id="gridBackground" class="absolute inset-0 opacity-20 grid-bg"></div>
                        
                        <!-- Face Tracking Corners -->
                        <div class="absolute top-8 left-8 w-12 h-12 border-t-4 border-l-4 border-cyan-400 rounded-tl-xl drop-shadow-[0_0_10px_rgba(6,182,212,0.8)] z-10"></div>
                        <div class="absolute top-8 right-8 w-12 h-12 border-t-4 border-r-4 border-cyan-400 rounded-tr-xl drop-shadow-[0_0_10px_rgba(6,182,212,0.8)] z-10"></div>
                        <div class="absolute bottom-8 left-8 w-12 h-12 border-b-4 border-l-4 border-cyan-400 rounded-bl-xl drop-shadow-[0_0_10px_rgba(6,182,212,0.8)] z-10"></div>
                        <div class="absolute bottom-8 right-8 w-12 h-12 border-b-4 border-r-4 border-cyan-400 rounded-br-xl drop-shadow-[0_0_10px_rgba(6,182,212,0.8)] z-10"></div>
                        
                        <!-- Scanning Line -->
                        <div class="absolute left-0 right-0 h-[2px] bg-cyan-400 shadow-[0_0_20px_rgba(6,182,212,1)] animate-scanLine z-10"></div>
                        
                        <!-- Live Feed Label -->
                        <div class="absolute top-4 w-full text-center text-xs text-cyan-500 font-mono tracking-widest uppercase animate-pulse z-10">
                            <span id="cameraStatus">Live Camera Feed</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Biometric Status Icons -->
            <div class="flex justify-center gap-12 mb-8 shrink-0 animate-fadeInUp" style="animation-delay: 0.4s;">
                <!-- Face Scan -->
                <div class="flex flex-col items-center gap-3">
                    <div id="faceIcon" class="p-4 rounded-full border-2 bg-slate-900 transition-all duration-500 border-green-400 shadow-[0_0_15px_rgba(74,222,128,0.4)]">
                        <svg class="w-8 h-8 transition-colors duration-500 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="text-center">
                        <p class="text-xs font-bold uppercase tracking-wider mb-1 text-green-400">Face Scan</p>
                        <p id="faceStatus" class="text-[10px] uppercase tracking-widest text-green-500">Verifying...</p>
                    </div>
                </div>
                
                <!-- Retina -->
                <div class="flex flex-col items-center gap-3">
                    <div id="retinaIcon" class="p-4 rounded-full border-2 bg-slate-900 transition-all duration-500 border-slate-700">
                        <svg class="w-8 h-8 transition-colors duration-500 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </div>
                    <div class="text-center">
                        <p class="text-xs font-bold uppercase tracking-wider mb-1 text-slate-400">Retina</p>
                        <p id="retinaStatus" class="text-[10px] uppercase tracking-widest text-slate-600">Waiting</p>
                    </div>
                </div>
                
                <!-- Fingerprint -->
                <div class="flex flex-col items-center gap-3">
                    <div id="fingerIcon" class="p-4 rounded-full border-2 bg-slate-900 transition-all duration-500 border-slate-700">
                        <svg class="w-8 h-8 transition-colors duration-500 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                        </svg>
                    </div>
                    <div class="text-center">
                        <p class="text-xs font-bold uppercase tracking-wider mb-1 text-slate-400">Fingerprint</p>
                        <p id="fingerStatus" class="text-[10px] uppercase tracking-widest text-slate-600">Waiting</p>
                    </div>
                </div>
            </div>
            
            <!-- Account Input (Hidden initially) -->
            <div id="accountInput" class="mb-6 shrink-0" style="display: none;">
                <input type="text" id="accountNumber" class="w-full p-4 bg-slate-800/50 border border-slate-600 rounded-lg text-white text-center text-lg tracking-widest" placeholder="Enter Account Number">
            </div>
            
            <!-- Action Buttons -->
            <div class="flex justify-center gap-4 shrink-0 animate-fadeInUp" style="animation-delay: 0.6s;">
                <button onclick="toggleTheme()" class="p-3 bg-slate-800/50 hover:bg-slate-700/50 rounded-full border border-slate-600 hover:border-cyan-400/50 transition-all duration-300" title="Toggle Theme">
                    <svg id="themeIcon" class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </button>
                <button onclick="restartBiometric()" id="restartBtn" class="p-3 bg-slate-800/50 hover:bg-slate-700/50 rounded-full border border-slate-600 hover:border-orange-400/50 transition-all duration-300" title="Restart Biometric" style="display: none;">
                    <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </button>
                <button onclick="startAuthentication()" id="actionBtn" class="px-24 py-4 bg-slate-800/50 hover:bg-slate-700/50 text-cyan-400 rounded-full border border-slate-600 hover:border-cyan-400/50 transition-all duration-300 backdrop-blur-sm uppercase tracking-[0.15em] font-semibold hover:scale-105 active:scale-95">
                    Start Scan
                </button>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/components/WindowsHelloFace.js"></script>
    <script>
        let currentStep = 0;
        let biometricData = {};
        let cameraStream = null;
        let windowsHelloAvailable = false;
        
        // Initialize camera and Windows Hello on page load
        window.addEventListener('load', async () => {
            await initializeCamera();
            await checkWindowsHelloAvailability();
        });
        
        async function initializeCamera() {
            try {
                const constraints = {
                    video: { 
                        width: { ideal: 640 }, 
                        height: { ideal: 480 }, 
                        facingMode: 'user',
                        frameRate: { ideal: 30 }
                    }
                };
                
                cameraStream = await navigator.mediaDevices.getUserMedia(constraints);
                
                // Display camera feed
                const video = document.getElementById('cameraVideo');
                const grid = document.getElementById('gridBackground');
                const status = document.getElementById('cameraStatus');
                
                if (video && cameraStream) {
                    video.srcObject = cameraStream;
                    video.style.display = 'block';
                    grid.style.display = 'none';
                    status.textContent = 'Live Camera Active';
                    console.log('Camera feed active');
                }
            } catch (error) {
                document.getElementById('cameraStatus').textContent = 'Camera Unavailable - Demo Mode';
                console.log('Camera access denied or unavailable');
            }
        }
        
        async function checkWindowsHelloAvailability() {
            try {
                const availability = await windowsHelloFace.checkAvailability();
                if (availability && availability.success) {
                    windowsHelloAvailable = availability.windows_hello_available;
                    console.log('Windows Hello Face available:', windowsHelloAvailable);
                    
                    if (windowsHelloAvailable) {
                        // Update UI to show Windows Hello is available
                        document.querySelector('p.text-slate-400').textContent = 'Windows Hello Face Ready - Look at camera';
                    }
                }
            } catch (error) {
                console.log('Failed to check Windows Hello availability:', error);
            }
        }
        
        function startAuthentication() {
            document.getElementById('accountInput').style.display = 'block';
            document.getElementById('actionBtn').textContent = 'Authenticate';
            document.getElementById('actionBtn').onclick = authenticate;
            document.getElementById('restartBtn').style.display = 'block';
            
            // Start progressive biometric sequence
            setTimeout(() => activateFaceScan(), 1000);
        }
        
        function restartBiometric() {
            // Reset all biometric data
            biometricData = {};
            
            // Reset all status indicators
            updateStatus('faceIcon', 'faceStatus', 'Waiting', false, 'idle');
            updateStatus('retinaIcon', 'retinaStatus', 'Waiting', false, 'idle');
            updateStatus('fingerIcon', 'fingerStatus', 'Waiting', false, 'idle');
            
            // Reset button
            const btn = document.getElementById('actionBtn');
            btn.textContent = 'Start Scan';
            btn.onclick = startAuthentication;
            btn.className = 'px-24 py-4 bg-slate-800/50 hover:bg-slate-700/50 text-cyan-400 rounded-full border border-slate-600 hover:border-cyan-400/50 transition-all duration-300 backdrop-blur-sm uppercase tracking-[0.15em] font-semibold hover:scale-105 active:scale-95';
            btn.disabled = false;
            
            // Hide restart button
            document.getElementById('restartBtn').style.display = 'none';
            
            showNotification('Biometric verification restarted', 'success');
        }
        
        async function activateFaceScan() {
            updateStatus('faceIcon', 'faceStatus', 'Analyzing...', true, 'scanning');
            await simulateFaceScan();
        }
        
        async function simulateFaceScan() {
            try {
                // Capture actual camera frame
                const canvas = document.createElement('canvas');
                const video = document.getElementById('cameraVideo');
                
                if (video && video.videoWidth > 0) {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(video, 0, 0);
                    
                    // Convert to base64
                    const imageData = canvas.toDataURL('image/jpeg', 0.8).split(',')[1];
                    
                    updateStatus('faceIcon', 'faceStatus', 'Processing...', true, 'scanning');
                    
                    // Send to biometric service for verification
                    const response = await fetch('../api/secure_biometric_auth.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            user_id: 1, // Demo user
                            biometric_data: imageData,
                            modality: 'face'
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success && result.confidence >= 85) {
                        updateStatus('faceIcon', 'faceStatus', 'Verified', true, 'complete');
                        biometricData.face_data = imageData;
                        biometricData.confidence = result.confidence;
                        setTimeout(() => activateRetinaScan(), 500);
                    } else {
                        updateStatus('faceIcon', 'faceStatus', 'Failed', false, 'idle');
                        showNotification('Face verification failed', 'error');
                    }
                } else {
                    // No camera - use Windows Hello or fail
                    updateStatus('faceIcon', 'faceStatus', 'No Camera', false, 'idle');
                    showNotification('Camera required for face scan', 'error');
                }
            } catch (error) {
                updateStatus('faceIcon', 'faceStatus', 'Error', false, 'idle');
                showNotification('Face scan error: ' + error.message, 'error');
            }
        }
        
        function activateRetinaScan() {
            // Skip retina scan - go directly to fingerprint for dual-factor auth
            updateStatus('retinaIcon', 'retinaStatus', 'Skipped', false, 'idle');
            setTimeout(() => activateFingerprintScan(), 500);
        }
        
        function activateFingerprintScan() {
            updateStatus('fingerIcon', 'fingerStatus', 'Place finger...', true, 'scanning');
            
            // Check for Windows Hello / WebAuthn support
            if (window.PublicKeyCredential) {
                setTimeout(() => {
                    updateStatus('fingerIcon', 'fingerStatus', 'Touch sensor...', true, 'scanning');
                    showWindowsHelloPrompt();
                }, 1000);
            } else {
                // Fallback to simulation
                simulateFingerprintScan();
            }
        }
        
        function showWindowsHelloPrompt() {
            const btn = document.getElementById('actionBtn');
            btn.textContent = 'Verify with Device Biometrics';
            btn.className = 'px-24 py-4 bg-blue-600 hover:bg-blue-700 text-white rounded-full border border-blue-500 transition-all duration-300 backdrop-blur-sm uppercase tracking-[0.15em] font-semibold hover:scale-105 active:scale-95 shadow-lg animate-pulse';
            btn.onclick = triggerWindowsHello;
        }
        
        async function triggerWindowsHello() {
            await simulateFingerprintScan();
        }
        
        async function simulateFingerprintScan() {
            try {
                updateStatus('fingerIcon', 'fingerStatus', 'Processing...', true, 'scanning');
                
                // Test fingerprint verification
                const response = await fetch('../api/secure_biometric_auth.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        user_id: 1, // Demo user
                        biometric_data: 'fingerprint_template_' + Date.now(),
                        modality: 'fingerprint'
                    })
                });
                
                const result = await response.json();
                
                if (result.success && result.confidence >= 85) {
                    updateStatus('fingerIcon', 'fingerStatus', 'Verified', true, 'complete');
                    biometricData.finger_data = 'fingerprint_template_' + Date.now();
                    enableAuthButton();
                } else {
                    updateStatus('fingerIcon', 'fingerStatus', 'Failed', false, 'idle');
                    showNotification('Fingerprint verification failed', 'error');
                }
            } catch (error) {
                updateStatus('fingerIcon', 'fingerStatus', 'Error', false, 'idle');
                showNotification('Fingerprint scan error: ' + error.message, 'error');
            }
        }
        
        function enableAuthButton() {
            const btn = document.getElementById('actionBtn');
            btn.textContent = 'Authenticate Now';
            btn.className = 'px-24 py-4 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white rounded-full border border-green-500 transition-all duration-300 backdrop-blur-sm uppercase tracking-[0.15em] font-semibold hover:scale-105 active:scale-95 shadow-lg shadow-green-500/25';
            btn.onclick = authenticate;
        }
        
        function generateBiometricTemplate(type) {
            // Generate realistic biometric template simulation
            const timestamp = Date.now();
            const random = Math.random().toString(36).substring(2, 15);
            return `${type}_template_${timestamp}_${random}`;
        }
        
        function updateStatus(iconId, statusId, status, active, state = 'idle') {
            const icon = document.getElementById(iconId);
            const statusEl = document.getElementById(statusId);
            
            if (active) {
                if (state === 'scanning') {
                    icon.className = 'p-4 rounded-full border-2 bg-slate-900 transition-all duration-500 border-yellow-400 shadow-[0_0_15px_rgba(251,191,36,0.4)] animate-pulse';
                    icon.querySelector('svg').className = 'w-8 h-8 transition-colors duration-500 text-yellow-400';
                    statusEl.className = 'text-[10px] uppercase tracking-widest text-yellow-500';
                } else if (state === 'complete') {
                    icon.className = 'p-4 rounded-full border-2 bg-slate-900 transition-all duration-500 border-green-400 shadow-[0_0_15px_rgba(74,222,128,0.4)]';
                    icon.querySelector('svg').className = 'w-8 h-8 transition-colors duration-500 text-green-400';
                    statusEl.className = 'text-[10px] uppercase tracking-widest text-green-500';
                } else {
                    icon.className = 'p-4 rounded-full border-2 bg-slate-900 transition-all duration-500 border-cyan-400 shadow-[0_0_15px_rgba(6,182,212,0.4)]';
                    icon.querySelector('svg').className = 'w-8 h-8 transition-colors duration-500 text-cyan-400';
                    statusEl.className = 'text-[10px] uppercase tracking-widest text-cyan-500';
                }
            } else {
                icon.className = 'p-4 rounded-full border-2 bg-slate-900 transition-all duration-500 border-slate-700';
                icon.querySelector('svg').className = 'w-8 h-8 transition-colors duration-500 text-slate-500';
                statusEl.className = 'text-[10px] uppercase tracking-widest text-slate-600';
            }
            
            statusEl.textContent = status;
        }
        
        async function authenticate() {
            // Check if this is step 2 (coming from PIN login)
            const urlParams = new URLSearchParams(window.location.search);
            const isStep2 = urlParams.get('step') === '2';
            
            if (!isStep2) {
                const accountNumber = document.getElementById('accountNumber').value;
                if (!accountNumber) {
                    showNotification('Please enter your account number', 'error');
                    return;
                }
            }
            
            // Dual-factor authentication: Face + Fingerprint only
            if (!biometricData.face_data || !biometricData.finger_data) {
                showNotification('Please complete Face and Fingerprint scans', 'error');
                return;
            }
            
            const btn = document.getElementById('actionBtn');
            btn.textContent = 'Processing...';
            btn.disabled = true;
            btn.className = 'px-24 py-4 bg-slate-600 text-slate-300 rounded-full border border-slate-500 cursor-not-allowed uppercase tracking-[0.15em] font-semibold';
            
            // Add authentication progress indicator
            showAuthProgress();
            
            try {
                if (isStep2) {
                    // Complete login for step 2
                    const response = await fetch('../api/complete-login.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(biometricData)
                    });
                    
                    const result = await response.json();
                    
                    // FAIL-SECURE: Only proceed if explicitly successful
                    if (result.success === true && result.session_id) {
                        showNotification('Authentication successful!', 'success');
                        btn.textContent = 'Success! Redirecting...';
                        btn.className = 'px-24 py-4 bg-green-600 text-white rounded-full border border-green-500 uppercase tracking-[0.15em] font-semibold';
                        setTimeout(() => {
                            window.location.href = 'dashboard-modern.php';
                        }, 1500);
                    } else {
                        showNotification('Authentication failed: ' + (result.message || 'Verification failed'), 'error');
                        resetAuthButton();
                    }
                } else {
                    // Regular biometric login with enhanced data
                    const authData = {
                        account_number: document.getElementById('accountNumber').value,
                        biometric_data: {
                            face: biometricData.face_data,
                            fingerprint: biometricData.finger_data,
                            timestamp: Date.now(),
                            confidence_scores: {
                                face: biometricData.confidence || (Math.random() * 10 + 90),
                                fingerprint: Math.random() * 10 + 90
                            },
                            hardware_used: biometricData.windows_hello_method || (window.PublicKeyCredential ? 'WebAuthn' : 'Simulation'),
                            windows_hello_used: !!biometricData.windows_hello_method
                        }
                    };
                    
                    const response = await fetch('../api/biometric-auth.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(authData)
                    });
                    
                    const result = await response.json();
                    
                    // FAIL-SECURE: Only proceed if explicitly successful
                    if (result.success === true && result.session_id) {
                        showNotification('Authentication successful!', 'success');
                        btn.textContent = 'Success! Redirecting...';
                        btn.className = 'px-24 py-4 bg-green-600 text-white rounded-full border border-green-500 uppercase tracking-[0.15em] font-semibold';
                        setTimeout(() => {
                            window.location.href = 'dashboard-modern.php';
                        }, 1500);
                    } else {
                        showNotification('Authentication failed: ' + (result.message || 'Verification failed'), 'error');
                        resetAuthButton();
                        updateStatus('faceIcon', 'faceStatus', 'Failed', false, 'idle');
                        updateStatus('fingerIcon', 'fingerStatus', 'Failed', false, 'idle');
                    }
                }
            } catch (error) {
                showNotification('Connection error. Please try again.', 'error');
                resetAuthButton();
            }
        }
        
        function showNotification(message, type) {
            // Create notification element if it doesn't exist
            let notification = document.getElementById('notification');
            if (!notification) {
                notification = document.createElement('div');
                notification.id = 'notification';
                notification.className = 'fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full';
                document.body.appendChild(notification);
            }
            
            // Set notification style and content
            if (type === 'success') {
                notification.className = 'fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 bg-green-600 text-white';
            } else {
                notification.className = 'fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 bg-red-600 text-white';
            }
            
            notification.textContent = message;
            notification.style.transform = 'translateX(0)';
            
            // Auto-hide after 3 seconds
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
            }, 3000);
        }
        
        function showAuthProgress() {
            // Visual feedback during authentication
            const icons = ['faceIcon', 'retinaIcon', 'fingerIcon'];
            icons.forEach((iconId, index) => {
                setTimeout(() => {
                    const icon = document.getElementById(iconId);
                    icon.style.transform = 'scale(1.1)';
                    setTimeout(() => {
                        icon.style.transform = 'scale(1)';
                    }, 200);
                }, index * 100);
            });
        }
        
        function resetAuthButton() {
            const btn = document.getElementById('actionBtn');
            btn.textContent = 'Try Again';
            btn.disabled = false;
            btn.className = 'px-24 py-4 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white rounded-full border border-green-500 transition-all duration-300 backdrop-blur-sm uppercase tracking-[0.15em] font-semibold hover:scale-105 active:scale-95 shadow-lg shadow-green-500/25';
        }
        
        // Theme toggle functionality
        function toggleTheme() {
            const body = document.body;
            const isDark = body.classList.contains('dark-theme') || !body.classList.contains('light-theme');
            
            if (isDark) {
                // Switch to light mode
                body.className = 'min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50 flex items-center justify-center p-4 font-sans light-theme';
                document.querySelector('.w-full.max-w-4xl').className = 'w-full max-w-4xl aspect-[4/3] bg-gradient-to-br from-white to-slate-50 rounded-2xl shadow-2xl border border-slate-200 overflow-hidden relative';
                updateBiometricThemeColors('light');
                document.getElementById('themeIcon').innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>';
                localStorage.setItem('theme', 'light');
            } else {
                // Switch to dark mode
                body.className = 'min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-blue-950 flex items-center justify-center p-4 font-sans dark-theme';
                document.querySelector('.w-full.max-w-4xl').className = 'w-full max-w-4xl aspect-[4/3] bg-gradient-to-br from-slate-900 to-slate-950 rounded-2xl shadow-2xl border border-slate-800 overflow-hidden relative';
                updateBiometricThemeColors('dark');
                document.getElementById('themeIcon').innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>';
                localStorage.setItem('theme', 'dark');
            }
        }
        
        function updateBiometricThemeColors(theme) {
            const cameraFrame = document.querySelector('.bg-slate-950');
            const texts = document.querySelectorAll('h1, h2, h3, p, span');
            const buttons = document.querySelectorAll('button');
            
            if (theme === 'light') {
                if (cameraFrame) cameraFrame.className = cameraFrame.className.replace('bg-slate-950', 'bg-slate-100');
                
                texts.forEach(text => {
                    if (text.classList.contains('text-white')) {
                        text.classList.remove('text-white');
                        text.classList.add('text-slate-900');
                    }
                    if (text.classList.contains('text-slate-400')) {
                        text.classList.remove('text-slate-400');
                        text.classList.add('text-slate-600');
                    }
                    if (text.classList.contains('text-cyan-400')) {
                        text.classList.remove('text-cyan-400');
                        text.classList.add('text-blue-600');
                    }
                });
                
                buttons.forEach(btn => {
                    if (btn.classList.contains('text-cyan-400')) {
                        btn.classList.remove('text-cyan-400');
                        btn.classList.add('text-blue-600');
                    }
                });
            } else {
                if (cameraFrame) cameraFrame.className = cameraFrame.className.replace('bg-slate-100', 'bg-slate-950');
                
                texts.forEach(text => {
                    if (text.classList.contains('text-slate-900')) {
                        text.classList.remove('text-slate-900');
                        text.classList.add('text-white');
                    }
                    if (text.classList.contains('text-slate-600')) {
                        text.classList.remove('text-slate-600');
                        text.classList.add('text-slate-400');
                    }
                    if (text.classList.contains('text-blue-600')) {
                        text.classList.remove('text-blue-600');
                        text.classList.add('text-cyan-400');
                    }
                });
                
                buttons.forEach(btn => {
                    if (btn.classList.contains('text-blue-600')) {
                        btn.classList.remove('text-blue-600');
                        btn.classList.add('text-cyan-400');
                    }
                });
            }
        }
        
        // Load saved theme on page load
        window.addEventListener('load', () => {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'light') {
                toggleTheme();
            }
        });
        
        // Cleanup camera stream when leaving page
        window.addEventListener('beforeunload', () => {
            if (cameraStream) {
                cameraStream.getTracks().forEach(track => track.stop());
            }
        });
    </script>
    </script>
    <script src="js/theme-system.js"></script>
</body>
</html>