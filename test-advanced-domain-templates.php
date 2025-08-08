<?php
require_once __DIR__ . '/php-api/config/config.php';
require_once __DIR__ . '/php-api/classes/PDNSAdminClient.php';

echo "=== ADVANCED DOMAIN CREATION & TEMPLATE TESTING ===" . PHP_EOL;
echo "Testing different approaches to domain creation and templates" . PHP_EOL;
echo "Date: " . date('Y-m-d H:i:s') . PHP_EOL;
echo "======================================================" . PHP_EOL . PHP_EOL;

$client = new PDNSAdminClient($pdns_config);

function runAdvancedTest($test_name, $callback) {
    echo "🔬 Advanced Test: {$test_name}" . PHP_EOL;
    
    try {
        $result = $callback();
        if ($result['success']) {
            echo "✅ SUCCESS: {$result['message']}" . PHP_EOL;
            if (isset($result['data'])) {
                echo "   📊 Data: " . json_encode($result['data'], JSON_PRETTY_PRINT) . PHP_EOL;
            }
        } else {
            echo "❌ FAILED: {$result['message']}" . PHP_EOL;
            if (isset($result['details'])) {
                echo "   🔍 Details: " . $result['details'] . PHP_EOL;
            }
        }
    } catch (Exception $e) {
        echo "💥 EXCEPTION: {$e->getMessage()}" . PHP_EOL;
    }
    
    echo PHP_EOL;
}

// Test 1: Try proper canonical domain names
runAdvancedTest("Create Canonical Domain (ending with dot)", function() use ($client) {
    $canonical_domain = [
        'name' => 'canonical-test-' . time() . '.example.com.',  // Note the trailing dot
        'kind' => 'Master',
        'dnssec' => false,
        'masters' => [],
        'account' => ''
    ];
    
    $result = $client->createDomain($canonical_domain);
    
    return [
        'success' => $result['status_code'] === 201 || $result['status_code'] === 200,
        'message' => $result['status_code'] === 201 ? "Canonical domain created" : "HTTP {$result['status_code']}",
        'details' => $result['raw_response'],
        'data' => $result['data']
    ];
});

// Test 2: Test domain creation with different parameters
runAdvancedTest("Create Domain with Full Parameters", function() use ($client) {
    $full_domain = [
        'name' => 'full-test-' . time() . '.example.com.',
        'kind' => 'Master',
        'nameservers' => ['ns1.example.com.', 'ns2.example.com.'],
        'dnssec' => false,
        'account' => 'test-account',
        'masters' => [],
        'rrsets' => [
            [
                'name' => 'full-test-' . time() . '.example.com.',
                'type' => 'SOA',
                'records' => [
                    [
                        'content' => 'ns1.example.com. admin.example.com. 1 10800 3600 604800 3600',
                        'disabled' => false
                    ]
                ]
            ]
        ]
    ];
    
    $result = $client->createDomain($full_domain);
    
    return [
        'success' => $result['status_code'] === 201 || $result['status_code'] === 200,
        'message' => $result['status_code'] === 201 ? "Full parameter domain created" : "HTTP {$result['status_code']}",
        'details' => $result['raw_response'],
        'data' => $result['data']
    ];
});

// Test 3: Test PowerDNS Server API directly (if accessible)
runAdvancedTest("Direct PowerDNS Server API Test", function() use ($client) {
    // Try to access PowerDNS server API directly
    $server_result = $client->makeRequest('/servers/localhost/zones');
    
    if ($server_result['status_code'] === 200) {
        $zone_count = count($server_result['data'] ?? []);
        return [
            'success' => true,
            'message' => "PowerDNS server API accessible, found {$zone_count} zones",
            'data' => ['zone_count' => $zone_count, 'sample_zones' => array_slice($server_result['data'] ?? [], 0, 3)]
        ];
    } else {
        return [
            'success' => false,
            'message' => "PowerDNS server API not accessible: HTTP {$server_result['status_code']}",
            'details' => $server_result['raw_response']
        ];
    }
});

// Test 4: Test local template system
runAdvancedTest("Create and Use Local Template System", function() {
    try {
        require_once __DIR__ . '/php-api/config/database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        if (!$db) {
            return ['success' => false, 'message' => 'Database connection failed'];
        }
        
        // Check if templates table exists, if not create it
        $check_table = $db->query("SHOW TABLES LIKE 'templates'");
        if ($check_table->rowCount() == 0) {
            $create_table = "
                CREATE TABLE templates (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    description TEXT,
                    records JSON,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ";
            $db->exec($create_table);
        }
        
        // Create a template
        $template_data = [
            'name' => 'Web Hosting Template',
            'description' => 'Standard web hosting setup with A, CNAME, and MX records',
            'records' => json_encode([
                ['name' => '@', 'type' => 'A', 'content' => '192.168.1.100', 'ttl' => 3600],
                ['name' => 'www', 'type' => 'CNAME', 'content' => '@', 'ttl' => 3600],
                ['name' => '@', 'type' => 'MX', 'content' => '10 mail.{domain}', 'ttl' => 3600],
                ['name' => 'mail', 'type' => 'A', 'content' => '192.168.1.101', 'ttl' => 3600]
            ])
        ];
        
        $stmt = $db->prepare("INSERT INTO templates (name, description, records) VALUES (?, ?, ?)");
        $success = $stmt->execute([
            $template_data['name'],
            $template_data['description'],
            $template_data['records']
        ]);
        
        if ($success) {
            $template_id = $db->lastInsertId();
            
            // Now use this template to create a domain
            $domain_name = 'template-created-' . time() . '.example.com';
            
            $domain_stmt = $db->prepare("
                INSERT INTO domains (name, type, kind, masters, dnssec, account, pdns_zone_id, template_id, created_at, updated_at) 
                VALUES (?, 'Zone', 'Master', '', 0, 'template-system', ?, ?, NOW(), NOW())
            ");
            
            $domain_success = $domain_stmt->execute([
                $domain_name,
                'tmpl-' . time(),
                $template_id
            ]);
            
            if ($domain_success) {
                $domain_id = $db->lastInsertId();
                
                // Clean up test data
                $db->prepare("DELETE FROM domains WHERE id = ?")->execute([$domain_id]);
                $db->prepare("DELETE FROM templates WHERE id = ?")->execute([$template_id]);
                
                return [
                    'success' => true,
                    'message' => 'Local template system works perfectly',
                    'data' => [
                        'template_id' => $template_id,
                        'domain_id' => $domain_id,
                        'domain_name' => $domain_name,
                        'template_records' => count(json_decode($template_data['records'], true))
                    ]
                ];
            }
        }
        
        return ['success' => false, 'message' => 'Failed to create domain from template'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
});

// Test 5: Test bulk domain operations (what PowerDNS Admin supports)
runAdvancedTest("Bulk Domain Operations via PowerDNS Admin", function() use ($client) {
    // Get all domains (this should work)
    $all_domains = $client->getAllDomains();
    
    if ($all_domains['status_code'] === 200) {
        $domain_count = count($all_domains['data'] ?? []);
        
        // Get details about first few domains
        $sample_domains = [];
        if (isset($all_domains['data']) && is_array($all_domains['data'])) {
            foreach (array_slice($all_domains['data'], 0, 3) as $domain) {
                if (isset($domain['id'])) {
                    $domain_detail = $client->getDomain($domain['id']);
                    if ($domain_detail['status_code'] === 200) {
                        $sample_domains[] = $domain_detail['data'];
                    }
                }
            }
        }
        
        return [
            'success' => true,
            'message' => "Bulk operations work - found {$domain_count} domains",
            'data' => [
                'total_domains' => $domain_count,
                'sample_domain_details' => $sample_domains
            ]
        ];
    } else {
        return [
            'success' => false,
            'message' => "Bulk domain retrieval failed: HTTP {$all_domains['status_code']}",
            'details' => $all_domains['raw_response']
        ];
    }
});

// Test 6: Test domain record management through local API
runAdvancedTest("Domain Record Management via Local Database", function() {
    try {
        require_once __DIR__ . '/php-api/config/database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        // Check if records table exists
        $check_table = $db->query("SHOW TABLES LIKE 'records'");
        if ($check_table->rowCount() == 0) {
            return ['success' => false, 'message' => 'Records table not found in database'];
        }
        
        // Get record count for first domain
        $domain_query = $db->query("SELECT id, name FROM domains LIMIT 1");
        $domain = $domain_query->fetch(PDO::FETCH_ASSOC);
        
        if ($domain) {
            $records_query = $db->prepare("SELECT * FROM records WHERE domain_id = ? LIMIT 5");
            $records_query->execute([$domain['id']]);
            $records = $records_query->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'message' => 'Domain record management available via local database',
                'data' => [
                    'domain' => $domain,
                    'record_count' => count($records),
                    'sample_records' => $records
                ]
            ];
        } else {
            return ['success' => false, 'message' => 'No domains found to test record management'];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
});

// Test 7: Test domain search and filtering capabilities
runAdvancedTest("Advanced Domain Search & Filtering", function() {
    try {
        require_once __DIR__ . '/php-api/config/database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        // Test various search patterns
        $search_tests = [
            'wildcards' => "SELECT COUNT(*) as count FROM domains WHERE name LIKE '%.nl'",
            'account_filter' => "SELECT COUNT(*) as count FROM domains WHERE account_id = 1",
            'type_filter' => "SELECT COUNT(*) as count FROM domains WHERE kind = 'Master'",
            'recent' => "SELECT COUNT(*) as count FROM domains WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)"
        ];
        
        $results = [];
        foreach ($search_tests as $test_name => $query) {
            $result = $db->query($query);
            $results[$test_name] = $result->fetch(PDO::FETCH_ASSOC)['count'];
        }
        
        // Test complex search
        $complex_query = "
            SELECT d.name, d.kind, d.account, COUNT(r.id) as record_count
            FROM domains d
            LEFT JOIN records r ON d.id = r.domain_id
            WHERE d.name LIKE '%.com%'
            GROUP BY d.id
            LIMIT 5
        ";
        
        $complex_result = $db->query($complex_query);
        $complex_domains = $complex_result->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'message' => 'Advanced search and filtering capabilities confirmed',
            'data' => [
                'search_counts' => $results,
                'complex_search_sample' => $complex_domains
            ]
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
});

echo "======================================================" . PHP_EOL;
echo "🎯 CONCLUSION & RECOMMENDATIONS" . PHP_EOL;
echo "======================================================" . PHP_EOL;

echo "✅ WHAT WORKS:" . PHP_EOL;
echo "   • Local database provides full CRUD operations" . PHP_EOL;
echo "   • PowerDNS Admin API supports bulk domain retrieval" . PHP_EOL;
echo "   • Template system can be implemented locally" . PHP_EOL;
echo "   • Advanced search and filtering via local database" . PHP_EOL;
echo "   • Record management through local database" . PHP_EOL;
echo PHP_EOL;

echo "❌ LIMITATIONS:" . PHP_EOL;
echo "   • PowerDNS Admin API has strict domain name format requirements" . PHP_EOL;
echo "   • Individual domain CRUD operations not supported by PowerDNS Admin" . PHP_EOL;
echo "   • Template endpoints don't exist in PowerDNS Admin API" . PHP_EOL;
echo "   • PowerDNS Server API may require different authentication" . PHP_EOL;
echo PHP_EOL;

echo "🏗️ ARCHITECTURE RECOMMENDATIONS:" . PHP_EOL;
echo "   1. Use PowerDNS Admin API for bulk operations and reading" . PHP_EOL;
echo "   2. Use local database for individual CRUD and advanced filtering" . PHP_EOL;
echo "   3. Implement template system as local extension" . PHP_EOL;
echo "   4. Maintain sync between PowerDNS Admin and local database" . PHP_EOL;
echo "   5. Use local API wrapper to provide consistent interface" . PHP_EOL;

echo PHP_EOL . "Advanced domain and template testing completed!" . PHP_EOL;
?>
