<?php
// Test to find correct PowerDNS API server ID and zone details
$base_path = __DIR__;
require_once $base_path . '/config/config.php';
require_once $base_path . '/classes/PDNSAdminClient.php';

echo "Finding correct PowerDNS API endpoints...\n";
echo "Base URL: https://dnsadmin.avant.nl/api/v1\n\n";

// Initialize PDNSAdmin client
$pdns_client = new PDNSAdminClient($pdns_config);

// Test 1: Try to find PowerDNS servers
echo "=== Test 1: Find PowerDNS servers ===\n";
$servers_response = $pdns_client->makeRequest('/api/v1/servers', 'GET');
echo "Status Code: " . $servers_response['status_code'] . "\n";
if ($servers_response['status_code'] == 200) {
    $servers = $servers_response['data'];
    echo "Found servers:\n";
    print_r($servers);
    
    // If we found servers, try to get zones from the first one
    if (!empty($servers)) {
        $server_id = $servers[0]['id'] ?? $servers[0]['name'] ?? 'localhost';
        echo "\nTrying zones for server: $server_id\n";
        $zones_response = $pdns_client->makeRequest("/api/v1/servers/{$server_id}/zones", 'GET');
        echo "Zones Status Code: " . $zones_response['status_code'] . "\n";
        if ($zones_response['status_code'] == 200) {
            $zones = $zones_response['data'];
            echo "Found " . count($zones) . " zones\n";
            if (!empty($zones)) {
                echo "First zone structure:\n";
                print_r($zones[0]);
            }
        } else {
            echo "Zones Error: " . json_encode($zones_response['data']) . "\n";
        }
    }
} else {
    echo "Error: " . json_encode($servers_response['data']) . "\n";
}

// Test 2: Try different server IDs
$server_ids = ['localhost', '0', '1', 'default'];
echo "\n=== Test 2: Try different server IDs ===\n";
foreach ($server_ids as $server_id) {
    echo "Trying server ID: $server_id\n";
    $zones_response = $pdns_client->makeRequest("/api/v1/servers/{$server_id}/zones", 'GET');
    echo "Status Code: " . $zones_response['status_code'] . "\n";
    if ($zones_response['status_code'] == 200) {
        $zones = $zones_response['data'];
        echo "✓ SUCCESS! Found " . count($zones) . " zones for server $server_id\n";
        if (!empty($zones)) {
            echo "First zone structure:\n";
            print_r($zones[0]);
        }
        break;
    } else {
        echo "✗ Failed for server $server_id\n";
    }
}

// Test 3: Check PowerDNS Admin specific zone detail endpoint
echo "\n=== Test 3: PowerDNS Admin zone details ===\n";
$pdns_admin_zones = $pdns_client->makeRequest('/pdnsadmin/zones', 'GET');
if ($pdns_admin_zones['status_code'] == 200 && !empty($pdns_admin_zones['data'])) {
    $first_zone = $pdns_admin_zones['data'][0];
    $zone_name = $first_zone['name'];
    
    echo "Trying to get details for zone: $zone_name\n";
    $zone_detail = $pdns_client->makeRequest("/pdnsadmin/zones/{$zone_name}", 'GET');
    echo "Zone detail status: " . $zone_detail['status_code'] . "\n";
    if ($zone_detail['status_code'] == 200) {
        echo "Zone detail structure:\n";
        print_r($zone_detail['data']);
    } else {
        echo "Zone detail error: " . json_encode($zone_detail['data']) . "\n";
    }
}

echo "\nTest completed!\n";
?>
