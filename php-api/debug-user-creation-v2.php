<?php
require_once 'config/config.php';
require_once 'classes/PDNSAdminClient.php';

// Test using the proper createUser method
$test_data = [
    'username' => 'testuser' . time(),
    'firstname' => 'Test',
    'lastname' => 'User',
    'email' => 'test@example.com',
    'password' => bin2hex(random_bytes(8)), 
    'role' => 'User'
];

echo "Testing PowerDNS Admin API with createUser method...\n";
echo "Data: " . json_encode($test_data, JSON_PRETTY_PRINT) . "\n\n";

$client = new PDNSAdminClient($pdns_config);

// Test the createUser method which uses /pdnsadmin/users
echo "=== Testing createUser method ===\n";
$response = $client->createUser($test_data);
echo "Status Code: " . $response['status_code'] . "\n";
echo "Raw Response: " . $response['raw_response'] . "\n";
echo "Parsed Data: " . json_encode($response['data'], JSON_PRETTY_PRINT) . "\n\n";

// Also test with different auth approaches
echo "=== Testing with Basic Auth (if configured) ===\n";
$basic_config = $pdns_config;
$basic_config['auth_type'] = 'basic';
// You'd need to set username/password if available
$basic_client = new PDNSAdminClient($basic_config);
$basic_response = $basic_client->createUser($test_data);
echo "Status Code: " . $basic_response['status_code'] . "\n";
echo "Raw Response: " . substr($basic_response['raw_response'], 0, 200) . "...\n\n";

// Test GET first to see if we can read users
echo "=== Testing GET /pdnsadmin/users ===\n";
$get_response = $client->getAllUsers();
echo "Status Code: " . $get_response['status_code'] . "\n";
echo "Raw Response: " . substr($get_response['raw_response'], 0, 300) . "...\n";
?>
