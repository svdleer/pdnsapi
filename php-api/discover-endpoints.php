<?php
require_once 'classes/PDNSAdminClient.php';
require_once 'config/config.php';

echo "=== PowerDNS Admin API Endpoint Discovery ===\n\n";

$client = new PDNSAdminClient($pdns_config);

// Test various endpoints to see what's available
$test_endpoints = [
    '/',                    // Root
    '/servers',             // Servers list
    '/servers/1',           // Server info
    '/servers/localhost',   // Alternative server name
    '/domains',             // Domains (PowerDNS Admin specific)
    '/zones',               // Zones (direct)
    '/pdnsadmin/domains',   // PowerDNS Admin domains
    '/api/v1/servers',      // Try with full path
];

foreach ($test_endpoints as $endpoint) {
    echo "Testing: $endpoint\n";
    
    try {
        $response = $client->makeRequest($endpoint, 'GET');
        $status_code = $response['status_code'];
        $data = $response['data'];
        $raw_response = $response['raw_response'];
        
        echo "  📊 HTTP $status_code - " . strlen($raw_response) . " bytes\n";
        
        if ($status_code == 200) {
            echo "  ✅ SUCCESS\n";
            if (is_array($data) && count($data) > 0) {
                echo "  📋 Contains " . count($data) . " items\n";
                if (isset($data[0]) && is_array($data[0])) {
                    echo "  🔍 First item keys: " . implode(', ', array_keys($data[0])) . "\n";
                }
            } elseif (is_array($data)) {
                echo "  📄 Response keys: " . implode(', ', array_keys($data)) . "\n";
            } elseif ($data) {
                echo "  📝 Response: " . substr(json_encode($data), 0, 100) . "...\n";
            }
        } elseif ($status_code == 404) {
            echo "  ❌ NOT FOUND\n";
        } elseif ($status_code == 401) {
            echo "  🔐 UNAUTHORIZED\n";
        } else {
            echo "  ❓ Response: " . substr($raw_response, 0, 100) . "...\n";
        }
    } catch (Exception $e) {
        echo "  ❌ ERROR: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}
?>
