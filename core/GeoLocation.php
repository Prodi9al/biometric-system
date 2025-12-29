<?php
// IP Geolocation lookup using free ip-api.com service
// Returns: country, region, city, lat, lon, timezone

function getIPLocation($ip) {
    // Skip local/private IPs
    if ($ip === 'unknown' || 
        $ip === '127.0.0.1' || 
        $ip === '::1' ||
        strpos($ip, '192.168.') === 0 ||
        strpos($ip, '10.') === 0) {
        return [
            'country' => 'Local Network',
            'city' => 'localhost',
            'region' => '',
            'timezone' => '',
            'lat' => null,
            'lon' => null
        ];
    }
    
    try {
        // Free tier: 45 requests per minute
        $url = "http://ip-api.com/json/{$ip}?fields=status,country,regionName,city,lat,lon,timezone";
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 2, // 2 second timeout
                'ignore_errors' => true
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            return null;
        }
        
        $data = json_decode($response, true);
        
        if ($data && $data['status'] === 'success') {
            return [
                'country' => $data['country'] ?? '',
                'city' => $data['city'] ?? '',
                'region' => $data['regionName'] ?? '',
                'timezone' => $data['timezone'] ?? '',
                'lat' => $data['lat'] ?? null,
                'lon' => $data['lon'] ?? null
            ];
        }
        
        return null;
        
    } catch (Exception $e) {
        error_log("Geolocation error: " . $e->getMessage());
        return null;
    }
}

// Simple cache to avoid repeated lookups
$geoCache = [];

function getCachedIPLocation($ip) {
    global $geoCache;
    
    if (!isset($geoCache[$ip])) {
        $geoCache[$ip] = getIPLocation($ip);
    }
    
    return $geoCache[$ip];
}
?>
