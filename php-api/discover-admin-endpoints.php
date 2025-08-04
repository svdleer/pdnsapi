<?php
require_once 'classes/PDNSAdminClient.php';
require_once 'config/config.php';

echo "=== PowerDNS Admin Endpoints Discovery ===\n\n";

$client = new PDNSAdminClient($pdns_config);

// Test PowerDNS Admin specific endpoints
$admin_endpoints = [
    '/pdnsadmin/domains',           // PowerDNS Admin domains
    '/pdnsadmin/zones',             // PowerDNS Admin zones  
    '/pdnsadmin/accounts',          // PowerDNS Admin accounts
    '/pdnsadmin/users',             // PowerDNS Admin users
    '/accounts',                    // Direct accounts
    '/users',                       // Direct users
    '/domains',                     // Direct domains
];

foreach ($admin_endpoints as $endpoint) {
    echo "Testing: $endpoint\n";
    
    $response = $client->makeRequest($endpoint, 'GET');
    $status_code = $response['status_code'];
    $data = $response['data'];
    $raw_response = $response['raw_response'];
    
    echo "  📊 HTTP $status_code - " . strlen($raw_response) . " bytes\n";
    
    if ($status_code == 200) {
        echo "  ✅ SUCCESS\n";
        if (is_array($data) && count($data) > 0) {
            echo "  📋 Contains " . count($data) . " items\n";
            
            // Show structure of first item
            if (isset($data[0]) && is_array($data[0])) {
                echo "  🔍 First item keys: " . implode(', ', array_keys($data[0])) . "\n";
                
                // Check for account-related fields
                $account_fields = array_filter(array_keys($data[0]), function($key) {
                    return strpos(strtolower($key), 'account') !== false || 
                           strpos(strtolower($key), 'user') !== false ||
                           strpos(strtolower($key), 'owner') !== false;
                });
                
                if (!empty($account_fields)) {
                    echo "  👤 Account-related fields: " . implode(', ', $account_fields) . "\n";
                }
            }
        } elseif (is_array($data)) {
            echo "  📄 Response keys: " . implode(', ', array_keys($data)) . "\n";
        }
    } elseif ($status_code == 404) {
        echo "  ❌ NOT FOUND\n";
    } elseif ($status_code == 401) {
        echo "  🔐 UNAUTHORIZED\n";
    } else {
        echo "  ❓ Response: " . substr($raw_response, 0, 100) . "...\n";
    }
    
    echo "\n";
}
?>
