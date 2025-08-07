<?php
require_once __DIR__ . '/php-api/config/config.php';
require_once __DIR__ . '/php-api/classes/PDNSAdminClient.php';

echo "=== COMPREHENSIVE DOMAIN TESTING ===" . PHP_EOL;
echo "Testing all domain endpoints and functionality" . PHP_EOL;
echo "Date: " . date('Y-m-d H:i:s') . PHP_EOL;
echo "========================================" . PHP_EOL . PHP_EOL;

// Initialize client
$client = new PDNSAdminClient($pdns_config);

// Test results tracking
$tests_passed = 0;
$tests_failed = 0;
$test_results = [];

function runTest($test_name, $callback) {
    global $tests_passed, $tests_failed, $test_results;
    
    echo "🧪 Testing: {$test_name}..." . PHP_EOL;
    
    try {
        $result = $callback();
        if ($result['success']) {
            echo "✅ PASSED: {$test_name}" . PHP_EOL;
            $tests_passed++;
            $test_results[$test_name] = 'PASSED';
        } else {
            echo "❌ FAILED: {$test_name} - {$result['message']}" . PHP_EOL;
            $tests_failed++;
            $test_results[$test_name] = 'FAILED: ' . $result['message'];
        }
    } catch (Exception $e) {
        echo "❌ ERROR: {$test_name} - Exception: {$e->getMessage()}" . PHP_EOL;
        $tests_failed++;
        $test_results[$test_name] = 'ERROR: ' . $e->getMessage();
    }
    
    echo PHP_EOL;
}

// Test 1: Get All Domains
runTest("Get All Domains", function() use ($client) {
    $result = $client->getAllDomains();
    
    if ($result['status_code'] !== 200) {
        return ['success' => false, 'message' => "HTTP {$result['status_code']}: " . json_encode($result['data'])];
    }
    
    $domain_count = is_array($result['data']) ? count($result['data']) : 0;
    echo "   📊 Found {$domain_count} domains" . PHP_EOL;
    
    if ($domain_count > 0) {
        $first_domain = $result['data'][0];
        echo "   🔍 Sample domain: " . ($first_domain['name'] ?? 'N/A') . PHP_EOL;
        
        // Store first domain for later tests
        $GLOBALS['test_domain_id'] = $first_domain['id'] ?? null;
        $GLOBALS['test_domain_name'] = $first_domain['name'] ?? null;
    }
    
    return ['success' => true, 'message' => "Retrieved {$domain_count} domains"];
});

// Test 2: Get Individual Domain (if we have one)
if (isset($GLOBALS['test_domain_id'])) {
    runTest("Get Individual Domain", function() use ($client) {
        $result = $client->getDomain($GLOBALS['test_domain_id']);
        
        if ($result['status_code'] !== 200) {
            return ['success' => false, 'message' => "HTTP {$result['status_code']}: " . json_encode($result['data'])];
        }
        
        $domain = $result['data'];
        echo "   📋 Domain: " . ($domain['name'] ?? 'N/A') . PHP_EOL;
        echo "   📋 ID: " . ($domain['id'] ?? 'N/A') . PHP_EOL;
        
        return ['success' => true, 'message' => "Retrieved domain details"];
    });
} else {
    echo "⚠️  Skipping individual domain test - no domains available" . PHP_EOL . PHP_EOL;
}

// Test 3: Test Domain Creation (will likely fail as most APIs don't support this)
runTest("Create Test Domain", function() use ($client) {
    $test_domain = [
        'name' => 'test-domain-' . time() . '.example.com',
        'kind' => 'Master',
        'dnssec' => false
    ];
    
    $result = $client->createDomain($test_domain);
    
    if ($result['status_code'] === 201 || $result['status_code'] === 200) {
        echo "   ✨ Created domain: {$test_domain['name']}" . PHP_EOL;
        $GLOBALS['created_domain'] = $result['data'];
        return ['success' => true, 'message' => "Domain created successfully"];
    } else {
        // Expected failure for most PowerDNS Admin setups
        return ['success' => false, 'message' => "HTTP {$result['status_code']} (expected for most setups)"];
    }
});

// Test 4: Update Domain (if we have one)
if (isset($GLOBALS['test_domain_id'])) {
    runTest("Update Domain", function() use ($client) {
        $update_data = [
            'dnssec' => true,
            'kind' => 'Master'
        ];
        
        $result = $client->updateDomain($GLOBALS['test_domain_id'], $update_data);
        
        if ($result['status_code'] === 200 || $result['status_code'] === 204) {
            return ['success' => true, 'message' => "Domain updated successfully"];
        } else {
            return ['success' => false, 'message' => "HTTP {$result['status_code']}: " . json_encode($result['data'])];
        }
    });
} else {
    echo "⚠️  Skipping domain update test - no domains available" . PHP_EOL . PHP_EOL;
}

// Test 5: Test Templates
echo "=== TEMPLATE TESTING ===" . PHP_EOL;

runTest("Get All Templates", function() use ($client) {
    $result = $client->getAllTemplates();
    
    if ($result['status_code'] !== 200) {
        return ['success' => false, 'message' => "HTTP {$result['status_code']}: " . json_encode($result['data'])];
    }
    
    $template_count = is_array($result['data']) ? count($result['data']) : 0;
    echo "   📊 Found {$template_count} templates" . PHP_EOL;
    
    if ($template_count > 0) {
        $first_template = $result['data'][0];
        echo "   🔍 Sample template: " . ($first_template['name'] ?? 'N/A') . PHP_EOL;
        $GLOBALS['test_template_id'] = $first_template['id'] ?? null;
    }
    
    return ['success' => true, 'message' => "Retrieved {$template_count} templates"];
});

// Test 6: Get Individual Template (if we have one)
if (isset($GLOBALS['test_template_id'])) {
    runTest("Get Individual Template", function() use ($client) {
        $result = $client->getTemplate($GLOBALS['test_template_id']);
        
        if ($result['status_code'] !== 200) {
            return ['success' => false, 'message' => "HTTP {$result['status_code']}: " . json_encode($result['data'])];
        }
        
        $template = $result['data'];
        echo "   📋 Template: " . ($template['name'] ?? 'N/A') . PHP_EOL;
        echo "   📋 ID: " . ($template['id'] ?? 'N/A') . PHP_EOL;
        
        return ['success' => true, 'message' => "Retrieved template details"];
    });
} else {
    echo "⚠️  Skipping individual template test - no templates available" . PHP_EOL . PHP_EOL;
}

// Test 7: Create Template
runTest("Create Test Template", function() use ($client) {
    $test_template = [
        'name' => 'Test Template ' . time(),
        'description' => 'Test template created by automated testing',
        'records' => [
            [
                'name' => '@',
                'type' => 'A',
                'content' => '192.168.1.100',
                'ttl' => 3600
            ]
        ]
    ];
    
    $result = $client->createTemplate($test_template);
    
    if ($result['status_code'] === 201 || $result['status_code'] === 200) {
        echo "   ✨ Created template: {$test_template['name']}" . PHP_EOL;
        $GLOBALS['created_template'] = $result['data'];
        return ['success' => true, 'message' => "Template created successfully"];
    } else {
        return ['success' => false, 'message' => "HTTP {$result['status_code']}: " . json_encode($result['data'])];
    }
});

// Test 8: Create Domain from Template (if we have templates)
if (isset($GLOBALS['test_template_id']) || isset($GLOBALS['created_template'])) {
    runTest("Create Domain from Template", function() use ($client) {
        $template_id = $GLOBALS['created_template']['id'] ?? $GLOBALS['test_template_id'];
        
        $domain_data = [
            'name' => 'template-test-' . time() . '.example.com',
            'description' => 'Domain created from template during testing'
        ];
        
        $result = $client->createDomainFromTemplate($template_id, $domain_data);
        
        if ($result['status_code'] === 201 || $result['status_code'] === 200) {
            echo "   ✨ Created domain from template: {$domain_data['name']}" . PHP_EOL;
            return ['success' => true, 'message' => "Domain created from template successfully"];
        } else {
            return ['success' => false, 'message' => "HTTP {$result['status_code']}: " . json_encode($result['data'])];
        }
    });
}

// Test 9: Domain Connectivity and Response Time
runTest("Domain API Response Time", function() use ($client) {
    $start_time = microtime(true);
    
    $result = $client->getAllDomains();
    
    $end_time = microtime(true);
    $response_time = round(($end_time - $start_time) * 1000, 2);
    
    echo "   ⏱️  Response time: {$response_time}ms" . PHP_EOL;
    
    if ($result['status_code'] === 200) {
        if ($response_time < 5000) {
            return ['success' => true, 'message' => "Good response time: {$response_time}ms"];
        } else {
            return ['success' => false, 'message' => "Slow response time: {$response_time}ms"];
        }
    } else {
        return ['success' => false, 'message' => "API connectivity failed"];
    }
});

// Test 10: Error Handling Test
runTest("Error Handling Test", function() use ($client) {
    // Try to get a non-existent domain
    $result = $client->getDomain(99999999);
    
    if ($result['status_code'] === 404) {
        return ['success' => true, 'message' => "Proper 404 error handling"];
    } else {
        return ['success' => false, 'message' => "Unexpected response: HTTP {$result['status_code']}"];
    }
});

// Summary
echo "========================================" . PHP_EOL;
echo "DOMAIN TESTING SUMMARY" . PHP_EOL;
echo "========================================" . PHP_EOL;
echo "✅ Tests Passed: {$tests_passed}" . PHP_EOL;
echo "❌ Tests Failed: {$tests_failed}" . PHP_EOL;
echo "📊 Total Tests: " . ($tests_passed + $tests_failed) . PHP_EOL;
echo "📈 Success Rate: " . round(($tests_passed / ($tests_passed + $tests_failed)) * 100, 1) . "%" . PHP_EOL;
echo PHP_EOL;

// Detailed Results
echo "DETAILED RESULTS:" . PHP_EOL;
echo "=================" . PHP_EOL;
foreach ($test_results as $test_name => $result) {
    echo "• {$test_name}: {$result}" . PHP_EOL;
}

echo PHP_EOL . "Domain testing completed!" . PHP_EOL;
?>
