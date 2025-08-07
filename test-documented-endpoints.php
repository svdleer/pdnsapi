<?php
/**
 * Test script to validate all endpoints documented in OpenAPI are working
 */

require_once 'php-api/includes/autoloader.php';

function test_endpoint($method, $url, $data = null, $description = "") {
    global $api_config;
    
    echo "\n=== Testing: $description ===\n";
    echo "Method: $method\n";
    echo "URL: $url\n";
    if ($data) {
        echo "Data: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    }
    echo "Request: ";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($api_config['api_key'] . ':')
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    switch ($method) {
        case 'POST':
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
            break;
        case 'PUT':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
            break;
        case 'DELETE':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            break;
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "CURL ERROR: $error\n";
        return false;
    }
    
    echo "Status: $http_code\n";
    
    $decoded = json_decode($response, true);
    if ($decoded) {
        echo "Response: " . json_encode($decoded, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "Raw Response: " . substr($response, 0, 500) . "\n";
    }
    
    return $http_code >= 200 && $http_code < 400;
}

// Configuration
$base_url = "https://localhost/php-api";

echo "PowerDNS Admin API - Endpoint Testing\n";
echo "====================================\n";
echo "Base URL: $base_url\n";

$tests = [
    // Status endpoint tests
    [
        'method' => 'GET',
        'url' => "$base_url/status",
        'description' => 'API Status'
    ],
    [
        'method' => 'GET',
        'url' => "$base_url/status?action=test_connection",
        'description' => 'Test Connection'
    ],
    [
        'method' => 'GET',
        'url' => "$base_url/status?action=health",
        'description' => 'Health Check'
    ],
    
    // Accounts endpoint tests
    [
        'method' => 'GET',
        'url' => "$base_url/accounts",
        'description' => 'Get All Accounts'
    ],
    [
        'method' => 'GET',
        'url' => "$base_url/accounts",
        'data' => ['username' => 'admin'],
        'description' => 'Get Account by Username'
    ],
    [
        'method' => 'POST',
        'url' => "$base_url/accounts",
        'data' => [
            'username' => 'test_documented_' . time(),
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ],
        'description' => 'Create Test Account'
    ],
    
    // Domains endpoint tests - Basic operations
    [
        'method' => 'GET',
        'url' => "$base_url/domains",
        'description' => 'Get All Domains'
    ],
    [
        'method' => 'GET',
        'url' => "$base_url/domains?sync=true",
        'description' => 'Get All Domains with Sync'
    ],
    [
        'method' => 'GET',
        'url' => "$base_url/domains",
        'data' => ['id' => 1],
        'description' => 'Get Domain by ID'
    ],
    [
        'method' => 'GET',
        'url' => "$base_url/domains",
        'data' => ['account_id' => 1],
        'description' => 'Get Domains by Account ID'
    ],
    [
        'method' => 'GET',
        'url' => "$base_url/domains?account_id=1",
        'description' => 'Get Domains by Account ID (query param)'
    ],
    
    // Domain update test
    [
        'method' => 'PUT',
        'url' => "$base_url/domains",
        'data' => [
            'id' => 1,
            'account_id' => 2
        ],
        'description' => 'Update Domain Account Assignment'
    ],
    
    // Domain add to account test
    [
        'method' => 'POST',
        'url' => "$base_url/domains",
        'data' => [
            'domain_name' => 'test-domain.example.com.',
            'account_id' => 1
        ],
        'description' => 'Add Domain to Account'
    ],
    
    // Domain-Account operations
    [
        'method' => 'POST',
        'url' => "$base_url/domain-account?action=list",
        'data' => ['account_id' => 1],
        'description' => 'List Account Domains'
    ],
    [
        'method' => 'POST',
        'url' => "$base_url/domain-account?action=add",
        'data' => [
            'domain_name' => 'test-assign.example.com.',
            'account_id' => 1
        ],
        'description' => 'Add Domain to Account (via domain-account endpoint)'
    ],
    [
        'method' => 'POST',
        'url' => "$base_url/domain-account?action=remove",
        'data' => ['domain_name' => 'test-assign.example.com.'],
        'description' => 'Remove Domain from Account'
    ]
];

$passed = 0;
$total = count($tests);

foreach ($tests as $test) {
    $success = test_endpoint(
        $test['method'],
        $test['url'],
        $test['data'] ?? null,
        $test['description']
    );
    
    if ($success) {
        $passed++;
        echo "✅ PASSED\n";
    } else {
        echo "❌ FAILED\n";
    }
    
    // Small delay between requests
    usleep(250000); // 250ms
}

echo "\n=== SUMMARY ===\n";
echo "Passed: $passed/$total tests\n";
echo "Success Rate: " . round(($passed/$total) * 100, 2) . "%\n";

if ($passed === $total) {
    echo "\n🎉 All documented endpoints are working!\n";
} else {
    echo "\n⚠️  Some endpoints need attention.\n";
}
?>
