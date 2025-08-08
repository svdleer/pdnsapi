<?php
/**
 * API Configuration
 */

// Load environment variables from .env file (if not already loaded by autoloader)
if (!isset($_ENV['PDNS_API_KEY'])) {
    require_once __DIR__ . '/../includes/env-loader.php';
}

$pdns_config = [
    'base_url' => $_ENV['PDNS_BASE_URL'] ?? 'https://dnsadmin.avant.nl/api/v1',
    'auth_type' => 'basic',
    'api_key' => $_ENV['PDNS_API_KEY'] ?? 'YXBpYWRtaW46VmV2ZWxnSWNzXm9tMg==', // Fallback for dev
    'pdns_server_key' => $_ENV['PDNS_SERVER_KEY'] ?? 'morWehofCidwiWejishOwko=!b', // Fallback for dev
    
    // PowerDNS Admin API settings for user management  
    'pdns_admin_url' => $_ENV['PDNS_BASE_URL'] ?? 'https://dnsadmin.avant.nl/api/v1',
    'pdns_admin_user' => $_ENV['PDNS_ADMIN_USER'] ?? 'apiadmin', 
    'pdns_admin_password' => $_ENV['PDNS_ADMIN_PASSWORD'] ?? 'VevelgIcs^om2'
];

// API settings
$api_settings = [
    'enable_cors' => true,
    'cors_origins' => ['*'], // For production, specify allowed origins
    'debug_mode' => false,
    'log_level' => 'INFO', // DEBUG, INFO, WARNING, ERROR
    'require_api_key' => true, // API key authentication required
    'force_https' => true, // Redirect HTTP to HTTPS
    'hsts_max_age' => 31536000, // HSTS max age in seconds (1 year)
    'api_keys' => [
        // Add your API keys here - format: 'key' => 'description'
        '18b1513723567c9eacfbc2887cd9cc8737ec0fcf61f5f7d1b11518dc3260cbe9' => 'Admin API Key',
        ($_ENV['AVANT_API_KEY'] ?? '46b3d78c557cd66a047a38897914d203ab5c359719161e836ecce5508e57b1a9') => 'Fallback API Key',
        // Generate secure keys using: openssl rand -hex 32
    ],
    'exempt_endpoints' => [
        // Allow access to documentation without API key for easier development
        '',           // Root endpoint 
        'index',      // Documentation endpoint
        'docs',       // Swagger UI
        'swagger',    // Swagger UI alternate
        'openapi',    // OpenAPI spec
        'health',     // Basic health check endpoint
        'debug-headers.php',  // Temporary debug endpoint
        'test-auth.php',      // Authentication flow test
        // Note: All actual API endpoints (/accounts, /domains, etc.) still require authentication
    ]
];

// IP Security Configuration - GLOBAL ALLOWLIST
$config['security'] = [
    'ip_validation_enabled' => true,
    
    // Global IP allowlist - applies to ALL API endpoints
    // Simple and secure: if your IP isn't here, no API access
    'allowed_ips' => [
        '127.0.0.1',           // localhost
        '::1',                 // localhost IPv6
        '149.210.167.40',      // server primary IP
        '149.210.166.5',       // server secondary IP
        '2a01:7c8:aab3:5d8:149:210:166:5', // server IPv6
        '192.168.1.0/24',      // local network example
        '10.0.0.0/8',          // private network example
        // Add your admin IPs here:
        // '203.0.113.10',     // office IP
        // '198.51.100.50',    // backup admin IP
    ],
    
    // Security logging and response
    'log_ip_violations' => true,
    'block_duration' => 3600, // 1 hour block for repeated violations
    'violation_threshold' => 5, // Block after 5 failed attempts
];

// Map security configuration for API functions
$api_security = [
    'require_ip_allowlist' => $config['security']['ip_validation_enabled'],
    'allowed_ips' => $config['security']['allowed_ips'],
    'log_blocked_attempts' => $config['security']['log_ip_violations'],
    'trust_proxy_headers' => true, // Trust proxy headers for real IP detection
    'proxy_header_priority' => [
        'HTTP_CF_CONNECTING_IP',     // Cloudflare
        'HTTP_X_FORWARDED_FOR',      // Standard proxy header
        'HTTP_X_REAL_IP',            // Nginx real IP
        'HTTP_CLIENT_IP',            // Alternative client IP
    ],
];

// API Response helper functions
function sendResponse($status_code, $data = null, $message = null) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    
    $response = [];
    if ($message) $response['message'] = $message;
    if ($data !== null) $response['data'] = $data;
    
    echo json_encode($response);
    exit;
}

function sendError($status_code, $message, $errors = null) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    
    $response = ['error' => $message];
    if ($errors) $response['errors'] = $errors;
    
    echo json_encode($response);
    exit;
}

// Additional required functions for index.php
function enforceHTTPS() {
    global $api_settings;
    
    if ($api_settings['force_https'] && !isHTTPS()) {
        $redirect_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header('Location: ' . $redirect_url, true, 301);
        exit;
    }
}

function isHTTPS() {
    return (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') ||
        (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
    );
}

function addSecurityHeaders() {
    global $api_settings;
    
    if (isHTTPS() && $api_settings['hsts_max_age'] > 0) {
        header('Strict-Transport-Security: max-age=' . $api_settings['hsts_max_age'] . '; includeSubDomains; preload');
    }
    
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' unpkg.com; style-src \'self\' \'unsafe-inline\' unpkg.com; img-src \'self\' data:; connect-src \'self\'');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

function requireApiKey() {
    global $api_settings;
    
    // Skip authentication if disabled
    if (!$api_settings['require_api_key']) {
        return true;
    }
    
    // Get current endpoint path
    $request_uri = $_SERVER['REQUEST_URI'];
    $path = parse_url($request_uri, PHP_URL_PATH);
    $path = trim($path, '/');
    
    // Remove base path if exists
    $base_path = 'php-api';
    if (strpos($path, $base_path) === 0) {
        $path = substr($path, strlen($base_path));
        $path = trim($path, '/');
    }
    
    // Check if endpoint is exempt from authentication
    if (in_array($path, $api_settings['exempt_endpoints'])) {
        return true;
    }
    
    // Check IP allowlist first
    if (!isIpAllowed()) {
        $client_ip = getClientIpAddress();
        logSecurityEvent("IP_BLOCKED", $client_ip, $path);
        sendError(403, "IP address not authorized for API access", [
            'client_ip' => $client_ip,
            'message' => 'Your IP address is not in the allowlist for API access'
        ]);
    }
    
    // Validate API key
    $api_key = getApiKeyFromRequest();
    if (!$api_key || !isValidApiKey($api_key)) {
        logSecurityEvent("INVALID_API_KEY", getClientIpAddress(), $path);
        sendError(401, "Valid Admin API Key required");
    }
    
    return true;
}

/**
 * Get the real client IP address, considering proxy headers
 */
function getClientIpAddress() {
    global $api_security;
    
    // If not trusting proxy headers, use REMOTE_ADDR
    if (!$api_security['trust_proxy_headers']) {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    // Check proxy headers in order of priority
    foreach ($api_security['proxy_header_priority'] as $header) {
        if (!empty($_SERVER[$header])) {
            $ips = explode(',', $_SERVER[$header]);
            $ip = trim($ips[0]); // Take the first IP (original client)
            
            // Validate IP format
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    // Fallback to REMOTE_ADDR
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Check if the client IP is in the allowlist
 */
function isIpAllowed() {
    global $api_security;
    
    // Skip IP validation if disabled
    if (!$api_security['require_ip_allowlist']) {
        return true;
    }
    
    $client_ip = getClientIpAddress();
    
    foreach ($api_security['allowed_ips'] as $allowed_ip) {
        if (ipInRange($client_ip, $allowed_ip)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Check if an IP address is within a given IP/CIDR range
 */
function ipInRange($ip, $range) {
    // Handle single IP addresses
    if (strpos($range, '/') === false) {
        return $ip === $range;
    }
    
    // Handle CIDR notation
    list($subnet, $mask) = explode('/', $range);
    
    // IPv6 support
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        return ipv6InRange($ip, $subnet, $mask);
    }
    
    // IPv4 support
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return ipv4InRange($ip, $subnet, $mask);
    }
    
    return false;
}

/**
 * Check if IPv4 address is in CIDR range
 */
function ipv4InRange($ip, $subnet, $mask) {
    $ip_long = ip2long($ip);
    $subnet_long = ip2long($subnet);
    $mask_long = -1 << (32 - $mask);
    
    return ($ip_long & $mask_long) === ($subnet_long & $mask_long);
}

/**
 * Check if IPv6 address is in CIDR range
 */
function ipv6InRange($ip, $subnet, $mask) {
    $ip_bin = inet_pton($ip);
    $subnet_bin = inet_pton($subnet);
    
    if (!$ip_bin || !$subnet_bin) return false;
    
    $mask_bytes = intval($mask / 8);
    $mask_bits = $mask % 8;
    
    // Compare full bytes
    if ($mask_bytes > 0) {
        if (substr($ip_bin, 0, $mask_bytes) !== substr($subnet_bin, 0, $mask_bytes)) {
            return false;
        }
    }
    
    // Compare remaining bits
    if ($mask_bits > 0 && $mask_bytes < 16) {
        $byte_mask = 0xFF << (8 - $mask_bits);
        $ip_byte = ord($ip_bin[$mask_bytes]) & $byte_mask;
        $subnet_byte = ord($subnet_bin[$mask_bytes]) & $byte_mask;
        
        if ($ip_byte !== $subnet_byte) {
            return false;
        }
    }
    
    return true;
}

/**
 * Extract API key from Authorization header
 */
function getApiKeyFromRequest() {
    // Use custom function to get headers (getallheaders() not always available)
    $headers = getAllRequestHeaders();
    
    // Check Authorization header
    if (isset($headers['Authorization'])) {
        $auth_header = $headers['Authorization'];
        
        // Basic auth format: "Basic base64(username:password)"
        if (strpos($auth_header, 'Basic ') === 0) {
            $encoded = substr($auth_header, 6);
            $decoded = base64_decode($encoded);
            
            // For API key auth, we treat the entire decoded string as the key
            // or split on ':' and use the first part as the key
            return explode(':', $decoded)[0];
        }
        
        // Bearer token format: "Bearer token"
        if (strpos($auth_header, 'Bearer ') === 0) {
            return substr($auth_header, 7);
        }
    }
    
    // Check X-API-Key header
    if (isset($headers['X-API-Key'])) {
        return $headers['X-API-Key'];
    }
    
    // Check query parameter (less secure, not recommended)
    if (isset($_GET['api_key'])) {
        return $_GET['api_key'];
    }
    
    return null;
}

/**
 * Get all request headers - works in all PHP environments
 */
function getAllRequestHeaders() {
    $headers = [];
    
    // Use getallheaders() if available (Apache)
    if (function_exists('getallheaders')) {
        return getallheaders();
    }
    
    // Fallback: parse $_SERVER for headers
    foreach ($_SERVER as $name => $value) {
        if (strpos($name, 'HTTP_') === 0) {
            // Convert HTTP_X_API_KEY to X-Api-Key
            $header_name = str_replace('_', '-', substr($name, 5));
            $header_name = ucwords(strtolower($header_name), '-');
            $headers[$header_name] = $value;
        }
    }
    
    return $headers;
}

/**
 * Validate API key against configured keys
 */
function isValidApiKey($provided_key) {
    global $api_settings;
    
    return array_key_exists($provided_key, $api_settings['api_keys']);
}

/**
 * Log security events for monitoring and alerting
 */
function logSecurityEvent($event_type, $ip_address, $endpoint = null, $details = null) {
    global $api_security;
    
    if (!$api_security['log_blocked_attempts']) {
        return;
    }
    
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event_type' => $event_type,
        'ip_address' => $ip_address,
        'endpoint' => $endpoint,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'details' => $details
    ];
    
    // Log to system error log
    error_log('SECURITY EVENT: ' . json_encode($log_entry));
    
    // TODO: Consider sending to external security monitoring system
    // e.g., Splunk, ELK stack, or security incident response system
}

function logApiRequest($endpoint, $method, $status_code) {
    global $api_settings;
    
    if ($api_settings['debug_mode'] || $api_settings['log_level'] === 'DEBUG') {
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'endpoint' => $endpoint,
            'method' => $method,
            'status_code' => $status_code,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
        ];
        
        // Log to error_log
        error_log('API Request: ' . json_encode($log_entry));
    }
}

/**
 * Global IP Validation - Simple and Clear
 * Checks if the client IP is in the global allowlist
 */
function validateClientIP() {
    global $config;
    
    if (!$config['security']['ip_validation_enabled']) {
        return true; // IP validation disabled
    }
    
    $clientIP = getClientIP();
    $allowedIPs = $config['security']['allowed_ips'];
    
    // Check if client IP is in the global allowlist
    foreach ($allowedIPs as $allowedIP) {
        if (ipInRange($clientIP, $allowedIP)) {
            return true; // IP is allowed
        }
    }
    
    // IP not allowed - log violation and block
    if ($config['security']['log_ip_violations']) {
        logSecurityEvent('IP_BLOCKED', $clientIP, $_SERVER['REQUEST_URI'] ?? '/', 
                        'Client IP not in global allowlist');
    }
    
    return false;
}

/**
 * Get the real client IP address
 */
function getClientIP() {
    // Check for IP from shared internet
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }
    // Check for IP passed from proxy
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    // Return remote address
    else {
        return $_SERVER['REMOTE_ADDR'];
    }
}
?>
