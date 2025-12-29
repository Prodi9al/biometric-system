<?php
// Main landing page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ghana Secure Bank - Biometric ATM System</title>
    <link rel="stylesheet" href="assets/css/banking.css">
    <style>
        .hero {
            text-align: center;
            padding: 4rem 2rem;
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(124, 58, 237, 0.1) 100%);
            border-radius: 20px;
            margin-bottom: 3rem;
        }
        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #1e293b;
        }
        .hero p {
            font-size: 1.2rem;
            color: #64748b;
            margin-bottom: 2rem;
        }
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        .feature {
            text-align: center;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="hero">
            <h1>🏦 Ghana Secure Bank</h1>
            <p>Advanced Biometric ATM System</p>
            <p style="font-size: 1rem; color: #6b7280;">Secure • Fast • Reliable Banking Experience</p>
            
            <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem; flex-wrap: wrap;">
                <a href="public/login.php" class="btn btn-primary">Customer Login</a>
                <a href="public/atm-login-sequential.php" class="btn btn-success">Biometric Login</a>
                <a href="admin/login-modern.php" class="btn btn-secondary">Admin Portal</a>
            </div>
        </div>
        
        <div class="features">
            <div class="feature">
                <div class="feature-icon">🧬</div>
                <h3>Biometric Security</h3>
                <p>Advanced face recognition and fingerprint scanning for maximum security</p>
            </div>
            
            <div class="feature">
                <div class="feature-icon">⚡</div>
                <h3>Fast Transactions</h3>
                <p>Quick and efficient banking operations with real-time processing</p>
            </div>
            
            <div class="feature">
                <div class="feature-icon">🔒</div>
                <h3>Bank-Grade Encryption</h3>
                <p>AES-256 encryption protects all your sensitive financial data</p>
            </div>
            
            <div class="feature">
                <div class="feature-icon">📱</div>
                <h3>Modern Interface</h3>
                <p>Intuitive and user-friendly design for seamless banking experience</p>
            </div>
            
            <div class="feature">
                <div class="feature-icon">🌍</div>
                <h3>24/7 Availability</h3>
                <p>Access your account anytime, anywhere with our reliable system</p>
            </div>
            
            <div class="feature">
                <div class="feature-icon">📊</div>
                <h3>Real-time Monitoring</h3>
                <p>Live transaction tracking and comprehensive audit trails</p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">System Status</h2>
            </div>
            <div class="grid grid-3">
                <div style="text-align: center;">
                    <div style="font-size: 2rem; color: #10b981; margin-bottom: 0.5rem;">✅</div>
                    <h4>ATM Network</h4>
                    <p style="color: #10b981;">Online</p>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 2rem; color: #10b981; margin-bottom: 0.5rem;">🔐</div>
                    <h4>Security Systems</h4>
                    <p style="color: #10b981;">Active</p>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 2rem; color: #10b981; margin-bottom: 0.5rem;">🧬</div>
                    <h4>Biometric Services</h4>
                    <p style="color: #10b981;">Operational</p>
                </div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 3rem; padding: 2rem; color: #6b7280;">
            <p>&copy; 2024 Ghana Secure Bank. All rights reserved.</p>
            <p style="font-size: 0.9rem;">Powered by Advanced Biometric Technology</p>
        </div>
    </div>
</body>
</html>