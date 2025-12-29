import React, { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { Banknote, CreditCard, ArrowRightLeft, LogOut, History, Wallet } from 'lucide-react';

// ... (ActionButton component remains the same) ...

export default function Dashboard() {
    const [userData, setUserData] = useState({ name: 'User', balance: '---', userId: null });
    const [alert, setAlert] = useState(null); // { type: 'success' | 'error', message: '...' }

    // Placeholder function to extract URL parameter (like user_id)
    const getUrlParameter = (name) => {
        name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
        const regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
        const results = regex.exec(window.location.search);
        return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
    };

    // --- Data Fetching on Load ---
    useEffect(() => {
        const userId = getUrlParameter('user_id');
        if (userId) {
            // Immediately fetch the user's details and current balance
            fetchBalance(userId);
            setUserData(prev => ({ ...prev, userId }));
        }
    }, []);

    const fetchBalance = async (userId) => {
        try {
            const response = await fetch(`/api/transaction.php?user_id=${userId}&type=Balance_Check`);
            const result = await response.json();

            if (response.ok && result.status === 'success') {
                setUserData(prev => ({
                    ...prev,
                    name: result.full_name || 'Customer',
                    balance: parseFloat(result.current_balance).toFixed(2),
                }));
            } else {
                setAlert({ type: 'error', message: 'Failed to load account data.' });
            }
        } catch (error) {
            setAlert({ type: 'error', message: 'Network error during balance check.' });
        }
    };

    // --- Transaction Handlers ---
    const handleTransaction = async (type) => {
        if (!userData.userId) return setAlert({ type: 'error', message: 'No active session (User ID missing).' });
        setAlert(null); // Clear previous alerts

        if (type === 'Balance_Check') {
            await fetchBalance(userData.userId);
            setAlert({ type: 'success', message: `Current Balance: ₵${userData.balance}` });
        
        } else if (type === 'Withdrawal') {
            // SIMULATION: Prompt for an amount
            const amountStr = prompt("Enter amount to withdraw (e.g., 100):");
            const amount = parseFloat(amountStr);
            
            if (isNaN(amount) || amount <= 0) return setAlert({ type: 'error', message: 'Invalid amount entered.' });
            
            const payload = {
                user_id: userData.userId,
                type: 'Withdrawal',
                amount: amount
            };

            try {
                const response = await fetch('/api/transaction.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                
                const result = await response.json();

                if (response.ok && result.status === 'success') {
                    setAlert({ type: 'success', message: `SUCCESS! ${result.message}` });
                    // Update balance immediately after successful withdrawal
                    setUserData(prev => ({ ...prev, balance: parseFloat(result.new_balance).toFixed(2) })); 
                } else {
                    setAlert({ type: 'error', message: `Transaction failed: ${result.message}` });
                }
            } catch (error) {
                setAlert({ type: 'error', message: 'Network error during withdrawal.' });
            }
        }
    };

    const handleLogout = () => {
        // Clear session logic and redirect to the welcome/login page
        window.location.href = '/authenticate.php';
    };

    return (
        <div className="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-blue-950 flex items-center justify-center p-4 font-sans">
            <div className="w-full max-w-4xl aspect-[4/3] bg-gradient-to-br from-slate-900 to-slate-950 rounded-2xl shadow-2xl border border-slate-800 overflow-hidden relative flex flex-col">
                {/* ... (Background Decor remains the same) ... */}
                
                {/* Header */}
                <div className="p-8 border-b border-slate-800/50 flex justify-between items-center bg-slate-900/50 backdrop-blur-md">
                    <div>
                        <motion.p initial={{ opacity: 0, x: -20 }} animate={{ opacity: 1, x: 0 }} className="text-slate-400 text-xs uppercase tracking-widest mb-1">Current Session</motion.p>
                        <motion.h1 initial={{ opacity: 0, x: -20 }} animate={{ opacity: 1, x: 0 }} transition={{ delay: 0.1 }} className="text-2xl text-white font-bold tracking-wide">Welcome, <span className="text-cyan-400">{userData.name}</span></motion.h1>
                    </div>
                    <motion.div initial={{ opacity: 0, scale: 0.8 }} animate={{ opacity: 1, scale: 1 }} transition={{ delay: 0.2 }} className="px-6 py-2 bg-slate-800 rounded-full border border-slate-700 flex items-center gap-3">
                        <div className="w-2 h-2 rounded-full bg-green-500 animate-pulse" />
                        <span className="text-slate-300 text-sm font-mono">SECURE CONNECTION</span>
                    </motion.div>
                </div>

                {/* Alert/Status Message */}
                {alert && (
                    <motion.div initial={{ opacity: 0, y: -20 }} animate={{ opacity: 1, y: 0 }} className={`mx-8 mt-4 p-3 rounded-lg text-sm font-medium ${alert.type === 'error' ? 'bg-red-900/40 text-red-300 border border-red-800' : 'bg-green-900/40 text-green-300 border border-green-800'}`}>
                        {alert.message}
                        {alert.type === 'success' && type !== 'Balance_Check' && 
                            <span className="font-bold ml-2">Current Balance: ₵{userData.balance}</span>
                        }
                    </motion.div>
                )}


                {/* Grid */}
                <div className="flex-1 p-8 grid grid-cols-3 gap-6">
                    <div onClick={() => handleTransaction('Withdrawal')}><ActionButton icon={Banknote} label="Withdraw Cash" delay={0.3} /></div>
                    <div onClick={() => handleTransaction('Balance_Check')}><ActionButton icon={Wallet} label="Check Balance" delay={0.4} /></div>
                    <div onClick={() => handleTransaction('Deposit')}><ActionButton icon={CreditCard} label="Deposit Funds" delay={0.5} /></div>
                    <ActionButton icon={ArrowRightLeft} label="Transfer" delay={0.6} />
                    <ActionButton icon={History} label="History" delay={0.7} />
                    {/* Quick Cash Button - You can link this to a handleTransaction('QuickCash', 50) */}
                    <motion.button initial={{ opacity: 0, scale: 0.9 }} animate={{ opacity: 1, scale: 1 }} transition={{ duration: 0.4, delay: 0.8 }} whileHover={{ scale: 1.05 }} whileTap={{ scale: 0.95 }} className="flex flex-col items-center justify-center p-6 bg-gradient-to-br from-cyan-900/20 to-blue-900/20 border border-cyan-500/30 rounded-2xl hover:border-cyan-400 transition-all group shadow-lg">
                        <span className="text-3xl font-bold text-cyan-400 mb-2">₵50</span>
                        <span className="text-cyan-200/70 font-medium tracking-wide uppercase text-sm">Quick Cash</span>
                    </motion.button>
                </div>

                {/* Footer */}
                <div className="p-8 border-t border-slate-800/50 flex justify-between items-center bg-slate-900/30">
                    <p className="text-slate-500 text-xs">Terminal ID: GH-ACC-042</p>
                    <motion.button onClick={handleLogout} whileHover={{ scale: 1.05, backgroundColor: 'rgba(239, 68, 68, 0.2)' }} whileTap={{ scale: 0.95 }} className="flex items-center gap-3 px-8 py-3 bg-red-500/10 border border-red-500/30 rounded-full text-red-400 hover:text-red-300 transition-all uppercase text-sm font-bold tracking-wider">
                        <LogOut className="w-4 h-4" /> Log Out
                    </motion.button>
                </div>
            </div>
        </div>
    );
}