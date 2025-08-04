<?php
echo "=== DEBUGGING POWERDNS ADMIN CONNECTION ===\n\n";

require_once 'config/config.php';
require_once 'classes/PDNSAdminClient.php';

// Test the actual configuration from config.php
$configs = [
    'dnsadmin.avant.nl' => $pdns_config
];

foreach ($configs as $name => $config) {
    echo "Testing configuration: $name\n";
    echo "URL: {$config['base_url']}\n";
    
    $client = new PDNSAdminClient($config);
    
    // Test basic connectivity
    $response = $client->makeRequest('/pdnsadmin/zones', 'GET');
    echo "Status: {$response['status_code']}\n";
    
    if ($response['status_code'] == 200) {
        echo "✅ Connection successful!\n";
        $zones = $response['data'];
        echo "Retrieved " . count($zones) . " zones\n";
        
        // Show sample zones
        if (count($zones) > 0) {
            echo "Sample zones:\n";
            foreach (array_slice($zones, 0, 3) as $zone) {
                echo "   - {$zone['name']} (account: " . ($zone['account'] ?? 'none') . ")\n";
            }
        }
        break; // Success, no need to test other configs
    } else {
        echo "❌ Connection failed\n";
        if (isset($response['data']['message'])) {
            echo "Error: {$response['data']['message']}\n";
        }
    }
    echo "\n";
}

echo "=== CONNECTION TEST COMPLETE ===\n";
?>
