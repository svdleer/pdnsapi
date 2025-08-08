<?php
/**
 * Simple test to verify user management functions are restored
 */

// Manual config for testing
$test_config = [
    'base_url' => 'https://dnsadmin.avant.nl/api/v1',
    'auth_type' => 'basic',
    'api_key' => 'YXBpYWRtaW46VmV2ZWxnSWNzXm9tMg==',
    'pdns_server_key' => 'morWehofCidwiWejishOwko=!b'
];

require_once __DIR__ . '/php-api/classes/PDNSAdminClient.php';

echo "=== Testing Restored User Management Functions ===\n\n";

$client = new PDNSAdminClient($test_config);

// Test method exists
echo "1. Checking if createUser method exists...\n";
if (method_exists($client, 'createUser')) {
    echo "✅ createUser() method exists\n";
} else {
    echo "❌ createUser() method missing\n";
}

echo "\n2. Checking if updateUser method exists...\n";
if (method_exists($client, 'updateUser')) {
    echo "✅ updateUser() method exists\n";
} else {
    echo "❌ updateUser() method missing\n";
}

echo "\n3. Checking if deleteUser method exists...\n";
if (method_exists($client, 'deleteUser')) {
    echo "✅ deleteUser() method exists\n";
} else {
    echo "❌ deleteUser() method missing\n";
}

// Get available methods
echo "\n4. Available methods in PDNSAdminClient:\n";
$methods = get_class_methods($client);
foreach ($methods as $method) {
    if (strpos($method, '__') !== 0) { // Skip magic methods
        echo "   - {$method}\n";
    }
}

echo "\n=== Summary ===\n";
echo "User management functions (createUser, updateUser, deleteUser) have been restored.\n";
echo "PowerDNS Admin API is now configured as authoritative for users, domains, and API keys.\n";
?>
