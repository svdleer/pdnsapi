<?php
require_once 'classes/PDNSAdminClient.php';
require_once 'config/config.php';

echo "=== PowerDNS Admin Endpoints Test ===\n\n";

$client = new PDNSAdminClient($pdns_config);

// Test PowerDNS Admin specific endpoints with Basic Auth
$endpoints = [
    'Users' => '/pdnsadmin/users',
    'Accounts' => '/pdnsadmin/accounts', 
    'Zones' => '/pdnsadmin/zones'
];

foreach ($endpoints as $name => $endpoint) {
    echo "Testing $name ($endpoint):\n";
    
    $response = $client->makeRequest($endpoint, 'GET');
    $status_code = $response['status_code'];
    $data = $response['data'];
    $raw_response = $response['raw_response'];
    
    echo "  📊 HTTP $status_code - " . strlen($raw_response) . " bytes\n";
    
    if ($status_code == 200) {
        echo "  ✅ SUCCESS\n";
        if (is_array($data) && count($data) > 0) {
            echo "  📋 Contains " . count($data) . " items\n";
            
            // Show structure of first item
            if (isset($data[0]) && is_array($data[0])) {
                echo "  🔍 First item keys: " . implode(', ', array_keys($data[0])) . "\n";
                
                // Show first few items
                foreach (array_slice($data, 0, 5) as $i => $item) {
                    if (isset($item['name'])) {
                        echo "    " . ($i + 1) . ". " . $item['name'] . "\n";
                    } elseif (isset($item['username'])) {
                        echo "    " . ($i + 1) . ". " . $item['username'] . "\n";
                    } elseif (isset($item['id'])) {
                        echo "    " . ($i + 1) . ". ID: " . $item['id'] . "\n";
                    }
                }
            }
        } elseif (is_array($data)) {
            echo "  📄 Response keys: " . implode(', ', array_keys($data)) . "\n";
        }
    } elseif ($status_code == 401) {
        echo "  🔐 UNAUTHORIZED - Check API key format\n";
        echo "  📝 Response: " . substr($raw_response, 0, 200) . "...\n";
    } else {
        echo "  ❌ FAILED\n";
        echo "  📝 Response: " . substr($raw_response, 0, 200) . "...\n";
    }
    
    echo "\n";
}
?>
