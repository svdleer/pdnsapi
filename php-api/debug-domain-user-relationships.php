<?php
// Debug script to find domain-user relationships in PowerDNS Admin
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'classes/PDNSAdminClient.php';

// Initialize PDNSAdmin client
$pdns_client = new PDNSAdminClient($pdns_config);

echo "Looking for domain-user relationships in PowerDNS Admin...\n\n";

// Test more specific endpoints that might show relationships
$relationship_endpoints = [
    '/pdnsadmin/users/1/zones',
    '/pdnsadmin/users/1/domains', 
    '/pdnsadmin/zones/permissions',
    '/pdnsadmin/permissions',
    '/pdnsadmin/domain_permissions',
    '/pdnsadmin/user_zones',
    '/pdnsadmin/zone_users',
    '/pdnsadmin/ownership'
];

foreach ($relationship_endpoints as $endpoint) {
    echo "Testing: $endpoint\n";
    $response = $pdns_client->makeRequest($endpoint);
    echo "  Status: " . $response['status_code'] . "\n";
    
    if ($response['status_code'] == 200) {
        echo "  SUCCESS! Found relationship data.\n";
        if (is_array($response['data'])) {
            echo "  Found " . count($response['data']) . " items\n";
            if (count($response['data']) > 0) {
                echo "  Sample:\n";
                echo "    " . json_encode($response['data'][0], JSON_PRETTY_PRINT) . "\n";
            }
        } else {
            echo "  Data: " . json_encode($response['data'], JSON_PRETTY_PRINT) . "\n";
        }
    } else if ($response['status_code'] == 404) {
        echo "  Endpoint not found\n";
    } else {
        echo "  Error: " . $response['status_code'] . "\n";
    }
    echo "\n";
}

// Let's also check what data is available for a specific user
echo "Getting detailed info for user ID 1 (admin):\n";
$admin_response = $pdns_client->makeRequest('/pdnsadmin/users/1');
if ($admin_response['status_code'] == 200) {
    echo "Admin user details:\n";
    echo json_encode($admin_response['data'], JSON_PRETTY_PRINT) . "\n";
} else {
    echo "Failed to get admin user details: " . $admin_response['status_code'] . "\n";
}

echo "\n";

// Check if there are any zone management endpoints
echo "Testing zone management endpoints:\n";
$zone_endpoints = [
    '/pdnsadmin/zones?owner=1',
    '/pdnsadmin/zones?user_id=1',
    '/pdnsadmin/manage/zones',
    '/pdnsadmin/user/zones'
];

foreach ($zone_endpoints as $endpoint) {
    echo "Testing: $endpoint\n";
    $response = $pdns_client->makeRequest($endpoint);
    echo "  Status: " . $response['status_code'] . "\n";
    if ($response['status_code'] == 200 && is_array($response['data'])) {
        echo "  Found " . count($response['data']) . " zones\n";
    }
    echo "\n";
}
?>
