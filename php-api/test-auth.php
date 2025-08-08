<?php
/**
 * Authentication Test Script
 * Tests the complete authentication flow step by step
 */

require_once 'config/config.php';

echo "Authentication Flow Test\n";
echo "========================\n\n";

// Step 1: Check if API key is required
echo "1. API Key Required: " . ($api_settings['require_api_key'] ? 'YES' : 'NO') . "\n";

// Step 2: Get current endpoint
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = trim($path, '/');

// Remove base path if exists
$base_path = 'php-api';
if (strpos($path, $base_path) === 0) {
    $path = substr($path, strlen($base_path));
    $path = trim($path, '/');
}

echo "2. Current Path: '$path'\n";

// Step 3: Check if endpoint is exempt
$is_exempt = in_array($path, $api_settings['exempt_endpoints']);
echo "3. Endpoint Exempt: " . ($is_exempt ? 'YES' : 'NO') . "\n";

if ($is_exempt) {
    echo "   -> This endpoint should NOT require authentication\n";
} else {
    echo "   -> This endpoint REQUIRES authentication\n";
}

// Step 4: Check IP allowlist
echo "\n4. IP Allowlist Check:\n";
$client_ip = getClientIpAddress();
echo "   - Client IP: $client_ip\n";
$ip_allowed = isIpAllowed();
echo "   - IP Allowed: " . ($ip_allowed ? 'YES' : 'NO') . "\n";

if (!$ip_allowed) {
    echo "   -> BLOCKED: IP not in allowlist\n";
} else {
    echo "   -> PASSED: IP is in allowlist\n";
}

// Step 5: API Key extraction
echo "\n5. API Key Extraction:\n";
$api_key = getApiKeyFromRequest();
echo "   - API Key Found: " . ($api_key ? 'YES' : 'NO') . "\n";
if ($api_key) {
    echo "   - API Key (first 8 chars): " . substr($api_key, 0, 8) . "...\n";
    echo "   - API Key Length: " . strlen($api_key) . " characters\n";
} else {
    echo "   - No API key found in request\n";
}

// Step 6: API Key validation
echo "\n6. API Key Validation:\n";
if ($api_key) {
    $is_valid = isValidApiKey($api_key);
    echo "   - API Key Valid: " . ($is_valid ? 'YES' : 'NO') . "\n";
    
    // Debug: Show configured keys (first 8 chars only for security)
    echo "   - Configured API Keys:\n";
    foreach ($api_settings['api_keys'] as $key => $description) {
        echo "     * " . substr($key, 0, 8) . "... -> $description\n";
    }
    
    if (!$is_valid) {
        echo "   -> FAILED: API key not in configuration\n";
    } else {
        echo "   -> PASSED: API key is valid\n";
    }
} else {
    echo "   -> SKIPPED: No API key to validate\n";
}

// Step 7: Overall authentication result
echo "\n7. Authentication Result:\n";
if (!$api_settings['require_api_key']) {
    echo "   -> ALLOWED: API key authentication disabled\n";
} elseif ($is_exempt) {
    echo "   -> ALLOWED: Endpoint is exempt from authentication\n";
} elseif (!$ip_allowed) {
    echo "   -> BLOCKED: IP address not allowed\n";
} elseif (!$api_key) {
    echo "   -> BLOCKED: No API key provided\n";
} elseif (!isValidApiKey($api_key)) {
    echo "   -> BLOCKED: Invalid API key\n";
} else {
    echo "   -> ALLOWED: All authentication checks passed\n";
}

// Step 8: Headers debug
echo "\n8. Headers Debug:\n";
$headers = getAllRequestHeaders();
echo "   - Total headers: " . count($headers) . "\n";
foreach ($headers as $name => $value) {
    if (stripos($name, 'api') !== false || stripos($name, 'auth') !== false) {
        echo "   - $name: $value\n";
    }
}

echo "\n=== Test Complete ===\n";
?>
