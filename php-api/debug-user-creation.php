<?php
require_once 'config/config.php';
require_once 'classes/PDNSAdminClient.php';

// Test data
$test_data = [
    'username' => 'testuser' . time(), // Add timestamp to make unique
    'firstname' => 'Test',
    'lastname' => 'User',
    'email' => 'test@example.com',
    'password' => bin2hex(random_bytes(8)), 
    'role' => 'User'
];

echo "Testing PowerDNS Admin API for user creation...\n";
echo "URL: " . $pdns_config['base_url'] . "/users\n";
echo "API Key: " . $pdns_config['api_key'] . "\n";
echo "Data: " . json_encode($test_data, JSON_PRETTY_PRINT) . "\n\n";

$client = new PDNSAdminClient($pdns_config);

// Try different endpoints
$endpoints_to_test = [
    '/users',
    '/pdnsadmin/users', 
    '/api/v1/users',
    '/user'
];

foreach ($endpoints_to_test as $endpoint) {
    echo "=== Testing endpoint: $endpoint ===\n";
    
    $response = $client->makeRequest($endpoint, 'POST', $test_data);
    
    echo "Status Code: " . $response['status_code'] . "\n";
    echo "Raw Response: " . $response['raw_response'] . "\n";
    echo "Parsed Data: " . json_encode($response['data'], JSON_PRETTY_PRINT) . "\n";
    echo "---\n\n";
}

// Also test GET to see what endpoints are available
echo "=== Testing GET /users (to see existing users) ===\n";
$get_response = $client->makeRequest('/users', 'GET');
echo "Status Code: " . $get_response['status_code'] . "\n";
echo "Raw Response: " . substr($get_response['raw_response'], 0, 500) . "...\n\n";

// Test GET /pdnsadmin/users
echo "=== Testing GET /pdnsadmin/users ===\n";  
$get_response2 = $client->makeRequest('/pdnsadmin/users', 'GET');
echo "Status Code: " . $get_response2['status_code'] . "\n";
echo "Raw Response: " . substr($get_response2['raw_response'], 0, 500) . "...\n\n";
?>
