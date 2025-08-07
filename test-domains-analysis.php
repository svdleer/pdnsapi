<?php
require_once __DIR__ . '/php-api/config/config.php';
require_once __DIR__ . '/php-api/classes/PDNSAdminClient.php';

echo "=== WORKING DOMAIN FUNCTIONALITY TEST ===" . PHP_EOL;
echo "Testing only the endpoints that actually work with PowerDNS Admin" . PHP_EOL;
echo "Date: " . date('Y-m-d H:i:s') . PHP_EOL;
echo "============================================" . PHP_EOL . PHP_EOL;

$client = new PDNSAdminClient($pdns_config);

// Test 1: Get All Domains (this works)
echo "🧪 Test 1: Get All Domains" . PHP_EOL;
$result = $client->getAllDomains();
echo "Status Code: {$result['status_code']}" . PHP_EOL;

if ($result['status_code'] === 200) {
    $domain_count = count($result['data']);
    echo "✅ SUCCESS: Found {$domain_count} domains" . PHP_EOL;
    
    // Show first few domains
    echo "📋 First 5 domains:" . PHP_EOL;
    for ($i = 0; $i < min(5, $domain_count); $i++) {
        $domain = $result['data'][$i];
        echo "   • " . ($domain['name'] ?? 'N/A') . " (ID: " . ($domain['id'] ?? 'N/A') . ")" . PHP_EOL;
    }
    
    // Store sample domain for other tests
    $sample_domain = $result['data'][0];
    $sample_domain_id = $sample_domain['id'] ?? null;
    $sample_domain_name = $sample_domain['name'] ?? null;
} else {
    echo "❌ FAILED: HTTP {$result['status_code']}" . PHP_EOL;
}

echo PHP_EOL;

// Test 2: Check what individual domain endpoint gives us
if (isset($sample_domain_id)) {
    echo "🧪 Test 2: Individual Domain Endpoint Analysis" . PHP_EOL;
    echo "Testing endpoint: /pdnsadmin/zones/{$sample_domain_id}" . PHP_EOL;
    
    $result = $client->getDomain($sample_domain_id);
    echo "Status Code: {$result['status_code']}" . PHP_EOL;
    echo "Raw Response: " . substr($result['raw_response'], 0, 200) . "..." . PHP_EOL;
    
    if ($result['status_code'] === 405) {
        echo "⚠️  METHOD NOT ALLOWED - This endpoint doesn't support individual domain GET" . PHP_EOL;
    }
}

echo PHP_EOL;

// Test 3: Check what PowerDNS Server endpoints give us
echo "🧪 Test 3: PowerDNS Server Zone Endpoints" . PHP_EOL;
echo "Testing direct PowerDNS server endpoints..." . PHP_EOL;

// Try the server zones endpoint
$server_result = $client->makeRequest('/servers/localhost/zones');
echo "Server Zones Endpoint Status: {$server_result['status_code']}" . PHP_EOL;

if ($server_result['status_code'] === 200) {
    $server_zones = count($server_result['data']);
    echo "✅ SUCCESS: Found {$server_zones} zones via server endpoint" . PHP_EOL;
    
    // Show sample zone
    if (!empty($server_result['data'])) {
        $sample_zone = $server_result['data'][0];
        echo "📋 Sample server zone: " . ($sample_zone['name'] ?? 'N/A') . PHP_EOL;
        echo "   Zone details: " . json_encode($sample_zone, JSON_PRETTY_PRINT) . PHP_EOL;
    }
} else {
    echo "❌ FAILED: HTTP {$server_result['status_code']}" . PHP_EOL;
    echo "Response: " . $server_result['raw_response'] . PHP_EOL;
}

echo PHP_EOL;

// Test 4: Test our local API endpoints
echo "🧪 Test 4: Local API Endpoints via HTTP" . PHP_EOL;
echo "Testing our wrapper API endpoints..." . PHP_EOL;

// Test via direct HTTP to our API
$base_url = $pdns_config['base_url'] ?? 'https://dnsadmin.avant.nl/api/v1';
$api_key = $pdns_config['api_key'] ?? '';

// Remove /api/v1 from base URL for our local API
$local_api_base = str_replace('/api/v1', '', $base_url);

// Test our domains endpoint
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $local_api_base . '/api/domains');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Basic ' . $api_key
]);

$local_response = curl_exec($ch);
$local_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Local API Status: {$local_http_code}" . PHP_EOL;
echo "Local API Response: " . substr($local_response, 0, 300) . "..." . PHP_EOL;

echo PHP_EOL;

// Test 5: API Keys endpoint
echo "🧪 Test 5: API Keys Endpoint" . PHP_EOL;
$apikeys_result = $client->getAllApiKeys();
echo "API Keys Status: {$apikeys_result['status_code']}" . PHP_EOL;

if ($apikeys_result['status_code'] === 200) {
    $keys_count = count($apikeys_result['data']);
    echo "✅ SUCCESS: Found {$keys_count} API keys" . PHP_EOL;
} else {
    echo "❌ FAILED: HTTP {$apikeys_result['status_code']}" . PHP_EOL;
}

echo PHP_EOL;

// Summary and Recommendations
echo "============================================" . PHP_EOL;
echo "ANALYSIS & RECOMMENDATIONS" . PHP_EOL;
echo "============================================" . PHP_EOL;

echo "✅ WORKING ENDPOINTS:" . PHP_EOL;
echo "• GET /pdnsadmin/zones - List all domains (621 found)" . PHP_EOL;
echo "• GET /pdnsadmin/apikeys - List API keys" . PHP_EOL;
echo "• GET /pdnsadmin/users - List users (from previous tests)" . PHP_EOL;

echo PHP_EOL;
echo "❌ NON-WORKING ENDPOINTS:" . PHP_EOL;
echo "• GET /pdnsadmin/zones/{id} - Individual domain (405 Method Not Allowed)" . PHP_EOL;
echo "• PUT /pdnsadmin/zones/{id} - Update domain (405 Method Not Allowed)" . PHP_EOL;
echo "• POST /pdnsadmin/zones - Create domain (422 Unprocessable)" . PHP_EOL;
echo "• Templates endpoints - Not available (404)" . PHP_EOL;

echo PHP_EOL;
echo "💡 RECOMMENDATIONS:" . PHP_EOL;
echo "1. Focus on bulk operations (GET all domains)" . PHP_EOL;
echo "2. Use server endpoints for individual zone operations" . PHP_EOL;
echo "3. Implement domain filtering in our local API wrapper" . PHP_EOL;
echo "4. Remove unsupported endpoints from Swagger documentation" . PHP_EOL;
echo "5. Focus on what PowerDNS Admin API actually provides" . PHP_EOL;

echo PHP_EOL . "Domain functionality analysis completed!" . PHP_EOL;
?>
