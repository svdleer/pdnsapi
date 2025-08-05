<?php
/**
 * Remote test script for accounts API endpoint
 */

// Configuration
$base_url = 'https://dnsadmin.avant.nl/php-api/api';
$api_key = 'your-api-key-here'; // Not required since auth is disabled

// Test data
$test_account = [
    'name' => 'testuser_' . time(),
    'description' => 'Test User Created Remotely',
    'contact' => 'Remote Test Contact',
    'mail' => 'remotetest@example.com'
];

echo "=== Remote Accounts API Test ===\n";
echo "Base URL: $base_url\n";
echo "Testing account creation with data: " . json_encode($test_account) . "\n\n";

// Test 1: Create account
echo "1. Testing POST /accounts (Create Account)\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $base_url . '/accounts');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_account));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-API-Key: ' . $api_key
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For testing only
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

if ($error) {
    echo "CURL Error: $error\n";
} else {
    echo "HTTP Code: $http_code\n";
    echo "Response: $response\n";
}
curl_close($ch);

echo "\n" . str_repeat("-", 50) . "\n\n";

// Test 2: Get all accounts
echo "2. Testing GET /accounts (List All Accounts)\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $base_url . '/accounts');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-API-Key: ' . $api_key
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

if ($error) {
    echo "CURL Error: $error\n";
} else {
    echo "HTTP Code: $http_code\n";
    echo "Response: $response\n";
}
curl_close($ch);

echo "\n" . str_repeat("-", 50) . "\n\n";

// Test 3: Test sync functionality
echo "3. Testing GET /accounts?sync=true (Sync from PowerDNS Admin DB)\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $base_url . '/accounts?sync=true');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-API-Key: ' . $api_key
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

if ($error) {
    echo "CURL Error: $error\n";
} else {
    echo "HTTP Code: $http_code\n";
    echo "Response: $response\n";
}
curl_close($ch);

echo "\n=== Test Complete ===\n";
?>
