<?php
/**
 * PDNSAdmin API Endpoint Discovery Script
 */

// Mock web environment for CLI testing
$_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';

echo "PDNSAdmin API Endpoint Discovery\n";
echo "=================================\n\n";

$base_domain = 'https://dnsadmin.avant.nl';
$credentials = [
    'username' => 'admin',
    'password' => 'dnVeku8jeku'
];

$test_endpoints = [
    '/api/v1/pdnsadmin/zones',
    '/api/v1/zones',
    '/api/v1/servers/localhost/zones',
    '/api/pdnsadmin/zones',
    '/api/zones',
    '/pdnsadmin/api/v1/zones',
    '/api/v1/pdnsadmin/accounts',
    '/api/v1/accounts'
];

function testEndpoint($url, $credentials) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERPWD, $credentials['username'] . ':' . $credentials['password']);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'status_code' => $http_code,
        'response' => $response,
        'error' => $error
    ];
}

foreach ($test_endpoints as $endpoint) {
    $full_url = $base_domain . $endpoint;
    echo "Testing: $full_url\n";
    
    $result = testEndpoint($full_url, $credentials);
    
    if ($result['error']) {
        echo "  ❌ cURL Error: " . $result['error'] . "\n";
    } else {
        echo "  📡 Status: " . $result['status_code'] . "\n";
        
        if ($result['status_code'] == 200) {
            echo "  ✅ SUCCESS! This endpoint works!\n";
            $data = json_decode($result['response'], true);
            if (is_array($data)) {
                echo "  📊 Response contains " . count($data) . " items\n";
            }
        } elseif ($result['status_code'] == 401) {
            echo "  🔐 Unauthorized - endpoint exists but credentials invalid\n";
        } elseif ($result['status_code'] == 403) {
            echo "  🚫 Forbidden - endpoint exists but access denied\n";
        } elseif ($result['status_code'] == 404) {
            echo "  ❌ Not Found - endpoint doesn't exist\n";
        } else {
            echo "  ⚠️  Other response\n";
        }
        
        // Show first 200 chars of response for debugging
        if (strlen($result['response']) > 0) {
            echo "  📄 Response: " . substr($result['response'], 0, 200) . "...\n";
        }
    }
    echo "\n";
}

echo "=================================\n";
echo "Discovery completed.\n";
?>
