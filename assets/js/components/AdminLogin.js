import React, { useState } from 'react';
import { motion } from 'framer-motion';
import { ShieldCheck, Lock, User, Server, AlertTriangle } from 'lucide-react';

export default function AdminLogin() {
    const [formData, setFormData] = useState({ admin_id: '', password: '' });
    const [status, setStatus] = useState(null); // {type: 'error', message: '...'}
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleInputChange = (e) => {
        setFormData({ ...formData, [e.target.name]: e.target.value });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setStatus(null); // Clear previous status
        setIsSubmitting(true);

        // Simple validation check
        if (!formData.admin_id || !formData.password) {
            setStatus({ type: 'error', message: 'Please enter both Admin ID and Password.' });
            setIsSubmitting(false);
            return;
        }

        try {
            const response = await fetch('/api/admin_auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            if (response.ok && result.status === 'success') {
                // SUCCESS: Redirect to Admin Dashboard
                window.location.href = `/admin/dashboard.php?admin_id=${result.admin_id}`; 
            } else {
                // FAILURE
                setStatus({ type: 'error', message: result.message || 'Authentication Failed.' });
            }

        } catch (error) {
            setStatus({ type: 'error', message: 'Network Error. Could not connect to the Admin Server.' });
        } finally {
            setIsSubmitting(false);
        }
    };
    
    return (
        <div className="min-h-screen bg-slate-950 flex items-center justify-center p-4 font-sans">
            <div className="w-full max-w-md bg-slate-900 border border-slate-800 rounded-xl shadow-2xl overflow-hidden relative">
                <div className="h-1 w-full bg-gradient-to-r from-blue-600 via-cyan-500 to-blue-600" />
                <div className="p-8">
                    <div className="text-center mb-8">
                        <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-900/20 mb-4 border border-blue-500/30">
                            <ShieldCheck className="w-8 h-8 text-blue-400" />
                        </div>
                        <h1 className="text-xl text-white font-bold tracking-wide">System Administration</h1>
                        <p className="text-slate-500 text-xs uppercase tracking-widest mt-2">Authorized Personnel Only</p>
                    </div>
                    
                    {/* Status Alert */}
                    {status && (
                        <div className="flex items-center gap-2 p-3 mb-4 text-sm font-medium bg-red-900/30 border border-red-700/50 text-red-300 rounded-lg">
                            <AlertTriangle className="w-5 h-5" /> {status.message}
                        </div>
                    )}
                    
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="relative group">
                            <div className="absolute left-4 top-3.5 text-slate-500 group-focus-within:text-blue-400 transition-colors"><User className="w-5 h-5" /></div>
                            <input 
                                type="text" 
                                name="admin_id"
                                placeholder="Admin ID (e.g., STF-2023-XXX)" 
                                value={formData.admin_id}
                                onChange={handleInputChange}
                                disabled={isSubmitting}
                                className="w-full bg-slate-950 border border-slate-700 text-white pl-12 pr-4 py-3 rounded-lg focus:outline-none focus:border-blue-500 transition-colors placeholder:text-slate-600" 
                            />
                        </div>
                        <div className="relative group">
                            <div className="absolute left-4 top-3.5 text-slate-500 group-focus-within:text-blue-400 transition-colors"><Lock className="w-5 h-5" /></div>
                            <input 
                                type="password" 
                                name="password"
                                placeholder="Secure Token / Password" 
                                value={formData.password}
                                onChange={handleInputChange}
                                disabled={isSubmitting}
                                className="w-full bg-slate-950 border border-slate-700 text-white pl-12 pr-4 py-3 rounded-lg focus:outline-none focus:border-blue-500 transition-colors placeholder:text-slate-600" 
                            />
                        </div>
                        <motion.button 
                            type="submit"
                            disabled={isSubmitting}
                            whileHover={{ scale: 1.02 }} 
                            whileTap={{ scale: 0.98 }} 
                            className={`w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-lg shadow-lg shadow-blue-900/20 transition-all mt-2 ${isSubmitting ? 'opacity-50 cursor-not-allowed' : ''}`}
                        >
                            {isSubmitting ? 'Verifying...' : 'ACCESS CONTROL PANEL'}
                        </motion.button>
                    </form>
                    
                    {/* Footer Status */}
                    <div className="mt-8 pt-6 border-t border-slate-800 flex items-center justify-between text-xs text-slate-500">
                        <div className="flex items-center gap-2"><div className="w-2 h-2 rounded-full bg-green-500 animate-pulse" /><span>Server Online</span></div>
                        <div className="flex items-center gap-1"><Server className="w-3 h-3" /><span>v.4.2.0 (Secure)</span></div>
                    </div>
                </div>
            </div>
        </div>
    );
}