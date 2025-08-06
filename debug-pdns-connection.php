<?php
/**
 * PowerDNS Admin Connection Debug Script
 * 
 * This script tests the connection to PowerDNS Admin and helps diagnose domain fetching issues.
 */

$base_path = __DIR__ . '/php-api';

require_once $base_path . '/config/config.php';
require_once $base_path . '/classes/PDNSAdminClient.php';

echo "========================================\n";
echo "  PowerDNS Admin Connection Debug\n";
echo "========================================\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

// Initialize PDNSAdmin client
$pdns_client = new PDNSAdminClient($pdns_config);

echo "🔍 Testing PowerDNS Admin connection...\n\n";

echo "Configuration:\n";
echo "  Base URL: " . $pdns_config['base_url'] . "\n";
echo "  Auth Type: " . ($pdns_config['auth_type'] ?? 'apikey') . "\n";
echo "  API Key: " . (isset($pdns_config['api_key']) ? 'Set (' . substr($pdns_config['api_key'], 0, 10) . '...)' : 'Not set') . "\n";
echo "  Username: " . ($pdns_config['username'] ?? 'Not set') . "\n";
echo "  Password: " . (isset($pdns_config['password']) ? 'Set' : 'Not set') . "\n\n";

// Test different endpoints
$endpoints_to_test = [
    '/pdnsadmin/zones' => 'PowerDNS Admin Zones API',
    '/servers/localhost/zones' => 'PowerDNS Server API (localhost)',
    '/servers/1/zones' => 'PowerDNS Server API (server 1)',
    '/api/v1/servers/localhost/zones' => 'PowerDNS API v1',
    '/pdnsadmin/accounts' => 'PowerDNS Admin Accounts API',
    '/pdnsadmin/statistics' => 'PowerDNS Admin Statistics'
];

foreach ($endpoints_to_test as $endpoint => $description) {
    echo "Testing: $description ($endpoint)\n";
    
    $response = $pdns_client->makeRequest($endpoint, 'GET');
    
    echo "  Status Code: " . ($response['status_code'] ?? 'Unknown') . "\n";
    
    if (isset($response['data'])) {
        if (is_array($response['data'])) {
            echo "  Response Type: Array with " . count($response['data']) . " items\n";
            if (count($response['data']) > 0 && is_array($response['data'][0])) {
                echo "  First Item Keys: " . implode(', ', array_keys($response['data'][0])) . "\n";
            } elseif (count($response['data']) > 0) {
                echo "  First Item: " . print_r($response['data'][0], true) . "\n";
            }
        } else {
            echo "  Response Type: " . gettype($response['data']) . "\n";
            echo "  Response Preview: " . substr(print_r($response['data'], true), 0, 200) . "\n";
        }
    }
    
    if (isset($response['error'])) {
        echo "  Error: " . $response['error'] . "\n";
    }
    
    if (isset($response['raw_response']) && !empty($response['raw_response'])) {
        $raw_preview = substr(strip_tags($response['raw_response']), 0, 100);
        echo "  Raw Response Preview: " . $raw_preview . "\n";
    }
    
    echo "\n";
}

// Test a simple connectivity check
echo "🌐 Testing basic connectivity...\n";
$test_response = $pdns_client->makeRequest('/', 'GET');
echo "  Root endpoint status: " . ($test_response['status_code'] ?? 'Unknown') . "\n";
if (isset($test_response['raw_response'])) {
    $raw_preview = substr(strip_tags($test_response['raw_response']), 0, 150);
    echo "  Root response preview: " . $raw_preview . "\n";
}

echo "\n========================================\n";
echo "Debug completed at: " . date('Y-m-d H:i:s') . "\n";
echo "========================================\n";

?>
