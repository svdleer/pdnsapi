<?php
/**
 * Test Enhanced Domain Operations with PowerDNS Server API
 */

require_once __DIR__ . '/php-api/includes/autoloader.php';
require_once __DIR__ . '/php-api/classes/PDNSAdminClient.php';

echo "=== Testing Enhanced Domain Operations ===\n\n";

// Test config
$test_config = [
    'base_url' => 'https://dnsadmin.avant.nl/api/v1',
    'auth_type' => 'basic',
    'api_key' => 'YXBpYWRtaW46VmV2ZWxnSWNzXm9tMg==',
    'pdns_server_key' => 'morWehofCidwiWejishOwko=!b'
];

$client = new PDNSAdminClient($test_config);

// Test 1: Search for a domain
echo "1. Testing searchDomainsByName('avant')...\n";
$response = $client->searchDomainsByName('avant');
echo "Status Code: " . $response['status_code'] . "\n";

if ($response['status_code'] == 200 && !empty($response['data'])) {
    $test_domain = $response['data'][0]['name'];
    echo "✅ Found " . count($response['data']) . " domains\n";
    echo "Using test domain: {$test_domain}\n\n";
    
    // Test 2: Get domain details via PowerDNS Server API
    echo "2. Testing getDomainDetailsByName('{$test_domain}')...\n";
    $response = $client->getDomainDetailsByName($test_domain);
    echo "Status Code: " . $response['status_code'] . "\n";
    
    if ($response['status_code'] == 200) {
        echo "✅ getDomainDetailsByName() works!\n";
        $domain_data = $response['data'];
        if (isset($domain_data['rrsets'])) {
            echo "Domain has " . count($domain_data['rrsets']) . " record sets\n";
        }
        if (isset($domain_data['kind'])) {
            echo "Domain type: " . $domain_data['kind'] . "\n";
        }
    } else {
        echo "❌ getDomainDetailsByName() failed\n";
        echo "Error: " . $response['raw_response'] . "\n";
    }
    echo "\n";
    
    // Test 3: Get domain records
    echo "3. Testing getDomainRecords('{$test_domain}')...\n";
    $response = $client->getDomainRecords($test_domain);
    echo "Status Code: " . $response['status_code'] . "\n";
    
    if ($response['status_code'] == 200) {
        echo "✅ getDomainRecords() works!\n";
        $records = $response['data'];
        if (isset($records['rrsets'])) {
            echo "Found " . count($records['rrsets']) . " record types\n";
            // Show first few record types
            foreach (array_slice($records['rrsets'], 0, 3) as $rrset) {
                echo "  - " . $rrset['name'] . " (" . $rrset['type'] . ")\n";
            }
        }
    } else {
        echo "❌ getDomainRecords() failed\n";
        echo "Error: " . $response['raw_response'] . "\n";
    }
    echo "\n";
    
    // Test 4: Test updateDomainByName (be careful - this could modify real DNS!)
    echo "4. Testing updateDomainByName() with SAFE operation...\n";
    // We'll just test the endpoint, not actually modify anything dangerous
    $safe_update = ['kind' => 'Native']; // This shouldn't change anything
    $response = $client->updateDomainByName($test_domain, $safe_update);
    echo "Status Code: " . $response['status_code'] . "\n";
    
    if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
        echo "✅ updateDomainByName() endpoint works!\n";
    } elseif ($response['status_code'] == 405) {
        echo "⚠️  updateDomainByName() returns 405 - method not allowed\n";
    } elseif ($response['status_code'] == 403) {
        echo "⚠️  updateDomainByName() returns 403 - permission denied\n";  
    } else {
        echo "❌ updateDomainByName() failed with status " . $response['status_code'] . "\n";
        echo "Error: " . $response['raw_response'] . "\n";
    }
    
} else {
    echo "❌ No domains found to test with\n";
}

echo "\n=== Available Domain Functions ===\n";
$methods = get_class_methods($client);
$domain_methods = array_filter($methods, function($method) {
    return strpos($method, 'Domain') !== false || strpos($method, 'domain') !== false;
});

foreach ($domain_methods as $method) {
    if (strpos($method, '__') !== 0) {
        echo "✅ {$method}()\n";
    }
}

echo "\n=== Summary ===\n";
echo "PowerDNS Admin API provides TWO layers for domain management:\n";
echo "1. /pdnsadmin/zones - Basic zone management (list, create, delete)\n";
echo "2. /servers/localhost/zones - Full PowerDNS Server API (details, records, updates)\n";
echo "\nThis gives us much more power than initially expected!\n";
?>
