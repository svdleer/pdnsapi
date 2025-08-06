<?php
/**
 * Test script for /domains API endpoints
 * Tests the existing PHP API that talks to PowerDNS Admin
 */

// Configuration
$base_url = 'https://pdnsapi.avant.nl';
$api_endpoints = [
    'GET /domains' => $base_url . '/domains',
    'GET /domains with query' => $base_url . '/domains?account_id=1',
    'POST /domains sync' => $base_url . '/domains'
];

echo "=== Testing /domains API Endpoints ===\n\n";

// Test GET /domains (list all domains)
echo "1. Testing GET /domains (list all)\n";
echo "URL: " . $api_endpoints['GET /domains'] . "\n";
echo "Method: GET\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_endpoints['GET /domains']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: " . $http_code . "\n";
echo "Response: " . substr($response, 0, 500) . (strlen($response) > 500 ? '...' : '') . "\n\n";

// Test GET /domains with query parameter
echo "2. Testing GET /domains with account_id filter\n";
echo "URL: " . $api_endpoints['GET /domains with query'] . "\n";
echo "Method: GET\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_endpoints['GET /domains with query']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: " . $http_code . "\n";
echo "Response: " . substr($response, 0, 500) . (strlen($response) > 500 ? '...' : '') . "\n\n";

// Test POST /domains (sync domains)
echo "3. Testing POST /domains (sync from PowerDNS Admin API)\n";
echo "URL: " . $api_endpoints['POST /domains sync'] . "\n";
echo "Method: POST\n";

$sync_data = json_encode([
    'action' => 'sync',
    'force' => false
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_endpoints['POST /domains sync']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $sync_data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Payload: " . $sync_data . "\n";
echo "Status: " . $http_code . "\n";
echo "Response: " . substr($response, 0, 500) . (strlen($response) > 500 ? '...' : '') . "\n\n";

// Test direct PowerDNS Admin API call (what your PHP API should be calling)
echo "4. Testing direct PowerDNS Admin API call\n";
echo "URL: https://dnsadmin.avant.nl/api/v1/servers/1/zones\n";
echo "Method: GET\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://dnsadmin.avant.nl/api/v1/servers/1/zones');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-API-Key: your-api-key-here',
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "Status: " . $http_code . "\n";
if ($error) {
    echo "cURL Error: " . $error . "\n";
}
echo "Response: " . substr($response, 0, 500) . (strlen($response) > 500 ? '...' : '') . "\n\n";

// Summary
echo "=== Test Summary ===\n";
echo "1. GET /domains - Tests listing all domains from your PHP API\n";
echo "2. GET /domains?account_id=1 - Tests filtered domain listing\n";
echo "3. POST /domains (sync) - Tests domain synchronization\n";
echo "4. Direct PowerDNS Admin API - Tests the underlying API your PHP calls\n\n";

echo "Note: Make sure your PHP API server is running and PowerDNS Admin API is accessible\n";
echo "Update the API key in test #4 with your actual PowerDNS Admin API key\n";
?>
