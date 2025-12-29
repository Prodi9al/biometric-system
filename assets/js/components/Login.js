import React, { useState, useEffect, useRef, useCallback } from 'react';
import { motion } from 'framer-motion';
import { ScanFace, Fingerprint, Eye, Smartphone } from 'lucide-react';

// Status constants
const STATUS = {
    WAITING: 'Waiting',
    CAPTURING: 'Capturing...',
    VERIFYING: 'Verifying...',
    SUCCESS: 'Matched',
    FAIL: 'Failed',
    DISABLED: 'App Required'
};

// --- REAL COMPONENT: CameraFrame ---
const CameraFrame = ({ onCapture, isScanning }) => {
    const videoRef = useRef(null);
    const canvasRef = useRef(null);
    const [stream, setStream] = useState(null);

    // Initialize Camera
    useEffect(() => {
        const startCamera = async () => {
            try {
                const mediaStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
                setStream(mediaStream);
                if (videoRef.current) {
                    videoRef.current.srcObject = mediaStream;
                }
            } catch (err) {
                console.error("Camera Access Error:", err);
                alert("Camera access denied. Please allow camera usage.");
            }
        };

        startCamera();

        return () => {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
        };
    }, []);

    // Capture Frame method exposed via prop or effect
    useEffect(() => {
        if (isScanning && videoRef.current && canvasRef.current) {
            const context = canvasRef.current.getContext('2d');
            if (context) {
                const { videoWidth, videoHeight } = videoRef.current;
                canvasRef.current.width = videoWidth;
                canvasRef.current.height = videoHeight;
                context.drawImage(videoRef.current, 0, 0, videoWidth, videoHeight);

                // Compress to JPEG 0.7 quality
                const dataUrl = canvasRef.current.toDataURL('image/jpeg', 0.7);
                // Strip prefix to get raw base64
                const base64 = dataUrl.split(',')[1];
                onCapture(base64);
            }
        }
    }, [isScanning, onCapture]);

    return (
        <div className="relative w-full h-full bg-black rounded-lg overflow-hidden border-2 border-slate-700">
            <video
                ref={videoRef}
                autoPlay
                playsInline
                muted
                className="w-full h-full object-cover"
            />
            <canvas ref={canvasRef} className="hidden" />

            {/* Overlay */}
            <div className="absolute inset-0 border border-cyan-500/30 rounded-lg pointer-events-none"></div>
            {isScanning && (
                <div className="absolute inset-0 bg-cyan-500/10 animate-pulse pointer-events-none"></div>
            )}
        </div>
    );
};

// --- COMPONENT: BiometricStatus ---
const BiometricStatus = ({ icon, status, active }) => {
    const getIcon = () => {
        switch (icon) {
            case 'facial': return <ScanFace size={24} />;
            case 'retina': return <Eye size={24} />;
            case 'fingerprint': return <Fingerprint size={24} />;
            default: return null;
        }
    };

    // Color logic
    let colorClass = "text-slate-600 border-slate-700 bg-slate-900/50";
    if (status === STATUS.SUCCESS) colorClass = "text-green-400 border-green-500 bg-green-900/20";
    if (status === STATUS.FAIL) colorClass = "text-red-400 border-red-500 bg-red-900/20";
    if (status === STATUS.DISABLED) colorClass = "text-slate-700 border-slate-800 bg-slate-950/30 opacity-70";
    if (active) colorClass = "text-cyan-400 border-cyan-500 bg-cyan-900/20 animate-pulse";

    return (
        <div className={`flex flex-col items-center gap-2 p-4 rounded-xl border-2 transition-all duration-300 ${colorClass}`}>
            <div className="p-2 rounded-full bg-slate-950/30">
                {getIcon()}
            </div>
            <div className="text-xs uppercase tracking-widest font-bold">
                {status}
            </div>
        </div>
    );
};


// --- MAIN LOGIN PAGE ---
export default function Login() {
    const [authStatus, setAuthStatus] = useState({
        facial: STATUS.WAITING,
        retina: STATUS.DISABLED, // Not supported on web
        fingerprint: STATUS.DISABLED, // Not supported on web without WebAuthn
        isAuthenticating: false,
        isScanning: false, // Triggers camera capture
        message: 'Align yourself in the frame to begin.',
        acc_num: '', // Will prompt or use default
        userId: null
    });

    // Initialize user ID lookup
    const lookupUser = async (account) => {
        try {
            const response = await fetch('/api/get-user-id.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ account_number: account })
            });
            const data = await response.json();
            return data.success ? data.user_id : null;
        } catch (e) {
            console.error("User lookup failed", e);
            return null;
        }
    };

    const handleCameraCapture = useCallback(async (base64Image) => {
        // Stop scanning immediately to prevent multiple captures
        setAuthStatus(prev => ({ ...prev, isScanning: false, message: 'Processing Biometric Data...' }));

        try {
            // 1. Get User ID if not present
            let currentUserId = authStatus.userId;
            const currentAccount = authStatus.acc_num || '2023 8841 9920 1102'; // Default for demo

            if (!currentUserId) {
                currentUserId = await lookupUser(currentAccount);
                if (!currentUserId) {
                    throw new Error("User account not found. Please register.");
                }
            }

            // 2. Call SECURE Biometric API
            const payload = {
                user_id: currentUserId,
                biometric_data: base64Image,
                modality: 'face', // STRICTLY FACE
                nonce: Date.now().toString()
            };

            const response = await fetch('/api/secure_biometric_auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const result = await response.json();

            if (result.success) {
                setAuthStatus(prev => ({
                    ...prev,
                    userId: currentUserId,
                    facial: STATUS.SUCCESS,
                    // Fingerprint remains DISABLED as it wasn't checked
                    message: `Face Verified! Confidence: ${result.confidence}%`,
                }));

                // Redirect
                setTimeout(() => {
                    window.location.href = result.redirect || '/dashboard-modern.php';
                }, 1500);
            } else {
                throw new Error(result.error || "Biometric validation failed.");
            }

        } catch (error) {
            setAuthStatus(prev => ({
                ...prev,
                facial: STATUS.FAIL,
                isAuthenticating: false,
                message: error.message || "Authentication Failed"
            }));
        }

    }, [authStatus.userId, authStatus.acc_num]);

    // Start Authentication Flow
    const startAuthentication = () => {
        if (authStatus.isAuthenticating) return;

        setAuthStatus(prev => ({
            ...prev,
            isAuthenticating: true,
            isScanning: true, // This triggers the CameraFrame to capture
            facial: STATUS.VERIFYING,
            message: 'Scanning Face...'
        }));
    };

    return (
        <div className="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-blue-950 flex items-center justify-center p-4 font-sans">
            <div className="w-full max-w-4xl aspect-[4/3] bg-gradient-to-br from-slate-900 to-slate-950 rounded-2xl shadow-2xl border border-slate-800 overflow-hidden relative">

                <div className="h-full flex flex-col p-8 relative z-10">
                    <div className="mb-4 shrink-0">
                        <motion.div initial={{ opacity: 0, y: -20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.6 }} className="text-center">
                            <h1 className="text-3xl tracking-[0.2em] text-cyan-400 uppercase font-bold drop-shadow-[0_0_10px_rgba(34,211,238,0.3)]">Biometric Access</h1>
                            <p className="text-slate-400 text-sm mt-2 tracking-widest uppercase">{authStatus.message}</p>
                        </motion.div>
                    </div>

                    {/* Camera Viewport */}
                    <motion.div initial={{ opacity: 0, scale: 0.95 }} animate={{ opacity: 1, scale: 1 }} transition={{ duration: 0.6, delay: 0.2 }} className="flex-1 flex items-center justify-center mb-6 min-h-0">
                        <div className="h-full aspect-square max-h-[380px]">
                            <CameraFrame
                                isScanning={authStatus.isScanning}
                                onCapture={handleCameraCapture}
                            />
                        </div>
                    </motion.div>

                    {/* Status Indicators */}
                    <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.6, delay: 0.4 }} className="flex justify-center gap-12 mb-8 shrink-0">
                        <BiometricStatus icon="facial" status={authStatus.facial} active={authStatus.facial === STATUS.VERIFYING} />
                        <BiometricStatus icon="fingerprint" status={authStatus.fingerprint} active={false} />
                    </motion.div>

                    {/* Controls */}
                    <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.6, delay: 0.6 }} className="flex justify-center gap-6 shrink-0">
                        <motion.button
                            onClick={startAuthentication}
                            disabled={authStatus.isAuthenticating}
                            whileHover={{ scale: 1.05 }}
                            whileTap={{ scale: 0.95 }}
                            className={`px-12 py-4 bg-blue-600/80 hover:bg-blue-500/80 text-white rounded-full transition-all duration-300 backdrop-blur-sm uppercase tracking-[0.15em] font-semibold ${authStatus.isAuthenticating ? 'opacity-50 cursor-not-allowed' : ''}`}
                        >
                            {authStatus.isAuthenticating ? 'Scanning...' : 'Authenticate'}
                        </motion.button>
                    </motion.div>

                    {/* Platform Note */}
                    <div className="absolute bottom-4 right-4 text-xs text-slate-600 flex items-center gap-2">
                        <Smartphone size={16} />
                        <span>Mobile App Required for Fingerprint</span>
                    </div>
                </div>
            </div>
        </div>
    );
}