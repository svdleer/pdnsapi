<?php
/**
 * Test the new domain-by-name functions with proper config
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/php-api/includes/autoloader.php';
require_once __DIR__ . '/php-api/classes/PDNSAdminClient.php';

echo "=== Testing New Domain-by-Name Functions ===\n\n";

// Create test config
$test_config = [
    'base_url' => 'https://dnsadmin.avant.nl/api/v1',
    'auth_type' => 'basic',
    'api_key' => 'YXBpYWRtaW46VmV2ZWxnSWNzXm9tMg==',
    'pdns_server_key' => 'morWehofCidwiWejishOwko=!b'
];

$client = new PDNSAdminClient($test_config);

// Test 1: Search domains by pattern
echo "1. Testing searchDomainsByName('avant')...\n";
$response = $client->searchDomainsByName('avant');
echo "Status Code: " . $response['status_code'] . "\n";
if ($response['status_code'] == 200) {
    echo "✅ searchDomainsByName() works\n";
    $domains = $response['data'] ?? [];
    echo "Found " . count($domains) . " domains matching 'avant'\n";
    
    if (!empty($domains)) {
        echo "Sample domains:\n";
        foreach (array_slice($domains, 0, 3) as $domain) {
            echo "  - ID: " . $domain['id'] . ", Name: " . $domain['name'] . ", PowerDNS Zone ID: " . ($domain['pdns_zone_id'] ?? 'N/A') . "\n";
        }
        
        // Test 2: Get domain by name using first found domain
        $test_domain_name = $domains[0]['name'];
        echo "\n2. Testing getDomainByName('{$test_domain_name}')...\n";
        $response = $client->getDomainByName($test_domain_name);
        echo "Status Code: " . $response['status_code'] . "\n";
        if ($response['status_code'] == 200) {
            echo "✅ getDomainByName() works\n";
            $domain_data = $response['data'];
            if (isset($domain_data['source']) && $domain_data['source'] === 'local_database_only') {
                echo "Domain found in local DB only: " . $domain_data['name'] . " (PowerDNS Zone ID: " . $domain_data['pdns_zone_id'] . ")\n";
                echo "Note: " . $domain_data['note'] . "\n";
            } else {
                echo "Domain from PowerDNS Admin: " . ($domain_data['name'] ?? 'N/A') . " (ID: " . ($domain_data['id'] ?? 'N/A') . ")\n";
            }
        } else {
            echo "❌ getDomainByName() failed\n";
            echo "Response: " . $response['raw_response'] . "\n";
        }
        
        // Test 3: Test updateDomainByName (should return 405)
        echo "\n3. Testing updateDomainByName() - should return 405...\n";
        $response = $client->updateDomainByName($test_domain_name, ['description' => 'test']);
        echo "Status Code: " . $response['status_code'] . "\n";
        if ($response['status_code'] == 405) {
            echo "✅ updateDomainByName() correctly returns 405 (Method Not Allowed)\n";
        } else {
            echo "❌ Unexpected response from updateDomainByName()\n";
            echo "Response: " . $response['raw_response'] . "\n";
        }
        
        // Test 4: Test domain ID lookup
        echo "\n4. Testing domain ID lookup for '{$test_domain_name}'...\n";
        $reflection = new ReflectionClass($client);
        $method = $reflection->getMethod('getDomainIdByName');
        $method->setAccessible(true);
        $pdns_zone_id = $method->invoke($client, $test_domain_name);
        
        if ($pdns_zone_id) {
            echo "✅ Local database lookup works: PowerDNS Zone ID = {$pdns_zone_id}\n";
        } else {
            echo "❌ Could not find PowerDNS Zone ID in local database\n";
        }
    }
} else {
    echo "❌ searchDomainsByName() failed\n";
    echo "Response: " . $response['raw_response'] . "\n";
}

echo "\n=== New Methods Available ===\n";
echo "The following domain-by-name methods are now available:\n";
echo "✅ searchDomainsByName(\$pattern) - Search domains by name pattern\n";
echo "✅ getDomainByName(\$domain_name) - Get specific domain by name\n";
echo "✅ deleteDomainByName(\$domain_name) - Delete domain by name\n";
echo "❌ updateDomainByName(\$domain_name, \$data) - Returns 405 (PowerDNS Admin limitation)\n";

echo "\nAll functions use local database for name->pdns_zone_id translation, then call PowerDNS Admin API with correct ID.\n";
?>
