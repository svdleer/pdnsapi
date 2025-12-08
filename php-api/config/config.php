<?php
/**
 * API Configuration
 */

// Load environment variables from .env file (if not already loaded by autoloader)
if (!isset($_ENV['PDNS_API_KEY'])) {
    require_once __DIR__ . '/../includes/env-loader.php';
}

$pdns_config = [
    'base_url' => $_ENV['PDNS_BASE_URL'] ?? 'https://your-pdns-server.example.com/api/v1',
    'auth_type' => 'basic',
    'api_key' => $_ENV['PDNS_API_KEY'] ?? 'your-pdns-api-key-here', // Fallback for dev
    'pdns_server_key' => $_ENV['PDNS_SERVER_KEY'] ?? 'your-pdns-server-key-here', // Fallback for dev
    
    // PowerDNS Admin API settings for user management  
    'pdns_admin_url' => $_ENV['PDNS_BASE_URL'] ?? 'https://your-pdns-server.example.com/api/v1',
    'pdns_admin_user' => $_ENV['PDNS_ADMIN_USER'] ?? 'your-admin-username', 
    'pdns_admin_password' => $_ENV['PDNS_ADMIN_PASSWORD'] ?? 'your-admin-password'
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
        ($_ENV['AVANT_API_KEY'] ?? 'your-api-key-here') => 'Admin API Key',
        // Generate secure keys using: openssl rand -hex 32
        // Example: 'abc123def456...' => 'Admin API Key',
    ],
    'exempt_endpoints' => [
        // Allow access to documentation without API key for easier development
        '',           // Root endpoint 
        'index',      // Documentation endpoint
        'docs',       // Swagger UI (Admin)
        'docs.html',  // Swagger UI (Admin) explicit
        'docs-user',  // Swagger UI (User)
        'docs-user.html', // Swagger UI (User) explicit
        'user-docs',  // Swagger UI (User) alternate
        'swagger',    // Swagger UI alternate
        'swagger-ui', // Swagger UI alternate
        'openapi',    // OpenAPI spec (Admin)
        'openapi.json', // OpenAPI JSON (Admin)
        'openapi.yaml', // OpenAPI YAML (Admin)
        'swagger.json', // Swagger JSON (Admin)
        'swagger.yaml', // Swagger YAML (Admin)
        'openapi-user', // OpenAPI spec (User)
        'openapi-user.json', // OpenAPI JSON (User)
        'openapi-user.yaml', // OpenAPI YAML (User)
        'openapi-user-nl', // OpenAPI spec (User Dutch)
        'openapi-user-nl.json', // OpenAPI JSON (User Dutch)
        'openapi-user-nl.yaml', // OpenAPI YAML (User Dutch)
        'avantlogo.png', // Avant logo
        'avant_header.png', // Avant header logo
        'health',     // Basic health check endpoint
        'debug-auth.php', // Temporary debug endpoint
        'env-check.php',  // Temporary env check endpoint
        'env-debug.php',  // Temporary env debug endpoint
        'simple-env-check.php', // Simple env check
        'config-test.php', // Config loading test
        // Note: All actual API endpoints (/accounts, /domains, etc.) still require authentication
    ]
];

// IP Security Configuration - GLOBAL ALLOWLIST
$config['security'] = [
    'ip_validation_enabled' => true,
    
    // Global IP allowlist - applies to ALL API endpoints
    // IP allowlist now loaded from database (see getIpAllowlist() function)
    'allowed_ips' => [], // Populated dynamically from database
    
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
    $key_info = $api_key ? isValidApiKey($api_key) : false;
    
    if (!$key_info) {
        logSecurityEvent("INVALID_API_KEY", getClientIpAddress(), $path);
        sendError(401, "Valid API Key required");
    }
    
    // Store key info in global variable for use in endpoints
    global $current_api_key_info;
    $current_api_key_info = $key_info;
    
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
 * Load IP allowlist from database
 */
function getIpAllowlist() {
    global $pdo;
    
    // Initialize PDO if not set
    if (!isset($pdo)) {
        $database = new Database();
        $pdo = $database->getConnection();
    }
    
    static $cached_ips = null;
    
    // Return cached IPs if already loaded
    if ($cached_ips !== null) {
        return $cached_ips;
    }
    
    $cached_ips = [];
    
    try {
        $stmt = $pdo->prepare("SELECT ip_address FROM ip_allowlist WHERE enabled = 1");
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cached_ips[] = $row['ip_address'];
        }
        
        // Fallback to localhost if database is empty or fails
        if (empty($cached_ips)) {
            error_log("Warning: IP allowlist empty, adding localhost fallback");
            $cached_ips = ['127.0.0.1', '::1'];
        }
        
    } catch (Exception $e) {
        error_log("Error loading IP allowlist from database: " . $e->getMessage());
        // Fallback to localhost if database fails
        $cached_ips = ['127.0.0.1', '::1'];
    }
    
    return $cached_ips;
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
    $allowed_ips = getIpAllowlist(); // Load from database
    
    foreach ($allowed_ips as $allowed_ip) {
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
    $parts = explode('/', $range);
    if (count($parts) !== 2) {
        error_log("Invalid CIDR notation: $range");
        return false;
    }
    
    list($subnet, $mask) = $parts;
    
    // Validate that mask is numeric
    if (!is_numeric($mask)) {
        error_log("Invalid CIDR mask (not numeric): $mask in range $range");
        return false;
    }
    
    // Determine IP type and validate both IP and subnet match
    $ipIsIPv4 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    $ipIsIPv6 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    $subnetIsIPv4 = filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    $subnetIsIPv6 = filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    
    // IPv4 support - both IP and subnet must be IPv4
    if ($ipIsIPv4 && $subnetIsIPv4) {
        return ipv4InRange($ip, $subnet, $mask);
    }
    
    // IPv6 support - both IP and subnet must be IPv6
    if ($ipIsIPv6 && $subnetIsIPv6) {
        return ipv6InRange($ip, $subnet, $mask);
    }
    
    // IP and subnet types don't match - return false
    // (IPv4 client can't match IPv6 subnet and vice versa)
    
    return false;
}

/**
 * Check if IPv4 address is in CIDR range
 */
function ipv4InRange($ip, $subnet, $mask) {
    // Validate mask range for IPv4 (0-32)
    $mask = (int)$mask;
    if ($mask < 0 || $mask > 32) {
        error_log("Invalid IPv4 CIDR mask: $mask (must be 0-32)");
        return false;
    }
    
    $ip_long = ip2long($ip);
    $subnet_long = ip2long($subnet);
    
    // Handle edge cases
    if ($ip_long === false || $subnet_long === false) {
        return false;
    }
    
    // Special case for /0 (all IPs)
    if ($mask === 0) {
        return true;
    }
    
    $mask_long = -1 << (32 - $mask);
    
    return ($ip_long & $mask_long) === ($subnet_long & $mask_long);
}

/**
 * Check if IPv6 address is in CIDR range
 */
function ipv6InRange($ip, $subnet, $mask) {
    // Validate mask range for IPv6 (0-128)
    $mask = (int)$mask;
    if ($mask < 0 || $mask > 128) {
        error_log("Invalid IPv6 CIDR mask: $mask (must be 0-128)");
        return false;
    }
    
    $ip_bin = inet_pton($ip);
    $subnet_bin = inet_pton($subnet);
    
    if (!$ip_bin || !$subnet_bin) return false;
    
    // Special case for /0 (all IPs)
    if ($mask === 0) {
        return true;
    }
    
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
 * Validate API key against configured keys and database
 * Returns array with key info if valid, false otherwise
 * @param string $provided_key The API key to validate
 * @return array|false Array with key info (is_admin, account_id, permissions) or false
 */
function isValidApiKey($provided_key) {
    global $api_settings, $pdo;
    
    // Initialize PDO if not set
    if (!isset($pdo)) {
        $database = new Database();
        $pdo = $database->getConnection();
    }
    
    // Check if it's an admin key from config
    if (array_key_exists($provided_key, $api_settings['api_keys'])) {
        return [
            'is_admin' => true,
            'account_id' => null,
            'permissions' => ['all' => true],
            'description' => $api_settings['api_keys'][$provided_key]
        ];
    }
    
    // Check database for account-scoped keys
    try {
        $stmt = $pdo->prepare("
            SELECT id, account_id, permissions, allowed_ips, enabled, expires_at, description
            FROM api_keys 
            WHERE api_key = ? 
            AND enabled = 1
        ");
        $stmt->execute([$provided_key]);
        $key_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$key_data) {
            return false;
        }
        
        // Check if key has expired
        if ($key_data['expires_at'] && strtotime($key_data['expires_at']) < time()) {
            return false;
        }
        
        // Check IP restrictions for this key
        if ($key_data['allowed_ips']) {
            $allowed_ips = json_decode($key_data['allowed_ips'], true);
            if (is_array($allowed_ips) && !empty($allowed_ips)) {
                $client_ip = getClientIpAddress();
                $ip_allowed = false;
                
                foreach ($allowed_ips as $allowed_ip) {
                    if (ipInRange($client_ip, $allowed_ip)) {
                        $ip_allowed = true;
                        break;
                    }
                }
                
                if (!$ip_allowed) {
                    logSecurityEvent("API_KEY_IP_BLOCKED", $client_ip, $_SERVER['REQUEST_URI'] ?? '/', 
                        "API key {$key_data['id']} blocked - IP not in key's allowlist");
                    return false;
                }
            }
        }
        
        // Update last_used_at timestamp
        $update_stmt = $pdo->prepare("UPDATE api_keys SET last_used_at = NOW() WHERE id = ?");
        $update_stmt->execute([$key_data['id']]);
        
        // Parse permissions JSON
        $permissions = $key_data['permissions'] ? json_decode($key_data['permissions'], true) : [];
        
        return [
            'is_admin' => false,
            'account_id' => $key_data['account_id'],
            'permissions' => $permissions,
            'description' => $key_data['description'],
            'key_id' => $key_data['id']
        ];
        
    } catch (Exception $e) {
        error_log("Error validating API key: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if current API key has specific permission
 * @param string $permission Permission to check (e.g., 'domains', 'create_domains', 'delete_domains')
 * @param string $access Optional access level to check ('r', 'w', 'rw')
 * @return bool True if permission granted
 */
function hasPermission($permission, $access = null) {
    global $current_api_key_info;
    
    if (!isset($current_api_key_info)) {
        return false;
    }
    
    // Admin keys have all permissions
    if ($current_api_key_info['is_admin']) {
        return true;
    }
    
    $permissions = $current_api_key_info['permissions'] ?? [];
    
    // Check specific permission
    if (!isset($permissions[$permission])) {
        return false;
    }
    
    $perm_value = $permissions[$permission];
    
    // Boolean permission (true/false)
    if (is_bool($perm_value)) {
        return $perm_value;
    }
    
    // Access level permission ('r', 'w', 'rw')
    if ($access && is_string($perm_value)) {
        if ($access === 'r') {
            return strpos($perm_value, 'r') !== false;
        } elseif ($access === 'w') {
            return strpos($perm_value, 'w') !== false;
        } elseif ($access === 'rw') {
            return strpos($perm_value, 'r') !== false && strpos($perm_value, 'w') !== false;
        }
    }
    
    return (bool)$perm_value;
}

/**
 * Get the account ID associated with the current API key
 * Returns null for admin keys
 * @return int|null Account ID or null
 */
function getApiKeyAccountId() {
    global $current_api_key_info;
    
    if (!isset($current_api_key_info)) {
        return null;
    }
    
    return $current_api_key_info['is_admin'] ? null : $current_api_key_info['account_id'];
}

/**
 * Check if current API key is an admin key (full access)
 * @return bool True if admin key
 */
function isAdminApiKey() {
    global $current_api_key_info;
    
    return isset($current_api_key_info) && $current_api_key_info['is_admin'];
}

/**
 * Check if a domain belongs to the account associated with the current API key
 * Admin keys have access to all domains
 * @param int $domain_id Domain ID to check
 * @return bool True if access granted
 */
function canAccessDomain($domain_id) {
    global $current_api_key_info, $pdo;
    
    // Initialize PDO if not set
    if (!isset($pdo)) {
        $database = new Database();
        $pdo = $database->getConnection();
    }
    
    if (!isset($current_api_key_info)) {
        return false;
    }
    
    // Admin keys can access all domains
    if ($current_api_key_info['is_admin']) {
        return true;
    }
    
    $account_id = $current_api_key_info['account_id'];
    
    if (!$account_id) {
        return false;
    }
    
    // Check if domain belongs to this account
    try {
        $stmt = $pdo->prepare("SELECT id FROM domains WHERE id = ? AND account_id = ?");
        $stmt->execute([$domain_id, $account_id]);
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        error_log("Error checking domain access: " . $e->getMessage());
        return false;
    }
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
