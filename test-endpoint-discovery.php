<?php
require_once __DIR__ . '/php-api/config/config.php';
require_once __DIR__ . '/php-api/classes/PDNSAdminClient.php';

echo "=== POWERDNS ADMIN API ENDPOINT DISCOVERY ===" . PHP_EOL;
echo "Testing actual available endpoints vs our assumptions" . PHP_EOL;
echo "Date: " . date('Y-m-d H:i:s') . PHP_EOL;
echo "=============================================" . PHP_EOL . PHP_EOL;

$client = new PDNSAdminClient($pdns_config);

function testEndpoint($description, $endpoint, $method = 'GET', $expected_codes = [200, 201, 204]) {
    global $client;
    
    echo "🔍 Testing: {$description}" . PHP_EOL;
    echo "   Endpoint: {$method} {$endpoint}" . PHP_EOL;
    
    try {
        $result = $client->makeRequest($endpoint, $method);
        $status = $result['status_code'];
        
        if (in_array($status, $expected_codes)) {
            echo "   ✅ WORKS: HTTP {$status}" . PHP_EOL;
            if (isset($result['data']) && is_array($result['data'])) {
                $count = count($result['data']);
                echo "   📊 Response: {$count} items" . PHP_EOL;
            }
        } elseif ($status === 404) {
            echo "   ❌ NOT FOUND: HTTP {$status} - Endpoint doesn't exist" . PHP_EOL;
        } elseif ($status === 405) {
            echo "   ⚠️  METHOD NOT ALLOWED: HTTP {$status} - Endpoint exists but method not supported" . PHP_EOL;
        } elseif ($status === 401 || $status === 403) {
            echo "   🔒 AUTH ERROR: HTTP {$status} - Authentication/authorization issue" . PHP_EOL;
        } else {
            echo "   ❓ UNEXPECTED: HTTP {$status}" . PHP_EOL;
        }
        
        // Show a sample of the raw response for debugging
        if (strlen($result['raw_response']) < 500) {
            echo "   🔍 Raw: " . trim($result['raw_response']) . PHP_EOL;
        }
        
    } catch (Exception $e) {
        echo "   💥 ERROR: {$e->getMessage()}" . PHP_EOL;
    }
    
    echo PHP_EOL;
}

echo "📍 TESTING DOMAIN/ZONE ENDPOINTS:" . PHP_EOL;
echo "=================================" . PHP_EOL;

// Test zone endpoints
testEndpoint("Get all zones (our current endpoint)", "/pdnsadmin/zones", "GET");
testEndpoint("Get all zones (alternative 1)", "/api/v1/servers/localhost/zones", "GET");
testEndpoint("Get all zones (alternative 2)", "/zones", "GET");
testEndpoint("Get all zones (alternative 3)", "/api/zones", "GET");
testEndpoint("Get all zones (web API)", "/api/v1/zones", "GET");

echo "📍 TESTING USER/ACCOUNT ENDPOINTS:" . PHP_EOL;
echo "==================================" . PHP_EOL;

// Test user endpoints  
testEndpoint("Get all users (our current endpoint)", "/pdnsadmin/users", "GET");
testEndpoint("Get all users (alternative 1)", "/api/v1/users", "GET");
testEndpoint("Get all users (alternative 2)", "/users", "GET");
testEndpoint("Get all users (alternative 3)", "/api/users", "GET");

echo "📍 TESTING API KEY ENDPOINTS:" . PHP_EOL;
echo "=============================" . PHP_EOL;

// Test API key endpoints
testEndpoint("Get all API keys (our current endpoint)", "/pdnsadmin/apikeys", "GET");
testEndpoint("Get all API keys (alternative 1)", "/api/v1/apikeys", "GET");
testEndpoint("Get all API keys (alternative 2)", "/apikeys", "GET");
testEndpoint("Get all API keys (alternative 3)", "/api/apikeys", "GET");

echo "📍 TESTING TEMPLATE ENDPOINTS:" . PHP_EOL;
echo "==============================" . PHP_EOL;

// Test template endpoints
testEndpoint("Get all templates (our current endpoint)", "/pdnsadmin/templates", "GET");
testEndpoint("Get all templates (alternative 1)", "/api/v1/templates", "GET");
testEndpoint("Get all templates (alternative 2)", "/templates", "GET");
testEndpoint("Get all templates (alternative 3)", "/api/templates", "GET");

echo "📍 TESTING ROOT API DISCOVERY:" . PHP_EOL;
echo "==============================" . PHP_EOL;

// Test root endpoints to see what's available
testEndpoint("API root", "/", "GET");
testEndpoint("API v1 root", "/api/v1", "GET");
testEndpoint("API root", "/api", "GET");
testEndpoint("PDNS Admin root", "/pdnsadmin", "GET");

echo "📍 TESTING SERVER API ENDPOINTS:" . PHP_EOL;
echo "================================" . PHP_EOL;

// Test PowerDNS server API endpoints
testEndpoint("Server localhost info", "/api/v1/servers/localhost", "GET");
testEndpoint("Server localhost zones", "/api/v1/servers/localhost/zones", "GET");
testEndpoint("Server localhost config", "/api/v1/servers/localhost/config", "GET");

echo "=============================================" . PHP_EOL;
echo "🔍 ENDPOINT DISCOVERY COMPLETED" . PHP_EOL;
echo "=============================================" . PHP_EOL;

echo "💡 NEXT STEPS:" . PHP_EOL;
echo "1. Identify which endpoints actually work" . PHP_EOL;
echo "2. Update PDNSAdminClient.php with correct endpoints" . PHP_EOL;
echo "3. Test CRUD operations with working endpoints" . PHP_EOL;
echo "4. Update OpenAPI documentation to match reality" . PHP_EOL;

echo PHP_EOL . "Endpoint discovery completed!" . PHP_EOL;
?>
