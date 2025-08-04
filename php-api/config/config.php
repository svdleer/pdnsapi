<?php
/**
 * API Configuration
 */
$pdns_config = [
    'base_url' => 'https://dnsadmin.avant.nl/api/v1',
    'auth_type' => 'apikey',
    'api_key' => 'beuePDTD2C8HzSx'
];

// API settings
$api_settings = [
    'enable_cors' => true,
    'cors_origins' => ['*'], // For production, specify allowed origins
    'debug_mode' => false,
    'log_level' => 'INFO', // DEBUG, INFO, WARNING, ERROR
    'require_api_key' => false, // Set to true when you want API key authentication
    'force_https' => true, // Redirect HTTP to HTTPS
    'hsts_max_age' => 31536000, // HSTS max age in seconds (1 year)
    'api_keys' => [
        // Add your API keys here - format: 'key' => 'description'
        'your-secure-api-key-here' => 'Default API Key'
        // Generate secure keys using: openssl rand -hex 32
    ],
    'exempt_endpoints' => [
        // Endpoints that don't require API key authentication
        '',           // Root/documentation endpoint
        'index',      // Documentation endpoint
        'docs',       // Swagger UI
        'swagger',    // Swagger UI alternate
        'openapi',    // OpenAPI spec
        'status',     // Status endpoint
        'php-test.php' // Test endpoint
    ]
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
    
    // For now, just return true (API key validation disabled)
    return true;
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
?>
