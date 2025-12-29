<?php
/**
 * Secure Biometric Authentication Handler
 * Implements challenge-response protocol with HMAC verification
 */
class BiometricAuth {
    private static function getSecret(): string {
        // CRITICAL: SHARED_SECRET must be loaded from environment.
        // DO NOT hardcode this value. DO NOT log this value.
        // It is used for HMAC signing between PHP and Python services.
        $secret = getenv('SHARED_SECRET');
        // INVARIANT: Secret must be present, non-empty, and meet minimum length (32 chars)
        if (!$secret || strlen(trim($secret)) < 32) {
            throw new Exception('CRITICAL: SHARED_SECRET invalid or too short (must be >= 32 bytes).');
        }
        return trim($secret); // Treat return as immutable. Trim to match Python.
    }
    private const SERVICE_URL = 'http://127.0.0.1:5000/verify'; // Force IPv4
    private const NONCE_EXPIRY = 300; // 5 minutes
    
    private static function logTrace($msg) {
        file_put_contents(__DIR__ . '/../admin/debug_trace.txt', date('[H:i:s] [Class] ') . $msg . "\n", FILE_APPEND);
    }
    
    /**
     * Generate cryptographically secure nonce for challenge-response
     */
    public static function generateNonce(): string {
        return bin2hex(random_bytes(32)); // 64-char hex string
    }
    
    /**
     * Initiate biometric verification with challenge-response
     * @param int $userId User ID for session binding
     * @param string $biometricData Base64 encoded biometric capture
     * @param string $modality 'face' or 'fingerprint'
     * @return array Authentication result with session data
     */
    public static function verifyBiometric(int $userId, string $biometricData, string $modality): array {
        // FAIL-SECURE: Default to failure state
        $authResult = ['success' => false, 'error' => 'Authentication failed'];
        
        try {
            $nonce = self::generateNonce();
            $timestamp = time();
            
            // Store nonce temporarily for verification (prevent replay attacks)
            $_SESSION['auth_nonce'] = $nonce;
            $_SESSION['auth_timestamp'] = $timestamp;
            
            $payload = [
                'user_id' => $userId,
                'biometric_data' => $biometricData,
                'modality' => $modality,
                'nonce' => $nonce,
                'timestamp' => $timestamp
            ];
            
            // Send secure request to Python service
            $response = self::sendSecureRequest($payload);
            
            // FAIL-SECURE: Any failure in the chain results in authentication failure
            if (!$response) {
                self::logAuthAttempt($userId, 'FAILED', $modality, 'Service unavailable');
                return $authResult; // Explicit failure
            }
            
            // Verify response integrity - must pass all checks
            if (!self::verifyResponse($response, $nonce)) {
                self::logAuthAttempt($userId, 'FAILED', $modality, 'Invalid response signature');
                return $authResult; // Explicit failure
            }
            
            // FAIL-SECURE: Only succeed if ALL conditions are met
            if (isset($response['authenticated']) && 
                $response['authenticated'] === true && 
                isset($response['confidence']) && 
                $response['confidence'] >= 50.0) {
                
                // Create secure session binding
                $sessionData = self::createSecureSession($userId, $modality, $response['confidence']);
                self::logAuthAttempt($userId, 'SUCCESS', $modality, "Confidence: {$response['confidence']}%");
                
                // Only return success after all operations complete successfully
                $authResult = [
                    'success' => true,
                    'session_id' => $sessionData['session_id'],
                    'confidence' => $response['confidence'],
                    'expires_at' => $sessionData['expires_at']
                ];
            } else {
                $reason = $response['error'] ?? 'Biometric verification failed or insufficient confidence';
                if ($response['confidence'] > 0) {
                     $reason .= " (Confidence: {$response['confidence']}%)";
                }
                self::logAuthAttempt($userId, 'FAILED', $modality, $reason);
                $authResult['error'] = $reason;
            }
            
        } catch (Exception $e) {
            self::logAuthAttempt($userId, 'ERROR', $modality, $e->getMessage());
        } finally {
            // Always clean up temporary nonce data
            unset($_SESSION['auth_nonce'], $_SESSION['auth_timestamp']);
        }
        
        return $authResult;
    }
    
    /**
     * Send secure HTTP request to Python biometric service
     */
    private static function sendSecureRequest(array $payload): ?array {
        try {
            self::logTrace("Sending request to Python Service: " . self::SERVICE_URL);
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/json',
                    'content' => json_encode($payload),
                    'timeout' => 30,
                    'ignore_errors' => true // Allow reading 400/500 responses
                ]
            ]);
            
            $response = @file_get_contents(self::SERVICE_URL, false, $context);
            
            // FAIL-SECURE: Any communication failure returns null
            if ($response === false || empty($response)) {
                self::logTrace("Connection Failed! Response was false/empty. Check if start_biometric_service.bat is running.");
                $error = error_get_last();
                if ($error) self::logTrace("PHP Error: " . $error['message']);
                return null;
            }
            
            self::logTrace("Received response from Python: " . substr($response, 0, 500) . "...");
            
            $decoded = json_decode($response, true);
            
            // Log service errors if present
            if (isset($decoded['error'])) {
                 error_log("Biometric Service Error: " . $decoded['error']);
                 // If it's a validation error (400), we might want to return it to help debug?
                 // But for security, we keep returning null to the caller, effectively failing auth.
                 // The 'details' in logAuthAttempt will capture the generic 'Service unavailable' or similar,
                 // but checking the PHP error log is key here.
            }
            
            // FAIL-SECURE: Invalid JSON returns null
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                return null;
            }
            
            return $decoded;
            
        } catch (Exception $e) {
            error_log("Secure request error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Verify HMAC signature of INCOMING request (Frontend -> PHP)
     * Phase 1 Verification
     */
    public static function verifyRequestSignature(array $request): bool {
        try {
            $required = ['user_id', 'biometric_data', 'modality', 'nonce', 'timestamp', 'signature'];
            foreach ($required as $field) {
                if (!array_key_exists($field, $request) || $request[$field] === null) return false;
            }

            // Verify Timestamp (Freshness)
            if (abs(time() - (int)$request['timestamp']) > self::NONCE_EXPIRY) return false;

            // Reconstruct Canonical Payload
            $payload = [
                'user_id' => (int)$request['user_id'],
                'biometric_data' => (string)$request['biometric_data'],
                'modality' => (string)$request['modality'],
                'nonce' => (string)$request['nonce'],
                'timestamp' => (int)$request['timestamp']
            ];
            
            ksort($payload);
            $canonicalJson = json_encode($payload);
            
            $expected = hash_hmac('sha256', $canonicalJson, self::getSecret());
            
            return hash_equals($expected, $request['signature']);

        } catch (Exception $e) {
            error_log("Request verification error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify HMAC signature and timestamp from Python service response
     * Phase 2 Verification
     */
    private static function verifyResponse(array $response, string $expectedNonce): bool {
        // FAIL-SECURE: Default to invalid response
        try {
            // Check ALL required fields exist and are not empty
            // Note: 'authenticated' can be false, so empty check needs care strictly for existence
            $requiredFields = ['authenticated', 'nonce', 'timestamp', 'signature'];
            foreach ($requiredFields as $field) {
                if (!array_key_exists($field, $response) || $response[$field] === null) {
                    return false;
                }
            }
            
            // Verify nonce matches exactly (prevents replay attacks)
            if (!hash_equals($response['nonce'], $expectedNonce)) {
                return false;
            }
            
            // Verify timestamp is recent and valid (prevents replay attacks)
            $responseTime = (int)$response['timestamp'];
            $currentTime = time();
            if (abs($currentTime - $responseTime) > self::NONCE_EXPIRY) {
                return false;
            }
            
            // Reconstruct canonical payload for signature verification
            // MUST MATCH Python's canonicalization rules exactly
            $payload = [
                'authenticated' => (bool)$response['authenticated'], // Ensure boolean type
                'nonce' => (string)$response['nonce'],
                'timestamp' => (int)$response['timestamp']
            ];
            
            // Sort keys (Python uses sort_keys=True)
            ksort($payload);
            
            // Encode to JSON (PHP default matches Python's separators=(',', ':'))
            // Python: {"authenticated":true,...} (no spaces)
            $canonicalJson = json_encode($payload);
            
            // Verify HMAC signature with constant-time comparison
            $expectedSignature = hash_hmac('sha256', $canonicalJson, self::getSecret());
            
            if (!hash_equals($expectedSignature, $response['signature'])) {
                // Debug log to help diagnose mismatches if they occur
                self::logTrace("Sig Mismatch! Got: {$response['signature']} Expected: {$expectedSignature}");
                // self::logTrace("Canonical Payload (PHP): " . $canonicalJson);
                // self::logTrace("Payload Hex: " . bin2hex($canonicalJson));
                // self::logTrace("Secret Hash (Debug): " . hash('sha256', self::getSecret()));
                return false;
            }
            
            // All checks passed
            return true;
            
        } catch (Exception $e) {
            error_log("Response verification error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create secure session with binding to prevent session hijacking
     */
    private static function createSecureSession(int $userId, string $modality, float $confidence): array {
        $sessionId = bin2hex(random_bytes(32));
        $expiresAt = time() + 3600; // 1 hour session
        
        // Store session server-side (not in cookies)
        $pdo = getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO secure_sessions (session_id, user_id, auth_method, confidence_score, expires_at, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$sessionId, $userId, $modality, $confidence, date('Y-m-d H:i:s', $expiresAt)]);
        
        // Bind to PHP session
        session_regenerate_id(true); // Prevent session fixation
        $_SESSION['secure_session_id'] = $sessionId;
        $_SESSION['user_id'] = $userId;
        $_SESSION['auth_method'] = $modality;
        $_SESSION['auth_time'] = time();
        
        return [
            'session_id' => $sessionId,
            'expires_at' => $expiresAt
        ];
    }
    
    /**
     * General Authentication Logging with Silent Forensic Data Collection & Device Fingerprinting
     * Collects browser fingerprint and network info without user prompts
     * Tracks attackers across sessions even with IP/browser changes
     * Publicly accessible for Login pages to record attempts
     */
    public static function logAuthAttempt(int $userId, string $status, string $authType, string $details, string $userType = 'customer', array $fingerprint = []): void {
        try {
            $pdo = getConnection();
            
            // Silent forensic data collection (no user permission needed)
            // Ngrok/Proxy Compatibility
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                 $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                 $ipAddress = trim($ips[0]);
            }
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null;
            $referer = $_SERVER['HTTP_REFERER'] ?? null;
            $requestUri = $_SERVER['REQUEST_URI'] ?? null;
            $xForwardedFor = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null; // Detects proxy/VPN
            
            // Device fingerprint data (from JavaScript)
            $screenResolution = $fingerprint['screen_resolution'] ?? null;
            $timezone = $fingerprint['timezone'] ?? null;
            $platform = $fingerprint['platform'] ?? null;
            $hardwareConcurrency = $fingerprint['hardware_concurrency'] ?? null;
            $deviceMemory = $fingerprint['device_memory'] ?? null;
            $colorDepth = $fingerprint['color_depth'] ?? null;
            $pixelRatio = $fingerprint['pixel_ratio'] ?? null;
            $touchSupport = isset($fingerprint['touch_support']) ? (int)$fingerprint['touch_support'] : null;
            $canvasFingerprint = $fingerprint['canvas_fingerprint'] ?? null;
            $webglVendor = $fingerprint['webgl_vendor'] ?? null;
            $doNotTrack = $fingerprint['do_not_track'] ?? null;
            $cookieEnabled = isset($fingerprint['cookie_enabled']) ? (int)$fingerprint['cookie_enabled'] : null;
            $onlineStatus = isset($fingerprint['online_status']) ? (int)$fingerprint['online_status'] : null;
            
            $stmt = $pdo->prepare("
                INSERT INTO auth_logs 
                (user_id, user_type, auth_type, status, ip_address, user_agent, accept_language, referer, request_uri, x_forwarded_for,
                 screen_resolution, timezone, platform, hardware_concurrency, device_memory, color_depth, pixel_ratio, touch_support,
                 canvas_fingerprint, webgl_vendor, do_not_track, cookie_enabled, online_status, details, timestamp) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $userId,
                $userType,
                $authType,
                $status,
                $ipAddress,
                $userAgent,
                $acceptLanguage,
                $referer,
                $requestUri,
                $xForwardedFor,
                $screenResolution,
                $timezone,
                $platform,
                $hardwareConcurrency,
                $deviceMemory,
                $colorDepth,
                $pixelRatio,
                $touchSupport,
                $canvasFingerprint,
                $webglVendor,
                $doNotTrack,
                $cookieEnabled,
                $onlineStatus,
                $details
            ]);
        } catch (Exception $e) {
            error_log("Logging error: " . $e->getMessage());
        }
    }
    
    /**
     * Validate existing secure session
     */
    public static function validateSession(): bool {
        // FAIL-SECURE: Default to invalid session
        $isValid = false;
        
        try {
            // Must have session ID
            if (!isset($_SESSION['secure_session_id']) || empty($_SESSION['secure_session_id'])) {
                return false;
            }
            
            $pdo = getConnection();
            if (!$pdo) {
                return false; // Database connection failure = invalid session
            }
            
            $stmt = $pdo->prepare("
                SELECT user_id, expires_at FROM secure_sessions 
                WHERE session_id = ? AND expires_at > NOW()
            ");
            
            if (!$stmt->execute([$_SESSION['secure_session_id']])) {
                return false; // Query failure = invalid session
            }
            
            $session = $stmt->fetch();
            
            // FAIL-SECURE: Only valid if session exists AND matches user_id
            if ($session && 
                isset($_SESSION['user_id']) && 
                $session['user_id'] == $_SESSION['user_id']) {
                $isValid = true;
            }
            
        } catch (Exception $e) {
            error_log("Session validation error: " . $e->getMessage());
            $isValid = false;
        }
        
        // Clean up invalid sessions
        if (!$isValid) {
            unset($_SESSION['secure_session_id'], $_SESSION['user_id'], $_SESSION['auth_method']);
        }
        
        return $isValid;
    }
}
?>