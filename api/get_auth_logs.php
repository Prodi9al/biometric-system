<?php
// Force Admin Session for this API
session_name('BANK_ADMIN_SESSION');
session_start();

require_once '../config/config.php';
require_once '../core/Session.php';
require_once '../core/GeoLocation.php';

header('Content-Type: application/json');

if (!Session::isLoggedIn() || Session::get('user_type') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $pdo = getConnection();
    
    // Get recent auth logs with user details and forensic data
    $sql = "
        SELECT 
            l.log_id as id,
            l.user_id as userId,
            l.user_type,
            CASE 
                WHEN l.user_type = 'admin' THEN a.full_name
                WHEN l.user_type = 'customer' THEN c.full_name
                ELSE 'Unknown'
            END as userName,
            l.auth_type as type,
            l.status,
            l.details,
            l.ip_address,
            l.user_agent,
            l.accept_language,
            l.referer,
            l.request_uri,
            l.x_forwarded_for,
            l.screen_resolution,
            l.timezone,
            l.platform,
            l.hardware_concurrency,
            l.device_memory,
            l.color_depth,
            l.pixel_ratio,
            l.touch_support,
            l.canvas_fingerprint,
            l.webgl_vendor,
            l.do_not_track,
            l.cookie_enabled,
            l.online_status,
            l.timestamp
        FROM auth_logs l
        LEFT JOIN customers c ON l.user_id = c.user_id AND l.user_type = 'customer'
        LEFT JOIN admin_users a ON l.user_id = a.admin_id AND l.user_type = 'admin'
        WHERE 1=1
    ";
    
    $params = [];
    
    // Filter by Specific User ID (from dropdown)
    if (isset($_GET['user_id']) && is_numeric($_GET['user_id']) && $_GET['user_id'] != '') {
        $sql .= " AND l.user_id = :user_id";
        $params[':user_id'] = $_GET['user_id'];
    }
    
    // Add sorting and limiting
    $sql .= " ORDER BY l.timestamp DESC LIMIT 100";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the logs for frontend with forensic data
    $formattedLogs = array_map(function($log) {
        // Get location data for IP
        $location = getCachedIPLocation($log['ip_address']);
        $locationStr = 'Unknown';
        if ($location) {
            $parts = array_filter([$location['city'], $location['region'], $location['country']]);
            $locationStr = implode(', ', $parts) ?: 'Unknown';
        }
        
        return [
            'id' => $log['id'],
            'user_id' => $log['userId'],
            'user_name' => $log['userName'] ?: 'System / Unknown',
            'auth_type' => ucwords(str_replace('_', ' ', $log['type'])),
            'status' => $log['status'],
            'details' => $log['details'],
            'ip' => $log['ip_address'],
            'location' => $locationStr,
            'location_data' => $location,
            'user_agent' => $log['user_agent'],
            'language' => $log['accept_language'],
            'referer' => $log['referer'],
            'request_uri' => $log['request_uri'],
            'x_forwarded_for' => $log['x_forwarded_for'],
            'screen_resolution' => $log['screen_resolution'],
            'timezone' => $log['timezone'],
            'platform' => $log['platform'],
            'hardware_concurrency' => $log['hardware_concurrency'],
            'device_memory' => $log['device_memory'],
            'color_depth' => $log['color_depth'],
            'pixel_ratio' => $log['pixel_ratio'],
            'touch_support' => $log['touch_support'],
            'canvas_fingerprint' => $log['canvas_fingerprint'],
            'webgl_vendor' => $log['webgl_vendor'],
            'do_not_track' => $log['do_not_track'],
            'cookie_enabled' => $log['cookie_enabled'],
            'online_status' => $log['online_status'],
            'timestamp' => $log['timestamp']
        ];
    }, $logs);
    
    echo json_encode($formattedLogs);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>