<?php
require_once 'config/config.php';
require_once 'classes/PDNSAdminClient.php';

echo "=== PowerDNS Admin Raw API Debug ===\n\n";

// Initialize client
$pdns_client = new PDNSAdminClient($pdns_config);

echo "Config:\n";
echo "Base URL: " . $pdns_config['base_url'] . "\n";
echo "Auth Type: " . $pdns_config['auth_type'] . "\n";
echo "API Key (first 10 chars): " . substr($pdns_config['api_key'], 0, 10) . "...\n\n";

// Test different endpoints
$endpoints = [
    '/servers/1/zones',
    '/servers/localhost/zones', 
    '/zones',
    '/accounts'
];

foreach ($endpoints as $endpoint) {
    echo "Testing endpoint: $endpoint\n";
    echo "Full URL: " . $pdns_config['base_url'] . $endpoint . "\n";
    
    $response = $pdns_client->makeRequest($endpoint);
    
    echo "Status Code: " . $response['status_code'] . "\n";
    
    if ($response['status_code'] == 200) {
        $data = json_decode($response['data'], true);
        if (is_array($data)) {
            echo "Success! Found " . count($data) . " items\n";
            if (count($data) > 0) {
                echo "First item structure:\n";
                print_r(array_keys($data[0]));
                echo "First item sample:\n";
                echo json_encode($data[0], JSON_PRETTY_PRINT) . "\n";
            }
        } else {
            echo "Response: " . substr($response['data'], 0, 200) . "...\n";
        }
    } else {
        echo "Error: " . $response['data'] . "\n";
    }
    
    echo "\n" . str_repeat('-', 50) . "\n\n";
}
?>
