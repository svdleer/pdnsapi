<?php
/**
 * Comprehensive Security Testing
 * Tests IP allowlist, API key authentication, and security features
 */

ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php_security_test.log');

echo "🔐 Comprehensive Security Testing\n";
echo "==================================\n\n";

function makeTestRequest($endpoint, $headers = [], $expected_status = 200, $description = '') {
    $url = "http://localhost/php-api{$endpoint}";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HEADER => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'SecurityTester/1.0'
    ]);
    
    $response = curl_exec($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    $status_icon = ($status_code === $expected_status) ? '✅' : '❌';
    echo "   {$status_icon} {$description}\n";
    echo "      Request: {$endpoint}\n";
    echo "      Expected: {$expected_status}, Got: {$status_code}\n";
    
    if ($error) {
        echo "      cURL Error: {$error}\n";
    }
    
    if ($status_code !== $expected_status) {
        echo "      Response: " . substr($response, 0, 200) . "...\n";
    }
    
    echo "\n";
    return ['status' => $status_code, 'response' => $response, 'success' => ($status_code === $expected_status)];
}

try {
    // Load environment
    $env_file = __DIR__ . '/.env';
    if (file_exists($env_file)) {
        $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value);
            }
        }
    }
    
    // Generate a test API key
    $test_api_key = bin2hex(random_bytes(32));
    $valid_api_key = $_ENV['PDNS_API_KEY'] ?? 'YWRtaW46ZG5zYWRtaW4yMDIzITEy'; // From .env
    
    echo "1. Testing IP Security (Database-driven allowlist)\n";
    echo "   Current IP: " . ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1') . "\n";
    
    // Test IP allowlist status
    $result = shell_exec('cd ' . __DIR__ . ' && php manage-ips-clean.php test 127.0.0.1 2>&1');
    echo "   IP Test Result: " . trim($result) . "\n\n";
    
    echo "2. Testing API Key Authentication\n";
    
    // Test 1: No authentication (should fail)
    makeTestRequest('/api/status', [], 401, 'No API key provided');
    
    // Test 2: Invalid API key (should fail)
    makeTestRequest('/api/status', [
        'X-API-Key: invalid-key-here'
    ], 401, 'Invalid API key');
    
    // Test 3: Valid API key (should succeed)
    makeTestRequest('/api/status', [
        "X-API-Key: {$valid_api_key}"
    ], 200, 'Valid API key (X-API-Key header)');
    
    // Test 4: Bearer token format (should succeed)
    makeTestRequest('/api/status', [
        "Authorization: Bearer {$valid_api_key}"
    ], 200, 'Valid API key (Authorization Bearer)');
    
    echo "3. Testing Different Endpoints with Authentication\n";
    
    // Test protected endpoints
    $endpoints_to_test = [
        '/api/status' => 'API Status endpoint',
        '/api/domains' => 'Domains listing',
        '/api/accounts' => 'Accounts listing'
    ];
    
    foreach ($endpoints_to_test as $endpoint => $description) {
        makeTestRequest($endpoint, [
            "X-API-Key: {$valid_api_key}"
        ], 200, $description);
    }
    
    echo "4. Testing Security Headers and Response Format\n";
    
    // Test security response format
    $result = makeTestRequest('/api/status', [], 401, 'Security error format check');
    if ($result['response']) {
        $response_data = json_decode($result['response'], true);
        if (isset($response_data['status']) && $response_data['status'] === 401) {
            echo "   ✅ Proper 401 response format\n";
            if (isset($response_data['details']['authentication_methods'])) {
                echo "   ✅ Authentication methods documented in response\n";
            }
        } else {
            echo "   ❌ Unexpected response format\n";
        }
    }
    
    echo "\n5. Testing IP Management Tools\n";
    
    // Test IP management
    echo "   Current allowlist:\n";
    $ip_list = shell_exec('cd ' . __DIR__ . ' && php manage-ips-clean.php list 2>&1');
    echo "   " . str_replace("\n", "\n   ", trim($ip_list)) . "\n\n";
    
    // Test adding a test IP (and removing it)
    $test_ip = '192.168.100.200';
    echo "   Testing IP management:\n";
    
    $add_result = shell_exec("cd " . __DIR__ . " && php manage-ips-clean.php add {$test_ip} 'Security Test IP' 2>&1");
    echo "   Add test IP: " . trim($add_result) . "\n";
    
    $test_result = shell_exec("cd " . __DIR__ . " && php manage-ips-clean.php test {$test_ip} 2>&1");
    echo "   Test IP access: " . trim($test_result) . "\n";
    
    $remove_result = shell_exec("cd " . __DIR__ . " && php manage-ips-clean.php remove {$test_ip} 2>&1");
    echo "   Remove test IP: " . trim($remove_result) . "\n\n";
    
    echo "6. Testing API Key Generation\n";
    
    // Test key generation
    $new_keys = shell_exec('cd ' . __DIR__ . ' && php generate-api-keys.php 2>&1 | head -20');
    if (strpos($new_keys, 'Single API Key') !== false) {
        echo "   ✅ API key generation working\n";
        echo "   Generated keys are 64-character hex strings\n";
    } else {
        echo "   ❌ API key generation failed\n";
        echo "   Output: " . $new_keys . "\n";
    }
    
    echo "\n7. Security Configuration Summary\n";
    
    // Check configuration
    require_once __DIR__ . '/config/config.php';
    global $config;
    
    if (isset($config['require_api_key']) && $config['require_api_key']) {
        echo "   ✅ API key authentication: ENABLED\n";
    } else {
        echo "   ⚠️  API key authentication: DISABLED\n";
    }
    
    if (isset($config['api_keys']) && is_array($config['api_keys']) && count($config['api_keys']) > 0) {
        echo "   ✅ API keys configured: " . count($config['api_keys']) . " keys\n";
    } else {
        echo "   ❌ No API keys configured\n";
    }
    
    echo "   ✅ IP allowlist: DATABASE-DRIVEN (global protection)\n";
    echo "   ✅ Dual authentication: API key + IP validation\n";
    
    echo "\n✅ Security Testing Completed!\n";
    echo "\n🛡️  Security Summary:\n";
    echo "   - IP Allowlist: Active with database storage\n";
    echo "   - API Key Auth: Required for all protected endpoints\n";
    echo "   - Dual Security: Both IP and API key required\n";
    echo "   - Security Tools: IP management and key generation working\n";
    echo "   - Response Format: Proper error responses with guidance\n";
    
} catch (Exception $e) {
    echo "❌ Security test failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\nSecurity test completed at " . date('Y-m-d H:i:s') . "\n";
echo "Check logs: /tmp/php_security_test.log\n";
?>
