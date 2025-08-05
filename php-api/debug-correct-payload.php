<?php
require_once 'config/config.php';
require_once 'classes/PDNSAdminClient.php';

echo "Testing PowerDNS Admin API with correct payload format...\n\n";

$client = new PDNSAdminClient($pdns_config);

// Test with the correct payload format
$test_data = [
    'username' => 'testuser' . time(),
    'plain_text_password' => bin2hex(random_bytes(8)),
    'firstname' => 'Test',
    'lastname' => 'User',
    'email' => 'test@example.com',
    'role' => [
        'id' => 2,
        'name' => 'User'
    ]
];

echo "Config:\n";
echo "- Base URL: " . $pdns_config['base_url'] . "\n";
echo "- Auth Type: " . $pdns_config['auth_type'] . "\n";
echo "- API Key: " . $pdns_config['api_key'] . "\n\n";

echo "Test Data (correct format):\n";
echo json_encode($test_data, JSON_PRETTY_PRINT) . "\n\n";

// Test the /pdnsadmin/users endpoint with correct payload
echo "=== Testing makeRequest('/pdnsadmin/users', 'POST') with correct format ===\n";
$response = $client->makeRequest('/pdnsadmin/users', 'POST', $test_data);
echo "Status: " . $response['status_code'] . "\n";
echo "Response: " . $response['raw_response'] . "\n\n";

// Show the equivalent curl command
echo "=== Equivalent curl command ===\n";
echo "curl -X POST 'https://dnsadmin.avant.nl/api/v1/pdnsadmin/users' \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -H 'Authorization: Basic YXBpYWRtaW46VmV2ZWxnSWNzXm9tMg==' \\\n";
echo "  -d '" . json_encode($test_data) . "' \\\n";
echo "  -k\n";
?>
