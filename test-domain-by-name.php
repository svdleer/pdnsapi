<?php
/**
 * Test the new domain-by-name functions
 */

require_once __DIR__ . '/php-api/includes/autoloader.php';
require_once __DIR__ . '/php-api/config/config.php';
require_once __DIR__ . '/php-api/classes/PDNSAdminClient.php';

echo "=== Testing New Domain-by-Name Functions ===\n\n";

$client = new PDNSAdminClient($pdns_config);

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
            echo "  - ID: " . $domain['id'] . ", Name: " . $domain['name'] . "\n";
        }
        
        // Test 2: Get domain by name using first found domain
        $test_domain_name = $domains[0]['name'];
        echo "\n2. Testing getDomainByName('{$test_domain_name}')...\n";
        $response = $client->getDomainByName($test_domain_name);
        echo "Status Code: " . $response['status_code'] . "\n";
        if ($response['status_code'] == 200) {
            echo "✅ getDomainByName() works\n";
            $domain_data = $response['data'];
            echo "Domain: " . ($domain_data['name'] ?? 'N/A') . " (ID: " . ($domain_data['id'] ?? 'N/A') . ")\n";
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

echo "\nAll functions use local database for name->ID translation, then call PowerDNS Admin API with ID.\n";
?>
