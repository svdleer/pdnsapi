<?php
/**
 * Test script for proper REST endpoints
 * Tests GET, PUT, DELETE on /accounts with JSON payloads
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

echo "=== Testing proper REST endpoints ===\n\n";

// Test 1: GET /accounts with JSON payload for specific account
echo "1. Testing GET /accounts with JSON payload {\"id\": 1}...\n";
$json_data = json_encode(['id' => 1]);
$response = makeRequest($base_url . '/accounts', 'GET', $json_data, $auth_header);
echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

// Test 2: GET /accounts with JSON payload for username
echo "2. Testing GET /accounts with JSON payload {\"username\": \"admin\"}...\n";
$json_data = json_encode(['username' => 'admin']);
$response = makeRequest($base_url . '/accounts', 'GET', $json_data, $auth_header);
echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

// Test 3: GET /domains with JSON payload for specific domain
echo "3. Testing GET /domains with JSON payload {\"id\": 1}...\n";
$json_data = json_encode(['id' => 1]);
$response = makeRequest($base_url . '/domains', 'GET', $json_data, $auth_header);
echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

// Test 4: GET /accounts without payload (should return all)
echo "4. Testing GET /accounts without payload (all accounts)...\n";
$response = makeRequest($base_url . '/accounts', 'GET', '', $auth_header);
echo "Response data count: " . (isset($response['response']['data']) ? count($response['response']['data']) : 'N/A') . "\n";
echo "HTTP Code: " . $response['http_code'] . "\n\n";

function makeRequest($url, $method, $json_data, $auth_header) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $headers = [$auth_header];
    
    if (!empty($json_data)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Content-Length: ' . strlen($json_data);
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
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
