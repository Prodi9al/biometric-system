import React, { useState } from 'react';
import { motion } from 'framer-motion';
import { UserPlus, Save, ScanFace, Eye, Fingerprint, CheckCircle, RefreshCw } from 'lucide-react';

// ... (Keep the CaptureCard component exactly as you wrote it) ...

export default function AdminEnroll() {
    const [captured, setCaptured] = useState({ face: false, retina: false, finger: false });
    const [formData, setFormData] = useState({ fullname: '', acc_num: '' });
    const [status, setStatus] = useState({ type: 'info', message: 'Enter details and capture biometrics to begin.' });
    const [isSaving, setIsSaving] = useState(false);
    
    // Placeholder function to simulate data capture from the sensor
    const mockCaptureData = () => "mock_data_" + Math.random().toString(36).substring(2);

    const toggleCapture = (type) => {
        // Toggle the captured state for the specific biometric
        setCaptured(prev => ({ ...prev, [type]: !prev[type] }));
        setStatus({ type: 'info', message: `Biometric ${type} status toggled.` });
    };

    const handleInputChange = (e) => {
        setFormData({ ...formData, [e.target.name]: e.target.value });
    };

    const handleSave = async (e) => {
        e.preventDefault();
        
        if (!captured.face || !captured.retina || !captured.finger || !formData.fullname || !formData.acc_num) {
            setStatus({ type: 'error', message: 'Please complete all fields and capture all biometrics.' });
            return;
        }

        setIsSaving(true);
        setStatus({ type: 'info', message: 'Saving profile and encrypting data...' });

        // Compile payload with simulated biometric data
        const payload = {
            fullname: formData.fullname,
            acc_num: formData.acc_num,
            // In a real system, these would be the encrypted templates from the sensor hardware
            face_data: mockCaptureData(), 
            retina_data: mockCaptureData(),
            finger_data: mockCaptureData(),
        };

        try {
            const response = await fetch('/api/enroll_biometric.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const result = await response.json();
            
            if (response.ok && result.status === 'success') {
                setStatus({ 
                    type: 'success', 
                    message: `SUCCESS! Customer ${result.user_id} enrolled. Ready for next customer.` 
                });
                // Reset form and capture status after success
                setFormData({ fullname: '', acc_num: '' });
                setCaptured({ face: false, retina: false, finger: false });
            } else {
                setStatus({ type: 'error', message: `Enrollment failed: ${result.message}` });
            }
        } catch (error) {
            setStatus({ type: 'error', message: `Network error: Could not connect to API.` });
        } finally {
            setIsSaving(false);
        }
    };


    return (
        <div className="min-h-screen bg-slate-950 p-8 font-sans flex items-center justify-center">
            {/* Main Container */}
            <div className="w-full max-w-5xl bg-slate-900/50 border border-slate-800 rounded-2xl overflow-hidden shadow-2xl backdrop-blur-sm">
                {/* Header */}
                {/* ... (Header remains the same) ... */}
                <div className="bg-slate-900 p-6 border-b border-slate-800 flex justify-between items-center">
                    <div>
                        <h1 className="text-2xl text-white font-bold flex items-center gap-3">
                            <UserPlus className="w-6 h-6 text-blue-400" />
                            New Customer Enrollment
                        </h1>
                        <p className="text-slate-500 text-xs uppercase tracking-widest mt-1 pl-9">Secure Biometric Registration Portal</p>
                    </div>
                    <div className="px-4 py-1 bg-blue-900/20 text-blue-400 border border-blue-500/20 rounded-full text-xs font-mono">
                        SESSION_ID: #8842_ACT
                    </div>
                </div>


                <form onSubmit={handleSave} className="p-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {/* Left Column: Basic Details Form */}
                    <div className="space-y-6">
                        <h3 className="text-slate-400 text-xs font-bold uppercase tracking-widest mb-4 border-b border-slate-800 pb-2">1. Account Details</h3>
                        
                        <div className="space-y-2">
                            <label className="text-slate-300 text-sm font-medium">Full Legal Name</label>
                            <input 
                                type="text" 
                                name="fullname"
                                placeholder="e.g. Kwame Mensah" 
                                value={formData.fullname}
                                onChange={handleInputChange}
                                className="w-full bg-slate-950 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-blue-500 transition-colors" 
                                required
                            />
                        </div>

                        <div className="space-y-2">
                            <label className="text-slate-300 text-sm font-medium">Assigned Account Number</label>
                            <div className="flex gap-2">
                                <input 
                                    type="text" 
                                    name="acc_num"
                                    placeholder="Enter 16-digit account number"
                                    value={formData.acc_num}
                                    onChange={handleInputChange}
                                    className="w-full bg-slate-950 border border-slate-700 rounded-lg px-4 py-3 text-white font-mono focus:outline-none focus:border-blue-500 transition-colors" 
                                    required
                                />
                                {/* Removed DOB and simplified acc_num generation for practical integration */}
                            </div>
                        </div>
                    </div>

                    {/* Right Column: Biometric Capture Grid */}
                    <div className="lg:col-span-2">
                        <h3 className="text-slate-400 text-xs font-bold uppercase tracking-widest mb-4 border-b border-slate-800 pb-2">2. Biometric Capture</h3>
                        
                        <div className="grid grid-cols-3 gap-4 mb-8">
                            <CaptureCard title="Facial Scan" icon={ScanFace} isCaptured={captured.face} onCapture={() => toggleCapture('face')} />
                            <CaptureCard title="Retina Scan" icon={Eye} isCaptured={captured.retina} onCapture={() => toggleCapture('retina')} />
                            <CaptureCard title="Fingerprint" icon={Fingerprint} isCaptured={captured.finger} onCapture={() => toggleCapture('finger')} />
                        </div>

                        {/* Quality Check / Console (Now reflecting current status) */}
                        <div className={`rounded-lg p-4 font-mono text-xs border ${status.type === 'error' ? 'bg-red-900/20 border-red-700 text-red-400' : status.type === 'success' ? 'bg-green-900/20 border-green-700 text-green-400' : 'bg-black/40 border-slate-800 text-blue-400'} h-32 overflow-y-auto space-y-1`}>
                            <p>{status.message}</p>
                            {isSaving && <p className="text-cyan-400 animate-pulse">[SYSTEM] Encrypting and transmitting data...</p>}
                        </div>
                    </div>

                    {/* Footer Actions */}
                    <div className="lg:col-span-3 pt-6 border-t border-slate-800 flex justify-end gap-4">
                        <button type="button" className="px-6 py-3 text-slate-400 hover:text-white font-medium transition-colors">Cancel</button>
                        <motion.button 
                            type="submit"
                            whileHover={{ scale: 1.02 }}
                            whileTap={{ scale: 0.98 }}
                            disabled={!captured.face || !captured.retina || !captured.finger || isSaving}
                            className={`px-8 py-3 rounded-lg font-bold flex items-center gap-2 shadow-lg transition-all ${
                                captured.face && captured.retina && captured.finger && !isSaving
                                ? 'bg-green-600 hover:bg-green-500 text-white shadow-green-900/20'
                                : 'bg-slate-700 text-slate-400 cursor-not-allowed'
                            }`}
                        >
                            <Save className="w-5 h-5" />
                            {isSaving ? 'Processing...' : 'SAVE PROFILE'}
                        </motion.button>
                    </div>
                </form>

            </div>
        </div>
    );
}