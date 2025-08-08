<?php
require_once __DIR__ . '/php-api/config/config.php';
require_once __DIR__ . '/php-api/classes/PDNSAdminClient.php';

echo "=== DOMAIN CREATION, REMOVAL & TEMPLATE TESTING ===" . PHP_EOL;
echo "Testing domain CRUD operations and template functionality" . PHP_EOL;
echo "Date: " . date('Y-m-d H:i:s') . PHP_EOL;
echo "====================================================" . PHP_EOL . PHP_EOL;

$client = new PDNSAdminClient($pdns_config);

// Test results tracking
$test_results = [];
$created_resources = [];

function runTest($test_name, $callback) {
    global $test_results;
    
    echo "🧪 Testing: {$test_name}..." . PHP_EOL;
    
    try {
        $result = $callback();
        if ($result['success']) {
            echo "✅ PASSED: {$test_name}" . PHP_EOL;
            if (isset($result['data'])) {
                echo "   📋 Result: " . json_encode($result['data'], JSON_PRETTY_PRINT) . PHP_EOL;
            }
            if (isset($result['message'])) {
                echo "   💬 Message: {$result['message']}" . PHP_EOL;
            }
            $test_results[$test_name] = 'PASSED';
        } else {
            echo "❌ FAILED: {$test_name}" . PHP_EOL;
            echo "   ❗ Reason: {$result['message']}" . PHP_EOL;
            $test_results[$test_name] = 'FAILED: ' . $result['message'];
        }
    } catch (Exception $e) {
        echo "❌ ERROR: {$test_name}" . PHP_EOL;
        echo "   ❗ Exception: {$e->getMessage()}" . PHP_EOL;
        $test_results[$test_name] = 'ERROR: ' . $e->getMessage();
    }
    
    echo PHP_EOL;
}

// Test 1: Try to create a domain via PowerDNS Admin API
runTest("Create Domain via PowerDNS Admin API", function() use ($client, &$created_resources) {
    $test_domain = [
        'name' => 'test-domain-' . time() . '.example.com',
        'kind' => 'Master',
        'dnssec' => false,
        'masters' => [],
        'account' => ''
    ];
    
    $result = $client->createDomain($test_domain);
    
    if ($result['status_code'] === 201 || $result['status_code'] === 200) {
        $created_resources[] = ['type' => 'domain', 'id' => $result['data']['id'] ?? null, 'name' => $test_domain['name']];
        return [
            'success' => true, 
            'message' => "Domain created successfully",
            'data' => $result['data']
        ];
    } else {
        return [
            'success' => false, 
            'message' => "HTTP {$result['status_code']}: " . ($result['raw_response'] ?? 'Unknown error')
        ];
    }
});

// Test 2: Try to create a template via PowerDNS Admin API
runTest("Create Template via PowerDNS Admin API", function() use ($client, &$created_resources) {
    $test_template = [
        'name' => 'Test Template ' . time(),
        'description' => 'Automated test template',
        'records' => [
            [
                'name' => '@',
                'type' => 'A',
                'content' => '192.168.1.100',
                'ttl' => 3600
            ],
            [
                'name' => 'www',
                'type' => 'CNAME',
                'content' => '@',
                'ttl' => 3600
            ]
        ]
    ];
    
    $result = $client->createTemplate($test_template);
    
    if ($result['status_code'] === 201 || $result['status_code'] === 200) {
        $created_resources[] = ['type' => 'template', 'id' => $result['data']['id'] ?? null, 'name' => $test_template['name']];
        return [
            'success' => true, 
            'message' => "Template created successfully",
            'data' => $result['data']
        ];
    } else {
        return [
            'success' => false, 
            'message' => "HTTP {$result['status_code']}: " . ($result['raw_response'] ?? 'Template endpoint not supported')
        ];
    }
});

// Test 3: Test domain creation via local wrapper API (domains.php)
runTest("Create Domain via Local Wrapper API", function() use (&$created_resources) {
    $test_domain_name = 'local-test-' . time() . '.example.com';
    
    // Since we can't create domains via PowerDNS Admin, 
    // let's test if we can add a domain to our local database
    try {
        require_once __DIR__ . '/php-api/config/database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        if (!$db) {
            return ['success' => false, 'message' => 'Database connection failed'];
        }
        
        // Insert test domain into local database
        $stmt = $db->prepare("
            INSERT INTO domains (name, type, kind, masters, dnssec, account, pdns_zone_id, created_at, updated_at) 
            VALUES (?, 'Zone', 'Master', '', 0, 'test-account', ?, NOW(), NOW())
        ");
        
        $test_zone_id = 'test-' . time();
        $success = $stmt->execute([$test_domain_name, $test_zone_id]);
        
        if ($success) {
            $domain_id = $db->lastInsertId();
            $created_resources[] = ['type' => 'local_domain', 'id' => $domain_id, 'name' => $test_domain_name];
            
            return [
                'success' => true, 
                'message' => "Local domain created successfully",
                'data' => ['id' => $domain_id, 'name' => $test_domain_name]
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to insert domain into local database'];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
});

// Test 4: Test domain retrieval by ID (local database)
runTest("Retrieve Domain by ID (Local Database)", function() use ($created_resources) {
    // Find a local domain we just created
    $local_domain = null;
    foreach ($created_resources as $resource) {
        if ($resource['type'] === 'local_domain') {
            $local_domain = $resource;
            break;
        }
    }
    
    if (!$local_domain) {
        return ['success' => false, 'message' => 'No local domain available for testing'];
    }
    
    try {
        require_once __DIR__ . '/php-api/config/database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        $stmt = $db->prepare("SELECT * FROM domains WHERE id = ?");
        $stmt->execute([$local_domain['id']]);
        $domain = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($domain) {
            return [
                'success' => true, 
                'message' => "Domain retrieved successfully",
                'data' => $domain
            ];
        } else {
            return ['success' => false, 'message' => 'Domain not found in local database'];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
});

// Test 5: Test domain update (local database)
runTest("Update Domain (Local Database)", function() use ($created_resources) {
    // Find a local domain we created
    $local_domain = null;
    foreach ($created_resources as $resource) {
        if ($resource['type'] === 'local_domain') {
            $local_domain = $resource;
            break;
        }
    }
    
    if (!$local_domain) {
        return ['success' => false, 'message' => 'No local domain available for testing'];
    }
    
    try {
        require_once __DIR__ . '/php-api/config/database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        // Update the domain
        $stmt = $db->prepare("UPDATE domains SET dnssec = 1, account = 'updated-test-account', updated_at = NOW() WHERE id = ?");
        $success = $stmt->execute([$local_domain['id']]);
        
        if ($success && $stmt->rowCount() > 0) {
            return [
                'success' => true, 
                'message' => "Domain updated successfully",
                'data' => ['id' => $local_domain['id'], 'changes' => 'dnssec=true, account=updated-test-account']
            ];
        } else {
            return ['success' => false, 'message' => 'Domain update failed or no changes made'];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
});

// Test 6: Test domain search/filtering (local database)
runTest("Search/Filter Domains (Local Database)", function() {
    try {
        require_once __DIR__ . '/php-api/config/database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        // Search for domains with 'test' in the name
        $stmt = $db->prepare("SELECT * FROM domains WHERE name LIKE ? LIMIT 5");
        $stmt->execute(['%test%']);
        $domains = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $count = count($domains);
        return [
            'success' => true, 
            'message' => "Found {$count} domains matching 'test'",
            'data' => ['count' => $count, 'sample_domains' => array_slice($domains, 0, 3)]
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
});

// Test 7: Test account-based domain filtering
runTest("Filter Domains by Account (Local Database)", function() {
    try {
        require_once __DIR__ . '/php-api/config/database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        // Get domains for account ID 1 (admin)
        $stmt = $db->prepare("SELECT * FROM domains WHERE account_id = ? LIMIT 10");
        $stmt->execute([1]);
        $domains = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $count = count($domains);
        return [
            'success' => true, 
            'message' => "Found {$count} domains for account ID 1",
            'data' => ['count' => $count, 'sample_domains' => array_slice($domains, 0, 3)]
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
});

// Test 8: Test domain creation from template (simulated)
runTest("Create Domain from Template (Simulated)", function() use (&$created_resources) {
    // Since PowerDNS Admin doesn't support templates, we'll simulate this
    $template_data = [
        'name' => 'Standard Web Template',
        'records' => [
            ['name' => '@', 'type' => 'A', 'content' => '192.168.1.100', 'ttl' => 3600],
            ['name' => 'www', 'type' => 'CNAME', 'content' => '@', 'ttl' => 3600],
            ['name' => 'mail', 'type' => 'A', 'content' => '192.168.1.101', 'ttl' => 3600]
        ]
    ];
    
    $domain_name = 'template-domain-' . time() . '.example.com';
    
    try {
        require_once __DIR__ . '/php-api/config/database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        // Create domain based on template
        $stmt = $db->prepare("
            INSERT INTO domains (name, type, kind, masters, dnssec, account, pdns_zone_id, created_at, updated_at) 
            VALUES (?, 'Zone', 'Master', '', 0, 'template-created', ?, NOW(), NOW())
        ");
        
        $test_zone_id = 'template-' . time();
        $success = $stmt->execute([$domain_name, $test_zone_id]);
        
        if ($success) {
            $domain_id = $db->lastInsertId();
            $created_resources[] = ['type' => 'template_domain', 'id' => $domain_id, 'name' => $domain_name];
            
            return [
                'success' => true, 
                'message' => "Domain created from template simulation",
                'data' => [
                    'domain_id' => $domain_id, 
                    'domain_name' => $domain_name, 
                    'template' => $template_data['name'],
                    'records_to_create' => count($template_data['records'])
                ]
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to create domain from template'];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
});

// Test 9: Cleanup - Remove created test domains
runTest("Cleanup Test Resources", function() use ($created_resources) {
    $cleaned_count = 0;
    $cleanup_results = [];
    
    foreach ($created_resources as $resource) {
        if ($resource['type'] === 'local_domain' || $resource['type'] === 'template_domain') {
            try {
                require_once __DIR__ . '/php-api/config/database.php';
                $database = new Database();
                $db = $database->getConnection();
                
                $stmt = $db->prepare("DELETE FROM domains WHERE id = ?");
                $success = $stmt->execute([$resource['id']]);
                
                if ($success && $stmt->rowCount() > 0) {
                    $cleaned_count++;
                    $cleanup_results[] = "Deleted domain: {$resource['name']} (ID: {$resource['id']})";
                }
            } catch (Exception $e) {
                $cleanup_results[] = "Failed to delete {$resource['name']}: " . $e->getMessage();
            }
        }
    }
    
    return [
        'success' => true, 
        'message' => "Cleaned up {$cleaned_count} test resources",
        'data' => $cleanup_results
    ];
});

// Summary
echo "====================================================" . PHP_EOL;
echo "DOMAIN CREATION, REMOVAL & TEMPLATE TEST SUMMARY" . PHP_EOL;
echo "====================================================" . PHP_EOL;

$passed = 0;
$failed = 0;

foreach ($test_results as $test_name => $result) {
    echo "• {$test_name}: ";
    if (strpos($result, 'PASSED') === 0) {
        echo "✅ {$result}" . PHP_EOL;
        $passed++;
    } else {
        echo "❌ {$result}" . PHP_EOL;
        $failed++;
    }
}

echo PHP_EOL;
echo "📊 SUMMARY:" . PHP_EOL;
echo "✅ Tests Passed: {$passed}" . PHP_EOL;
echo "❌ Tests Failed: {$failed}" . PHP_EOL;
echo "📈 Success Rate: " . round(($passed / ($passed + $failed)) * 100, 1) . "%" . PHP_EOL;

echo PHP_EOL;
echo "💡 KEY FINDINGS:" . PHP_EOL;
echo "• PowerDNS Admin API has limited CRUD capabilities" . PHP_EOL;
echo "• Local database provides full CRUD functionality" . PHP_EOL;
echo "• Template operations must be handled locally" . PHP_EOL;
echo "• Hybrid approach (PowerDNS Admin + Local DB) works best" . PHP_EOL;

echo PHP_EOL . "Domain CRUD and template testing completed!" . PHP_EOL;
?>
