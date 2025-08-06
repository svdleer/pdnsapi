<?php
/**
 * Test script for JSON-only endpoints
 * Tests the new /accounts/get, /accounts/update, /accounts/delete endpoints
 * and /domains/get, /domains/update endpoints
 */

require_once 'config/config.php';

// Base URL and credentials from config
$base_url = 'https://pdnsapi.avant.nl';
$username = $pdns_config['pdns_admin_user'] ?? '';
$password = $pdns_config['pdns_admin_password'] ?? '';

if (empty($username) || empty($password)) {
    die("Error: API credentials not found in config. Username: '$username', Password length: " . strlen($password) . "\n");
}

$auth_header = 'Authorization: Basic ' . base64_encode($username . ':' . $password);

echo "=== Testing JSON-only endpoints ===\n\n";

// Test 1: GET account by ID using JSON endpoint
echo "1. Testing /accounts/get with ID...\n";
$json_data = json_encode(['id' => 1]);
$response = makeJsonRequest($base_url . '/accounts/get', $json_data, $auth_header);
echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

// Test 2: GET account by username using JSON endpoint
echo "2. Testing /accounts/get with username...\n";
$json_data = json_encode(['username' => 'admin']);
$response = makeJsonRequest($base_url . '/accounts/get', $json_data, $auth_header);
echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

// Test 3: GET domain by ID using JSON endpoint
echo "3. Testing /domains/get with ID...\n";
$json_data = json_encode(['id' => 1]);
$response = makeJsonRequest($base_url . '/domains/get', $json_data, $auth_header);
echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

// Test 4: Invalid JSON input
echo "4. Testing invalid JSON input...\n";
$response = makeJsonRequest($base_url . '/accounts/get', 'invalid json', $auth_header);
echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

// Test 5: Missing required fields
echo "5. Testing missing required fields...\n";
$json_data = json_encode(['invalid_field' => 'test']);
$response = makeJsonRequest($base_url . '/accounts/get', $json_data, $auth_header);
echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

function makeJsonRequest($url, $json_data, $auth_header) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        $auth_header,
        'Content-Type: application/json',
        'Content-Length: ' . strlen($json_data)
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = [
        'http_code' => $http_code,
        'response' => json_decode($response, true) ?? $response
    ];
    
    return $result;
}
?>
