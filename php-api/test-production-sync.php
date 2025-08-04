<?php
require_once 'classes/PDNSAdminClient.php';

// Load configuration
$pdns_config = [
    'base_url' => 'https://dnsadmin.avant.nl/api/v1',
    'api_key' => 'YWRtaW46ZG5WZWt1OEpla3U=',
    'auth_type' => 'apikey'
];

// Initialize PDNSAdmin client
$pdns_client = new PDNSAdminClient($pdns_config);

echo "Testing PowerDNS Admin API connection...\n";
echo "Base URL: " . $pdns_config['base_url'] . "\n";
echo "Using endpoint: /servers/1/zones\n\n";

// Test the getAllDomains method
$response = $pdns_client->getAllDomains();

echo "HTTP Status Code: " . $response['status_code'] . "\n";

if ($response['status_code'] == 200) {
    $domains = $response['data'];
    echo "Success! Retrieved " . count($domains) . " domains\n\n";
    
    // Show first few domains
    echo "First 3 domains:\n";
    for ($i = 0; $i < min(3, count($domains)); $i++) {
        $domain = $domains[$i];
        echo "- Domain: " . ($domain['name'] ?? 'N/A') . "\n";
        echo "  ID: " . ($domain['id'] ?? 'N/A') . "\n";
        echo "  Type: " . ($domain['type'] ?? 'N/A') . "\n";
        echo "  Account: " . (isset($domain['account']) ? $domain['account'] : 'N/A') . "\n";
        echo "\n";
    }
} else {
    echo "Error!\n";
    echo "Response: " . $response['raw_response'] . "\n";
}
?>
