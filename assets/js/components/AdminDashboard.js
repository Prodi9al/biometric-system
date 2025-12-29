import React, { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { Users, Activity, Banknote, AlertTriangle, UserPlus, Settings, RefreshCw, LogOut } from 'lucide-react';

// --- StatCard Component --- (Using props from state)
const StatCard = ({ title, value, sub, icon: Icon, color }) => (
    <div className="bg-slate-900 border border-slate-800 p-6 rounded-xl relative overflow-hidden">
        <div className={`absolute top-0 right-0 p-4 opacity-10 ${color}`}><Icon className="w-16 h-16" /></div>
        <div className="relative z-10">
            <p className="text-slate-400 text-xs uppercase tracking-widest mb-1">{title}</p>
            <h3 className="text-2xl font-bold text-white mb-1">{value}</h3>
            <p className={`text-xs ${color.replace('text-', 'text-opacity-80 ')}`}>{sub}</p>
        </div>
    </div>
);

// --- CashCassette Component ---
const CashCassette = ({ label, amount, percentage }) => (
    <div className="mb-4">
        <div className="flex justify-between text-xs text-slate-400 mb-1"><span>{label}</span><span>₵ {amount.toLocaleString()}</span></div>
        <div className="h-2 w-full bg-slate-800 rounded-full overflow-hidden">
            <motion.div 
                initial={{ width: 0 }} 
                animate={{ width: `${percentage}%` }} 
                transition={{ duration: 1 }} 
                className={`h-full rounded-full ${percentage < 20 ? 'bg-red-500' : 'bg-green-500'}`} 
            />
        </div>
    </div>
);

// --- MAIN DASHBOARD (UPDATED) ---
export default function AdminDashboard() {
    const [stats, setStats] = useState({
        total_users: '---',
        biometric_accuracy: '---',
        total_cash: '---',
        security_alerts: '---',
        cash_levels: []
    });
    const [isLoading, setIsLoading] = useState(true);

    const fetchData = async () => {
        setIsLoading(true);
        try {
            const response = await fetch('/api/admin_health.php');
            const result = await response.json();
            
            if (response.ok && result.status === 'success') {
                const data = result.data;
                setStats({
                    total_users: data.total_users.toLocaleString(),
                    biometric_accuracy: data.biometric_accuracy.toFixed(1) + '%',
                    total_cash: `₵ ${data.total_cash.toLocaleString()}`,
                    security_alerts: data.security_alerts.toLocaleString(),
                    cash_levels: data.cash_levels
                });
            } else {
                 console.error("Failed to fetch data:", result.message);
                 setStats(prev => ({ ...prev, total_users: 'Error', security_alerts: 'Error' }));
            }
        } catch (error) {
            console.error("Network error fetching dashboard data:", error);
            setStats(prev => ({ ...prev, total_users: 'Network Error', security_alerts: 'N/A' }));
        } finally {
            setIsLoading(false);
        }
    };

    // Fetch data on component mount
    useEffect(() => {
        fetchData();
    }, []);
    
    // Function to handle redirection to Enrollment
    const handleEnrollmentClick = () => {
        window.location.href = '/admin/enroll_user.php';
    };

    // Function to handle Logout (Redirects to Admin Login)
    const handleLogout = () => {
        // Clear admin session/token here (in a real app)
        window.location.href = '/admin/admin_login.php';
    };

    return (
        <div className="min-h-screen bg-slate-950 p-6 font-sans text-slate-200">
            {/* Header */}
            <header className="flex justify-between items-center mb-8 pb-6 border-b border-slate-800">
                <div>
                    <h1 className="text-2xl font-bold text-white">System Dashboard</h1>
                    <p className="text-slate-500 text-xs uppercase tracking-widest">Biometric ATM Network / Terminal #042</p>
                </div>
                <div className="flex items-center gap-4">
                    <span className="px-3 py-1 bg-green-500/10 text-green-400 border border-green-500/20 rounded-full text-xs font-bold">SYSTEM HEALTHY</span>
                    <button onClick={handleLogout} className="w-10 h-10 bg-red-900/20 text-red-400 rounded-full flex items-center justify-center border border-red-500/30 hover:bg-red-900/50 transition-colors"><LogOut className="w-5 h-5" /></button>
                </div>
            </header>
            
            {isLoading && <p className="text-center text-cyan-400 animate-pulse">Loading real-time data...</p>}

            <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <StatCard title="Total Users" value={stats.total_users} sub="+24 this week" icon={Users} color="text-blue-500" />
                <StatCard title="Biometric Accuracy" value={stats.biometric_accuracy} sub={`${stats.frr}% FRR`} icon={Activity} color="text-green-500" />
                <StatCard title="Total Cash" value={stats.total_cash} sub="82% Capacity" icon={Banknote} color="text-cyan-500" />
                <StatCard title="Security Alerts" value={stats.security_alerts} sub="Failed attempts (Last 7 Days)" icon={AlertTriangle} color={stats.security_alerts > 0 ? "text-red-500" : "text-green-500"} />
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div className="lg:col-span-2 space-y-6">
                    {/* Administrative Actions Block */}
                    <div className="bg-slate-900 border border-slate-800 rounded-xl p-6">
                        <h3 className="text-white font-bold mb-6 flex items-center gap-2"><UserPlus className="w-5 h-5 text-blue-400" /> Administrative Actions</h3>
                        <div className="grid grid-cols-2 gap-4">
                            <button onClick={handleEnrollmentClick} className="p-4 bg-blue-600/10 border border-blue-500/30 hover:bg-blue-600/20 rounded-lg text-left transition-colors group">
                                <span className="block text-blue-400 font-bold mb-1 group-hover:text-blue-300">Enroll New Customer</span>
                                <span className="text-xs text-slate-500">Capture Face, Retina, Fingerprint</span>
                            </button>
                            <button className="p-4 bg-slate-800/50 border border-slate-700 hover:bg-slate-800 rounded-lg text-left transition-colors">
                                <span className="block text-white font-bold mb-1">View Audit Logs</span>
                                <span className="text-xs text-slate-500">Check recent access attempts</span>
                            </button>
                            {/* Run Diagnostics/Update Firmware removed for simplicity */}
                        </div>
                    </div>
                </div>

                {/* Cash Levels Block */}
                <div className="bg-slate-900 border border-slate-800 rounded-xl p-6">
                    <div className="flex justify-between items-center mb-6">
                        <h3 className="text-white font-bold flex items-center gap-2"><Banknote className="w-5 h-5 text-cyan-400" /> Cash Levels</h3>
                        <button onClick={fetchData} className="p-2 hover:bg-slate-800 rounded-full transition-colors"><RefreshCw className="w-4 h-4 text-slate-500" /></button>
                    </div>
                    <div className="space-y-6">
                        {stats.cash_levels.map((cassette, index) => (
                            <CashCassette 
                                key={index}
                                label={cassette.label} 
                                amount={cassette.amount} 
                                percentage={cassette.percentage} 
                            />
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
}