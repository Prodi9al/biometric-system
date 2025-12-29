<?php
class Session {
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            // Only set name if not already set manually (e.g. by API scripts)
            if (!session_name() || session_name() === 'PHPSESSID') {
                // Segregate Admin and User sessions
                $isAdmin = strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/') !== false;
                
                if ($isAdmin) {
                    session_name('BANK_ADMIN_SESSION');
                } else {
                    session_name('BANK_USER_SESSION');
                }
            }
            
            session_start();
        }
    }
    
    public static function set($key, $value) {
        self::start();
        $_SESSION[$key] = $value;
    }
    
    public static function get($key, $default = null) {
        self::start();
        return $_SESSION[$key] ?? $default;
    }
    
    public static function destroy() {
        self::start();
        session_destroy();
        session_unset();
    }
    
    public static function isLoggedIn() {
        $userId = self::get('user_id');
        if ($userId === null) {
            return false;
        }
        
        // If secure session exists, validate it
        $secureSessionId = self::get('secure_session_id');
        if ($secureSessionId) {
            require_once __DIR__ . '/BiometricAuth.php';
            return BiometricAuth::validateSession();
        }
        
        return true; // Legacy session valid
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: /biometric%20system/public/login.php');
            exit;
        }
    }
    
    public static function requireAdmin() {
        if (!self::isLoggedIn() || self::get('user_type') !== 'admin') {
            header('Location: /biometric%20system/admin/login.php');
            exit;
        }
    }
}
?>