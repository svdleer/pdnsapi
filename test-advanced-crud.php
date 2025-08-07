<?php
/**
 * Advanced CRUD Testing for PowerDNS Admin API
 */

require_once 'php-api/config/config.php';
require_once 'php-api/classes/PDNSAdminClient.php';

echo "=== ADVANCED CRUD TESTING ===" . PHP_EOL;
echo "Date: " . date('Y-m-d H:i:s') . PHP_EOL;
echo PHP_EOL;

$client = new PDNSAdminClient($pdns_config);

// Test 1: Create multiple test users
echo "1. CREATING MULTIPLE TEST USERS" . PHP_EOL;
echo "===============================" . PHP_EOL;

$test_users = [
    [
        'username' => 'testuser_' . time() . '_1',
        'plain_text_password' => 'password123',
        'firstname' => 'Test',
        'lastname' => 'User One',
        'email' => 'testuser1_' . time() . '@example.com',
        'role_id' => 2
    ],
    [
        'username' => 'testuser_' . time() . '_2', 
        'plain_text_password' => 'password123',
        'firstname' => 'Test',
        'lastname' => 'User Two',
        'email' => 'testuser2_' . time() . '@example.com',
        'role_id' => 2
    ]
];

$created_users = [];

foreach ($test_users as $index => $user_data) {
    echo "Creating user: " . $user_data['username'] . PHP_EOL;
    $result = $client->createUser($user_data);
    
    echo "Status: " . $result['status_code'];
    
    if ($result['status_code'] == 201 || $result['status_code'] == 200) {
        echo " ✅ SUCCESS" . PHP_EOL;
        $created_users[] = $user_data['username'];
    } else {
        echo " ❌ FAILED" . PHP_EOL;
        if (isset($result['raw_response'])) {
            echo "Error: " . substr($result['raw_response'], 0, 100) . PHP_EOL;
        }
    }
}

echo "Created " . count($created_users) . " users successfully" . PHP_EOL;
echo PHP_EOL;

// Test 2: List all users and find our test users
echo "2. FINDING CREATED TEST USERS" . PHP_EOL;
echo "=============================" . PHP_EOL;

$all_users = $client->getAllUsers();
echo "Total users in system: " . count($all_users['data'] ?? []) . PHP_EOL;

$found_test_users = [];
if (!empty($all_users['data'])) {
    foreach ($all_users['data'] as $user) {
        if (in_array($user['username'], $created_users)) {
            $found_test_users[] = $user;
            echo "Found test user: " . $user['username'] . " (ID: " . $user['id'] . ")" . PHP_EOL;
        }
    }
}

echo "Found " . count($found_test_users) . " test users" . PHP_EOL;
echo PHP_EOL;

// Test 3: Update a test user
echo "3. UPDATING TEST USER" . PHP_EOL;
echo "=====================" . PHP_EOL;

if (!empty($found_test_users)) {
    $user_to_update = $found_test_users[0];
    $update_data = [
        'firstname' => 'Updated',
        'lastname' => 'Test User'
    ];
    
    echo "Updating user ID: " . $user_to_update['id'] . PHP_EOL;
    $update_result = $client->updateAccount($user_to_update['id'], $update_data);
    
    echo "Update status: " . $update_result['status_code'];
    
    if ($update_result['status_code'] == 200) {
        echo " ✅ SUCCESS" . PHP_EOL;
    } else {
        echo " ❌ FAILED" . PHP_EOL;
        echo "Response: " . substr($update_result['raw_response'], 0, 200) . PHP_EOL;
    }
}

echo PHP_EOL;

// Test 4: Test zone creation (if possible)
echo "4. TESTING ZONE CREATION" . PHP_EOL;
echo "========================" . PHP_EOL;

$test_zone_data = [
    'name' => 'test-' . time() . '.example.com.',
    'kind' => 'Master',
    'nameservers' => ['ns1.example.com.', 'ns2.example.com.']
];

echo "Attempting to create zone: " . $test_zone_data['name'] . PHP_EOL;
$zone_create_result = $client->createDomain($test_zone_data);

echo "Zone creation status: " . $zone_create_result['status_code'];

if ($zone_create_result['status_code'] == 201 || $zone_create_result['status_code'] == 200) {
    echo " ✅ SUCCESS" . PHP_EOL;
} else {
    echo " ❌ FAILED" . PHP_EOL;
    echo "Response: " . substr($zone_create_result['raw_response'], 0, 200) . PHP_EOL;
}

echo PHP_EOL;

// Test 5: Clean up - Delete test users
echo "5. CLEANUP - DELETING TEST USERS" . PHP_EOL;
echo "=================================" . PHP_EOL;

foreach ($found_test_users as $user) {
    echo "Deleting user: " . $user['username'] . " (ID: " . $user['id'] . ")" . PHP_EOL;
    $delete_result = $client->deleteAccount($user['id']);
    
    echo "Delete status: " . $delete_result['status_code'];
    
    if ($delete_result['status_code'] == 200 || $delete_result['status_code'] == 204) {
        echo " ✅ DELETED" . PHP_EOL;
    } else {
        echo " ❌ FAILED TO DELETE" . PHP_EOL;
        echo "Response: " . substr($delete_result['raw_response'], 0, 200) . PHP_EOL;
    }
}

echo PHP_EOL;

// Test 6: Final verification
echo "6. FINAL VERIFICATION" . PHP_EOL;
echo "=====================" . PHP_EOL;

$final_users = $client->getAllUsers();
echo "Final user count: " . count($final_users['data'] ?? []) . PHP_EOL;

$final_zones = $client->makeRequest('/pdnsadmin/zones');
echo "Final zone count: " . count($final_zones['data'] ?? []) . PHP_EOL;

echo PHP_EOL;
echo "=== ADVANCED CRUD TESTING COMPLETE ===" . PHP_EOL;
echo "All major operations tested: CREATE, READ, UPDATE, DELETE" . PHP_EOL;
?>
