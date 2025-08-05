<?php
require_once 'config/config.php';
require_once 'classes/PDNSAdminClient.php';

echo "Testing PowerDNS Admin API permissions...\n";
echo "API Key: " . $pdns_config['api_key'] . "\n";
echo "Base URL: " . $pdns_config['base_url'] . "\n\n";

$client = new PDNSAdminClient($pdns_config);

// Test various endpoints to see what we have access to
$endpoints_to_test = [
    '/servers/1/zones' => 'GET',
    '/servers/localhost/zones' => 'GET', 
    '/servers/1/config' => 'GET',
    '/pdnsadmin/zones' => 'GET',
    '/pdnsadmin/accounts' => 'GET',
    '/pdnsadmin/users' => 'GET',
    '/pdnsadmin/apikeys' => 'GET',
    '/' => 'GET',
    '/api' => 'GET',
    '/status' => 'GET'
];

foreach ($endpoints_to_test as $endpoint => $method) {
    echo "=== Testing $method $endpoint ===\n";
    $response = $client->makeRequest($endpoint, $method);
    echo "Status: " . $response['status_code'] . "\n";
    
    if ($response['status_code'] == 200) {
        echo "SUCCESS! Response: " . substr($response['raw_response'], 0, 200) . "...\n";
    } elseif ($response['status_code'] == 401) {
        echo "UNAUTHORIZED\n";
    } elseif ($response['status_code'] == 404) {
        echo "NOT FOUND\n";
    } else {
        echo "Other error: " . substr($response['raw_response'], 0, 100) . "...\n";
    }
    echo "---\n\n";
}
?>
