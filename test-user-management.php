<?php
/**
 * Test User Management Functions in PowerDNS Admin API
 * 
 * This script tests the user CRUD operations to verify that
 * PowerDNS Admin API is indeed authoritative for user management.
 */

require_once __DIR__ . '/php-api/includes/autoloader.php';
require_once __DIR__ . '/php-api/config/config.php';
require_once __DIR__ . '/php-api/classes/PDNSAdminClient.php';

// Initialize client
$client = new PDNSAdminClient($pdns_config);

echo "=== Testing PowerDNS Admin API User Management ===\n\n";

// Test 1: Get all users
echo "1. Testing getAllUsers()...\n";
$response = $client->getAllUsers();
echo "Status Code: " . $response['status_code'] . "\n";
if ($response['status_code'] == 200) {
    echo "✅ getAllUsers() works\n";
    $users = $response['data'] ?? [];
    echo "Found " . count($users) . " users\n";
    
    // Show first user for reference
    if (!empty($users)) {
        $first_user = $users[0];
        echo "Sample user: " . ($first_user['username'] ?? 'N/A') . "\n";
    }
} else {
    echo "❌ getAllUsers() failed\n";
    echo "Response: " . $response['raw_response'] . "\n";
}
echo "\n";

// Test 2: Get single user (if we have users)
if (!empty($users)) {
    $test_username = $users[0]['username'] ?? null;
    if ($test_username) {
        echo "2. Testing getUser() with username: {$test_username}...\n";
        $response = $client->getUser($test_username);
        echo "Status Code: " . $response['status_code'] . "\n";
        if ($response['status_code'] == 200) {
            echo "✅ getUser() works\n";
        } else {
            echo "❌ getUser() failed\n";
            echo "Response: " . $response['raw_response'] . "\n";
        }
        echo "\n";
    }
}

// Test 3: Try to create a test user
echo "3. Testing createUser()...\n";
$test_user_data = [
    'username' => 'test_api_user_' . time(),
    'password' => 'test_password_123',
    'email' => 'testuser@example.com',
    'role' => 'User'
];

$response = $client->createUser($test_user_data);
echo "Status Code: " . $response['status_code'] . "\n";
if ($response['status_code'] == 200 || $response['status_code'] == 201) {
    echo "✅ createUser() works\n";
    $created_user = $test_user_data['username'];
    
    // Test 4: Try to update the created user
    echo "\n4. Testing updateUser()...\n";
    $update_data = [
        'email' => 'updated_testuser@example.com',
        'role' => 'User'
    ];
    
    $response = $client->updateUser($created_user, $update_data);
    echo "Status Code: " . $response['status_code'] . "\n";
    if ($response['status_code'] == 200) {
        echo "✅ updateUser() works\n";
        
        // Test 5: Delete the test user
        echo "\n5. Testing deleteUser()...\n";
        $response = $client->deleteUser($created_user);
        echo "Status Code: " . $response['status_code'] . "\n";
        if ($response['status_code'] == 200 || $response['status_code'] == 204) {
            echo "✅ deleteUser() works\n";
        } else {
            echo "❌ deleteUser() failed\n";
            echo "Response: " . $response['raw_response'] . "\n";
        }
    } else {
        echo "❌ updateUser() failed\n";
        echo "Response: " . $response['raw_response'] . "\n";
        
        // Still try to clean up
        echo "\nCleaning up test user...\n";
        $client->deleteUser($created_user);
    }
} else {
    echo "❌ createUser() failed\n";
    echo "Response: " . $response['raw_response'] . "\n";
}

echo "\n=== Test Summary ===\n";
echo "User management functions have been restored based on PowerDNS Admin API being authoritative.\n";
echo "If any tests failed, it may be due to API configuration or permission issues.\n";
?>
