<?php require_once '../config/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= BANK_NAME ?> - Account Registration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <style>
        .step-active { border-color: #22d3ee; color: #22d3ee; }
        .step-completed { border-color: #22c55e; color: #22c55e; background: rgba(34, 197, 94, 0.1); }
    </style>
    </style>
    <link rel="stylesheet" href="css/theme-overrides.css">
</head>
<body class="min-h-screen bg-slate-950 flex flex-col font-sans text-slate-200">
    
    <!-- Navbar -->
    <nav class="bg-slate-900 border-b border-slate-800 px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-green-500 to-cyan-500 flex items-center justify-center font-bold text-white text-xs">A</div>
            <span class="font-bold text-white tracking-wide"><?= BANK_NAME ?></span>
        </div>
        <div class="flex items-center gap-4">
            <button onclick="toggleTheme()" class="p-2 bg-slate-800 hover:bg-slate-700 text-slate-400 rounded-full border border-slate-700 transition-colors" title="Toggle Theme">
                <svg id="themeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </button>
            <a href="login.php" class="text-sm text-slate-400 hover:text-white transition-colors">Back to Login</a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow flex items-center justify-center p-4">
        <div class="w-full max-w-2xl bg-slate-900/50 backdrop-blur border border-slate-800 rounded-xl overflow-hidden shadow-2xl">
            
            <!-- Progress Steps -->
            <div class="grid grid-cols-3 border-b border-slate-800">
                <div id="stepHeader1" class="py-4 text-center border-b-2 border-cyan-400 text-cyan-400 font-bold text-sm bg-slate-800/50">
                    1. Account Details
                </div>
                <div id="stepHeader2" class="py-4 text-center border-b-2 border-transparent text-slate-500 font-bold text-sm">
                    2. Face Scan
                </div>
                <div id="stepHeader3" class="py-4 text-center border-b-2 border-transparent text-slate-500 font-bold text-sm">
                    3. Finish
                </div>
            </div>

            <div class="p-8">
                <!-- STEP 1: Details Form -->
                <div id="step1" class="space-y-6">
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-bold text-white">Create New Account</h2>
                        <p class="text-slate-400 text-sm mt-2">Enter your personal details to begin.</p>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs uppercase tracking-wider text-slate-500 mb-1">Full Name</label>
                            <input type="text" id="fullName" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-3 text-white focus:border-cyan-500 outline-none transition-colors" placeholder="e.g. John Doe">
                        </div>
                        <div>
                            <label class="block text-xs uppercase tracking-wider text-slate-500 mb-1">Choose a PIN (4 Digits)</label>
                            <input type="password" id="pin" maxlength="4" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-3 text-white focus:border-cyan-500 outline-none transition-colors tracking-widest text-center text-xl" placeholder="••••">
                        </div>
                    </div>

                    <button onclick="submitStep1()" class="w-full bg-cyan-600 hover:bg-cyan-500 text-white font-bold py-3 rounded-lg shadow-lg shadow-cyan-900/20 transition-all mt-4">
                        Next: Face ID Setup →
                    </button>
                    <p id="error1" class="text-red-400 text-sm text-center hidden mt-2"></p>
                </div>

                <!-- STEP 2: Biometric -->
                <div id="step2" class="hidden space-y-6 text-center">
                    <h2 class="text-2xl font-bold text-white">Face Verification</h2>
                    <p class="text-slate-400 text-sm">Position your face within the frame.</p>
                    
                    <div class="relative w-64 h-64 mx-auto bg-black rounded-full overflow-hidden border-4 border-slate-700 shadow-2xl">
                        <video id="camera" class="w-full h-full object-cover" autoplay playsinline muted></video>
                        <div class="absolute inset-0 border-4 border-cyan-500/30 rounded-full animate-pulse"></div>
                    </div>
                    
                    <p id="scanStatus" class="text-cyan-400 font-mono text-sm animate-pulse">Waiting for camera...</p>

                    <button id="captureBtn" onclick="captureFace()" class="w-full bg-cyan-600 hover:bg-cyan-500 text-white font-bold py-3 rounded-lg shadow-lg transition-all hidden">
                        📸 Capture Photo
                    </button>
                    <button id="retryBtn" onclick="startCamera()" class="w-full bg-slate-700 hover:bg-slate-600 text-white font-bold py-3 rounded-lg hidden">
                        ↺ Retake
                    </button>
                </div>

                <!-- STEP 3: Success -->
                <div id="step3" class="hidden text-center space-y-6 py-8">
                    <div class="w-20 h-20 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-check text-4xl text-green-500"></i>
                    </div>
                    
                    <h2 class="text-3xl font-bold text-white">Registration Complete!</h2>
                    
                    <div class="bg-slate-800/50 p-6 rounded-xl border border-slate-700">
                        <p class="text-slate-400 text-sm uppercase tracking-widest mb-2">Your Account Number</p>
                        <p id="finalAccount" class="text-3xl font-mono text-cyan-400 font-bold tracking-wider select-all"></p>
                        <p class="text-xs text-slate-500 mt-2">(Please save this number safely)</p>
                    </div>

                    <div class="bg-yellow-900/20 border border-yellow-700/30 p-4 rounded-lg">
                        <p class="text-yellow-400 text-sm">
                            <i class="fas fa-clock mr-2"></i>
                            <strong>Status: Pending Approval</strong><br>
                            An administrator must approve your account before you can log in.
                        </p>
                    </div>
                    
                    <a href="login.php" class="block w-full bg-slate-700 hover:bg-slate-600 text-white font-bold py-3 rounded-lg transition-all">
                        Return to Login
                    </a>
                </div>

            </div>
        </div>
    </main>

    <script>
        let userData = { userId: null, account: null };
        let stream = null;

        async function submitStep1() {
            const fullName = document.getElementById('fullName').value;
            const pin = document.getElementById('pin').value;
            const errorEl = document.getElementById('error1');
            
            if (fullName.length < 3 || pin.length !== 4) {
                errorEl.textContent = "Please enter a valid name and 4-digit PIN.";
                errorEl.classList.remove('hidden');
                return;
            }
            
            // Disable button
            document.querySelector('#step1 button').innerHTML = 'Creating Account...';
            document.querySelector('#step1 button').disabled = true;

            try {
                const res = await fetch('../api/register_user.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ full_name: fullName, pin: pin })
                });
                
                const data = await res.json();
                
                if (data.success) {
                    userData.userId = data.user_id;
                    userData.account = data.account_number;
                    
                    // Move to Step 2
                    goToStep(2);
                    startCamera();
                } else {
                    throw new Error(data.error);
                }
            } catch (err) {
                errorEl.textContent = "Error: " + err.message;
                errorEl.classList.remove('hidden');
                document.querySelector('#step1 button').disabled = false;
                document.querySelector('#step1 button').innerHTML = 'Next: Face ID Setup →';
            }
        }

        async function startCamera() {
            const video = document.getElementById('camera');
            document.getElementById('captureBtn').classList.remove('hidden');
            document.getElementById('retryBtn').classList.add('hidden');
            document.getElementById('scanStatus').textContent = "Align face in frame...";
            
            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
                video.srcObject = stream;
            } catch (err) {
                document.getElementById('scanStatus').textContent = "Camera blocked. Enable permissions.";
            }
        }

        async function captureFace() {
            const video = document.getElementById('camera');
            const canvas = document.createElement('canvas');
            
            // Resize to reasonable dimensions (e.g. 640px loc) to prevent Upload Errors
            const MAX_WIDTH = 640;
            const scale = MAX_WIDTH / video.videoWidth;
            canvas.width = MAX_WIDTH;
            canvas.height = video.videoHeight * scale;
            
            canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
            
            const imageBase64 = canvas.toDataURL('image/jpeg', 0.7).split(',')[1];
            
            // Stop camera
            stream.getTracks().forEach(track => track.stop());
            document.getElementById('scanStatus').textContent = "Processing...";
            
            try {
                // Call Python Service directly? No, securely call secure_biometric_auth or enrollment endpoint?
                // We don't have an enrollment endpoint in PHP yet that proxies to Python.
                // Let's create one or just use `secure_biometric_auth.php`? No, secure auth requires validation.
                // We need an enroll API.
                
                // Temporary: Directly call an enroll endpoint we will creating
                const res = await fetch('../api/enroll_biometric.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        user_id: userData.userId,
                        face_data: imageBase64,
                        modality: 'face'
                    })
                });
                
                const result = await res.json();
                
                if (result.success) {
                    goToStep(3);
                    document.getElementById('finalAccount').textContent = userData.account;
                } else {
                    document.getElementById('scanStatus').textContent = "Capture failed: " + result.error;
                    document.getElementById('captureBtn').classList.add('hidden');
                    document.getElementById('retryBtn').classList.remove('hidden');
                }
                
            } catch (err) {
                document.getElementById('scanStatus').textContent = "Enrollment error.";
                document.getElementById('retryBtn').classList.remove('hidden');
            }
        }

        function goToStep(step) {
            document.getElementById('step1').classList.add('hidden');
            document.getElementById('step2').classList.add('hidden');
            document.getElementById('step3').classList.add('hidden'); // Fix typo in original thought
            document.getElementById('step' + step).classList.remove('hidden');
            
            // Headers
            if (step === 2) {
                document.getElementById('stepHeader1').classList.add('step-completed');
                document.getElementById('stepHeader2').className = "py-4 text-center border-b-2 border-cyan-400 text-cyan-400 font-bold text-sm bg-slate-800/50";
            } else if (step === 3) {
                 document.getElementById('stepHeader1').classList.add('step-completed');
                 document.getElementById('stepHeader2').classList.add('step-completed');
                 document.getElementById('stepHeader3').className = "py-4 text-center border-b-2 border-green-500 text-green-500 font-bold text-sm bg-slate-800/50";
            }
        }
    </script>
    </script>
    <script src="js/theme-system.js"></script>
</body>
</html>
