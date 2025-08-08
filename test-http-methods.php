<?php
require_once __DIR__ . '/php-api/config/config.php';
require_once __DIR__ . '/php-api/classes/PDNSAdminClient.php';

echo "=== TESTING HTTP METHODS ON WORKING ENDPOINTS ===" . PHP_EOL;
echo "Testing CRUD operations on confirmed working endpoints" . PHP_EOL;
echo "Date: " . date('Y-m-d H:i:s') . PHP_EOL;
echo "=================================================" . PHP_EOL . PHP_EOL;

$client = new PDNSAdminClient($pdns_config);

function testCrudMethods($endpoint_name, $base_endpoint) {
    global $client;
    
    echo "🎯 Testing CRUD methods for: {$endpoint_name}" . PHP_EOL;
    echo "   Base endpoint: {$base_endpoint}" . PHP_EOL;
    echo "   ----------------------------------------" . PHP_EOL;
    
    // Test GET (list all)
    echo "   📖 GET {$base_endpoint}...";
    try {
        $result = $client->makeRequest($base_endpoint, 'GET');
        echo " HTTP {$result['status_code']}";
        if ($result['status_code'] === 200) {
            $count = count($result['data'] ?? []);
            echo " ✅ ({$count} items)";
        }
        echo PHP_EOL;
    } catch (Exception $e) {
        echo " ❌ Error: {$e->getMessage()}" . PHP_EOL;
    }
    
    // For zones, try to get a specific zone ID from the list
    $test_id = null;
    if ($base_endpoint === '/pdnsadmin/zones') {
        try {
            $list_result = $client->makeRequest($base_endpoint, 'GET');
            if ($list_result['status_code'] === 200 && !empty($list_result['data'])) {
                $test_id = $list_result['data'][0]['name'] ?? null;  // Use zone name as ID
            }
        } catch (Exception $e) {
            // Ignore errors for now
        }
    } elseif ($base_endpoint === '/pdnsadmin/users') {
        try {
            $list_result = $client->makeRequest($base_endpoint, 'GET');
            if ($list_result['status_code'] === 200 && !empty($list_result['data'])) {
                $test_id = $list_result['data'][0]['username'] ?? $list_result['data'][0]['id'] ?? null;
            }
        } catch (Exception $e) {
            // Ignore errors for now
        }
    } elseif ($base_endpoint === '/pdnsadmin/apikeys') {
        try {
            $list_result = $client->makeRequest($base_endpoint, 'GET');
            if ($list_result['status_code'] === 200 && !empty($list_result['data'])) {
                $test_id = $list_result['data'][0]['id'] ?? null;
            }
        } catch (Exception $e) {
            // Ignore errors for now
        }
    }
    
    // Test GET with ID (if we have one)
    if ($test_id) {
        echo "   📖 GET {$base_endpoint}/{$test_id}...";
        try {
            $result = $client->makeRequest("{$base_endpoint}/{$test_id}", 'GET');
            echo " HTTP {$result['status_code']}";
            if ($result['status_code'] === 200) {
                echo " ✅";
            } elseif ($result['status_code'] === 404) {
                echo " ❌ (Not Found)";
            } elseif ($result['status_code'] === 405) {
                echo " ⚠️  (Method Not Allowed)";
            }
            echo PHP_EOL;
        } catch (Exception $e) {
            echo " ❌ Error: {$e->getMessage()}" . PHP_EOL;
        }
    } else {
        echo "   📖 GET {$base_endpoint}/[ID]... ⚠️  (No test ID available)" . PHP_EOL;
    }
    
    // Test POST (create)
    echo "   ➕ POST {$base_endpoint}...";
    try {
        $test_data = [];
        if ($base_endpoint === '/pdnsadmin/zones') {
            $test_data = [
                'name' => 'test-endpoint-' . time() . '.example.com.',
                'kind' => 'Master',
                'dnssec' => false
            ];
        } elseif ($base_endpoint === '/pdnsadmin/users') {
            $test_data = [
                'username' => 'testuser' . time(),
                'password' => 'testpass123',
                'email' => 'test' . time() . '@example.com'
            ];
        } elseif ($base_endpoint === '/pdnsadmin/apikeys') {
            $test_data = [
                'description' => 'Test API Key ' . time(),
                'role' => 'Administrator'
            ];
        }
        
        $result = $client->makeRequest($base_endpoint, 'POST', $test_data);
        echo " HTTP {$result['status_code']}";
        
        if ($result['status_code'] === 201 || $result['status_code'] === 200) {
            echo " ✅ (Created)";
            // Store created resource for cleanup
            if (isset($result['data']['id'])) {
                $created_id = $result['data']['id'];
            } elseif (isset($result['data']['name'])) {
                $created_id = $result['data']['name'];
            } else {
                $created_id = null;
            }
            
            // Test DELETE if we have an ID
            if ($created_id) {
                echo PHP_EOL . "   🗑️  DELETE {$base_endpoint}/{$created_id}...";
                try {
                    $delete_result = $client->makeRequest("{$base_endpoint}/{$created_id}", 'DELETE');
                    echo " HTTP {$delete_result['status_code']}";
                    if ($delete_result['status_code'] === 204 || $delete_result['status_code'] === 200) {
                        echo " ✅ (Deleted)";
                    }
                } catch (Exception $e) {
                    echo " ❌ Error: {$e->getMessage()}";
                }
            }
        } elseif ($result['status_code'] === 405) {
            echo " ⚠️  (Method Not Allowed)";
        } elseif ($result['status_code'] === 422) {
            echo " ⚠️  (Validation Error)";
        } else {
            echo " ❌";
        }
        echo PHP_EOL;
    } catch (Exception $e) {
        echo " ❌ Error: {$e->getMessage()}" . PHP_EOL;
    }
    
    // Test PUT (update) - only if we have a test ID
    if ($test_id) {
        echo "   ✏️  PUT {$base_endpoint}/{$test_id}...";
        try {
            $update_data = ['description' => 'Updated via API test'];
            $result = $client->makeRequest("{$base_endpoint}/{$test_id}", 'PUT', $update_data);
            echo " HTTP {$result['status_code']}";
            if ($result['status_code'] === 200 || $result['status_code'] === 204) {
                echo " ✅";
            } elseif ($result['status_code'] === 405) {
                echo " ⚠️  (Method Not Allowed)";
            }
            echo PHP_EOL;
        } catch (Exception $e) {
            echo " ❌ Error: {$e->getMessage()}" . PHP_EOL;
        }
    } else {
        echo "   ✏️  PUT {$base_endpoint}/[ID]... ⚠️  (No test ID available)" . PHP_EOL;
    }
    
    // Test PATCH
    if ($test_id) {
        echo "   🔧 PATCH {$base_endpoint}/{$test_id}...";
        try {
            $patch_data = ['description' => 'Patched via API test'];
            $result = $client->makeRequest("{$base_endpoint}/{$test_id}", 'PATCH', $patch_data);
            echo " HTTP {$result['status_code']}";
            if ($result['status_code'] === 200 || $result['status_code'] === 204) {
                echo " ✅";
            } elseif ($result['status_code'] === 405) {
                echo " ⚠️  (Method Not Allowed)";
            }
            echo PHP_EOL;
        } catch (Exception $e) {
            echo " ❌ Error: {$e->getMessage()}" . PHP_EOL;
        }
    } else {
        echo "   🔧 PATCH {$base_endpoint}/[ID]... ⚠️  (No test ID available)" . PHP_EOL;
    }
    
    echo PHP_EOL;
}

// Test the three working endpoints we discovered
testCrudMethods("Zones/Domains", "/pdnsadmin/zones");
testCrudMethods("Users", "/pdnsadmin/users");  
testCrudMethods("API Keys", "/pdnsadmin/apikeys");

echo "=================================================" . PHP_EOL;
echo "🎯 ENDPOINT & METHOD ANALYSIS SUMMARY" . PHP_EOL;
echo "=================================================" . PHP_EOL;

echo "✅ CONFIRMED WORKING ENDPOINTS:" . PHP_EOL;
echo "   • GET /pdnsadmin/zones (621 zones)" . PHP_EOL;
echo "   • GET /pdnsadmin/users (50 users)" . PHP_EOL;  
echo "   • GET /pdnsadmin/apikeys (2 API keys)" . PHP_EOL;
echo PHP_EOL;

echo "❌ CONFIRMED NON-EXISTENT ENDPOINTS:" . PHP_EOL;
echo "   • /pdnsadmin/templates (404)" . PHP_EOL;
echo "   • /api/v1/* (all return 404)" . PHP_EOL;
echo "   • Standard PowerDNS server API endpoints" . PHP_EOL;
echo PHP_EOL;

echo "🔧 CURRENT CLIENT ENDPOINT STATUS:" . PHP_EOL;
echo "   ✅ Zones: Using correct /pdnsadmin/zones" . PHP_EOL;
echo "   ✅ Users: Using correct /pdnsadmin/users" . PHP_EOL;
echo "   ✅ API Keys: Using correct /pdnsadmin/apikeys" . PHP_EOL;
echo "   ❌ Templates: Using non-existent /pdnsadmin/templates" . PHP_EOL;
echo PHP_EOL;

echo "💡 RECOMMENDATIONS:" . PHP_EOL;
echo "   1. Our current endpoints for zones, users, and apikeys are CORRECT" . PHP_EOL;
echo "   2. Remove template endpoints from PDNSAdminClient (they don't exist)" . PHP_EOL;
echo "   3. Test individual CRUD methods to see what's supported" . PHP_EOL;
echo "   4. Update OpenAPI docs to reflect actual capabilities" . PHP_EOL;

echo PHP_EOL . "HTTP method testing completed!" . PHP_EOL;
?>
