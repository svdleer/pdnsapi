<?php
echo "Direct cURL Test for PDNSAdmin API\n";
echo "==================================\n\n";

$api_key = 'WndRSU5HdUpUVEpRNjcw';
$url = 'https://dnsadmin.avant.nl/api/v1/pdnsadmin/zones';

echo "Testing URL: $url\n";
echo "Using API Key: $api_key\n\n";

// Test 1: X-API-Key header
echo "Test 1: X-API-Key header\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-API-Key: ' . $api_key
]);
curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_STDERR, fopen('php://stdout', 'w'));

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: $http_code\n";
echo "Response: $response\n\n";

// Test 2: Authorization Bearer header
echo "Test 2: Authorization Bearer header\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $api_key
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: $http_code\n";
echo "Response: $response\n\n";

// Test 3: Authorization X-API-Key format
echo "Test 3: Authorization X-API-Key format\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: X-API-Key ' . $api_key
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: $http_code\n";
echo "Response: $response\n";
?>
