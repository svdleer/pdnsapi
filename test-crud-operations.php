<?php
/**
 * Test CRUD Operations for PowerDNS Admin API
 */

require_once 'php-api/config/config.php';
require_once 'php-api/classes/PDNSAdminClient.php';

echo "=== TESTING CRUD OPERATIONS ===" . PHP_EOL;
echo "Date: " . date('Y-m-d H:i:s') . PHP_EOL;
echo PHP_EOL;

$client = new PDNSAdminClient($pdns_config);

// Test 1: Analyze existing data structure
echo "1. ANALYZING EXISTING DATA STRUCTURES" . PHP_EOL;
echo "=====================================" . PHP_EOL;

// Get existing users
$users = $client->getAllUsers();
if (!empty($users['data'])) {
    $user = $users['data'][0];
    echo "Sample user keys: " . implode(', ', array_keys($user)) . PHP_EOL;
    echo "Sample user: " . $user['username'] . " (" . $user['email'] . ")" . PHP_EOL;
}

echo PHP_EOL;

// Get existing zones  
$zones = $client->makeRequest('/pdnsadmin/zones');
if (!empty($zones['data'])) {
    $zone = $zones['data'][0];
    echo "Sample zone keys: " . implode(', ', array_keys($zone)) . PHP_EOL;
    echo "Sample zone: " . $zone['name'] . " (ID: " . $zone['id'] . ")" . PHP_EOL;
}

echo PHP_EOL;

// Test 2: Create a test user
echo "2. TESTING USER CREATION" . PHP_EOL;
echo "========================" . PHP_EOL;

$test_username = "testuser_" . time();
$test_user_data = [
    'username' => $test_username,
    'plain_text_password' => 'test123456',
    'firstname' => 'Test',
    'lastname' => 'User',
    'email' => $test_username . '@example.com',
    'role_id' => 2 // User role
];

echo "Attempting to create user: " . $test_username . PHP_EOL;
$create_result = $client->createUser($test_user_data);
echo "Create user status: " . $create_result['status_code'] . PHP_EOL;

if ($create_result['status_code'] !== 200 && $create_result['status_code'] !== 201) {
    echo "Create failed. Response: " . substr($create_result['raw_response'], 0, 200) . PHP_EOL;
} else {
    echo "User created successfully!" . PHP_EOL;
}

echo PHP_EOL;

// Test 3: Test getting specific user
echo "3. TESTING GET SPECIFIC USER" . PHP_EOL;
echo "============================" . PHP_EOL;

if (isset($users['data'][0])) {
    $existing_user = $users['data'][0];
    $username = $existing_user['username'];
    
    echo "Testing get user by username: " . $username . PHP_EOL;
    $get_result = $client->makeRequest('/pdnsadmin/users/' . $username);
    echo "Get user status: " . $get_result['status_code'] . PHP_EOL;
    
    if ($get_result['status_code'] == 200 && isset($get_result['data'])) {
        echo "✅ Retrieved user: " . ($get_result['data']['username'] ?? 'unknown') . PHP_EOL;
    } elseif ($get_result['status_code'] == 404) {
        echo "❌ Individual user retrieval not supported" . PHP_EOL;
        echo "Note: PowerDNS Admin may only support bulk user listing" . PHP_EOL;
    }
} else {
    echo "No users available for testing" . PHP_EOL;
}

echo PHP_EOL;

// Test 4: Test zone operations
echo "4. TESTING ZONE OPERATIONS" . PHP_EOL;
echo "==========================" . PHP_EOL;

if (!empty($zones['data'])) {
    $test_zone = $zones['data'][0];
    $zone_name = $test_zone['name'];
    
    echo "Testing get zone by name: " . $zone_name . PHP_EOL;
    $zone_result = $client->makeRequest('/pdnsadmin/zones/' . urlencode($zone_name));
    echo "Get zone status: " . $zone_result['status_code'] . PHP_EOL;
    
    if ($zone_result['status_code'] == 200) {
        echo "✅ Retrieved zone: " . ($zone_result['data']['name'] ?? 'unknown') . PHP_EOL;
    } elseif ($zone_result['status_code'] == 405) {
        echo "❌ Individual zone retrieval not supported (Method not allowed)" . PHP_EOL;
        echo "Note: PowerDNS Admin may only support bulk zone listing" . PHP_EOL;
    } else {
        echo "❌ Zone retrieval failed (Status: " . $zone_result['status_code'] . ")" . PHP_EOL;
    }
} else {
    echo "No zones available for testing" . PHP_EOL;
}

echo PHP_EOL;

// Test 5: API Keys operations
echo "5. TESTING API KEYS" . PHP_EOL;
echo "===================" . PHP_EOL;

$apikeys = $client->getAllApiKeys();
echo "API Keys count: " . count($apikeys['data'] ?? []) . PHP_EOL;

if (!empty($apikeys['data'])) {
    $apikey = $apikeys['data'][0];
    echo "Sample API key keys: " . implode(', ', array_keys($apikey)) . PHP_EOL;
}

echo PHP_EOL;
echo "=== CRUD TESTING COMPLETE ===" . PHP_EOL;
?>
