<?php
// Test the corrected PowerDNS Admin zone endpoint
$base_path = __DIR__;
require_once $base_path . '/config/config.php';
require_once $base_path . '/classes/PDNSAdminClient.php';

echo "Testing corrected PowerDNS Admin zone endpoint...\n";
echo "Base URL: https://dnsadmin.avant.nl/api/v1\n\n";

// Initialize PDNSAdmin client
$pdns_client = new PDNSAdminClient($pdns_config);

// Test the corrected endpoint: /servers/1/zones
echo "=== Testing /servers/1/zones endpoint ===\n";
$zone_response = $pdns_client->makeRequest('/servers/1/zones', 'GET');
echo "Status Code: " . $zone_response['status_code'] . "\n";

if ($zone_response['status_code'] == 200) {
    $zones = $zone_response['data'];
    echo "Success! Found " . count($zones) . " zones\n";
    if (!empty($zones)) {
        $first_zone = $zones[0];
        echo "First zone structure:\n";
        print_r($first_zone);
        
        // Check if there's account information available
        if (isset($first_zone['account'])) {
            echo "✓ Account field found: " . $first_zone['account'] . "\n";
        } else {
            echo "✗ No account field in zone data\n";
        }
        
        // Check what other fields are available
        echo "Available fields: " . implode(', ', array_keys($first_zone)) . "\n";
    }
} else {
    echo "Error: " . json_encode($zone_response['data']) . "\n";
    echo "Raw response: " . $zone_response['raw_response'] . "\n";
}

// Test updated getAllDomains method
echo "\n=== Testing updated getAllDomains method ===\n";
$domains_response = $pdns_client->getAllDomains();
echo "Status Code: " . $domains_response['status_code'] . "\n";

if ($domains_response['status_code'] == 200) {
    $domains = $domains_response['data'];
    echo "getAllDomains found " . count($domains) . " domains\n";
    if (!empty($domains)) {
        echo "First domain from getAllDomains:\n";
        print_r($domains[0]);
    }
} else {
    echo "getAllDomains error: " . json_encode($domains_response['data']) . "\n";
}

echo "\nTest completed!\n";
?>
