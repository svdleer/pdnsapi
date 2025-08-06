<?php
/**
 * Test PowerDNS Server API with direct curl and different auth methods
 */

$base_path = __DIR__ . '/php-api';
require_once $base_path . '/config/config.php';

echo "======================================\n";
echo "  PowerDNS Server API Auth Test\n";
echo "======================================\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

$test_url = "https://dnsadmin.avant.nl/api/v1/servers/1/zones";
$server_key = $pdns_config['pdns_server_key']; // morWehofCidwiWejishOwko=!b
$basic_auth = $pdns_config['api_key']; // YWRtaW46ZG5WZWt1OEpla3U=

echo "🔍 Testing PowerDNS Server API with different auth methods...\n";
echo "URL: $test_url\n\n";

// Test 1: X-API-Key header with server key
echo "Test 1: X-API-Key header with PowerDNS server key\n";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $test_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-API-Key: ' . $server_key
    ],
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $http_code\n";
echo "Response: " . substr($response, 0, 100) . "\n\n";

// Test 2: Basic auth with server key
echo "Test 2: Basic auth with PowerDNS server key\n";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $test_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($server_key . ':')
    ],
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $http_code\n";
echo "Response: " . substr($response, 0, 100) . "\n\n";

// Test 3: Basic auth with admin credentials
echo "Test 3: Basic auth with admin credentials\n";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $test_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Basic ' . $basic_auth
    ],
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $http_code\n";
echo "Response: " . substr($response, 0, 100) . "\n\n";

echo "Test completed at: " . date('Y-m-d H:i:s') . "\n";
?>
