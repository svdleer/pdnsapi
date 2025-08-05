<?php
require_once 'config/config.php';
require_once 'classes/PDNSAdminClient.php';

echo "Testing PowerDNS Admin API authentication...\n\n";

$client = new PDNSAdminClient($pdns_config);

// Test the exact call that createAccount makes
$test_data = [
    'username' => 'testuser' . time(),
    'firstname' => 'Test',
    'lastname' => 'User',
    'email' => 'test@example.com',
    'password' => bin2hex(random_bytes(8)), 
    'role' => 'User'
];

echo "Config:\n";
echo "- Base URL: " . $pdns_config['base_url'] . "\n";
echo "- Auth Type: " . $pdns_config['auth_type'] . "\n";
echo "- API Key: " . $pdns_config['api_key'] . "\n";
echo "- Server Key: " . $pdns_config['pdns_server_key'] . "\n\n";

echo "Test Data:\n";
echo json_encode($test_data, JSON_PRETTY_PRINT) . "\n\n";

// Test the exact endpoint that's failing
echo "=== Testing makeRequest('/users', 'POST') ===\n";
$response = $client->makeRequest('/users', 'POST', $test_data);
echo "Status: " . $response['status_code'] . "\n";
echo "Response: " . $response['raw_response'] . "\n\n";

// Test if /pdnsadmin/users works
echo "=== Testing makeRequest('/pdnsadmin/users', 'POST') ===\n";
$response2 = $client->makeRequest('/pdnsadmin/users', 'POST', $test_data);
echo "Status: " . $response2['status_code'] . "\n";
echo "Response: " . $response2['raw_response'] . "\n\n";

// Test the createUser method
echo "=== Testing createUser() method ===\n";
$response3 = $client->createUser($test_data);
echo "Status: " . $response3['status_code'] . "\n";
echo "Response: " . $response3['raw_response'] . "\n\n";

// Show what curl command would be equivalent
echo "=== Equivalent curl command ===\n";
echo "curl -X POST 'https://dnsadmin.avant.nl/api/v1/users' \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -H 'Authorization: Basic Hw8nc1GbL8CYddo' \\\n";
echo "  -d '" . json_encode($test_data) . "' \\\n";
echo "  -k\n";
?>
