<?php
/**
 * PDNSAdmin Connection Test Script
 * Use this to test your PDNSAdmin credentials before using the API
 */

// Mock web environment for CLI testing
$_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/PDNSAdminClient.php';

echo "Testing PDNSAdmin Connection...\n";
echo "================================\n\n";

echo "Configuration:\n";
echo "- Base URL: " . $pdns_config['base_url'] . "\n";
echo "- Auth Type: " . $pdns_config['auth_type'] . "\n";
echo "- Username: " . ($pdns_config['username'] ?? 'NOT SET') . "\n";
echo "- Password: " . (empty($pdns_config['password']) ? 'NOT SET' : '[SET]') . "\n";
echo "- API Key: " . (empty($pdns_config['api_key']) ? 'NOT SET' : '[SET]') . "\n\n";

try {
    $client = new PDNSAdminClient($pdns_config);
    
    echo "Attempting to connect...\n";
    $response = $client->getAllDomains();
    
    if ($response === false || $response['status_code'] >= 400) {
        echo "❌ FAILED: Could not connect to PDNSAdmin\n";
        echo "Status Code: " . ($response['status_code'] ?? 'Unknown') . "\n";
        echo "Response: " . ($response['raw_response'] ?? 'No response') . "\n";
        echo "Check your credentials and URL\n";
    } else {
        echo "✅ SUCCESS: Connected to PDNSAdmin\n";
        echo "Status Code: " . $response['status_code'] . "\n";
        
        $domains = $response['data'] ?? [];
        echo "Found " . count($domains) . " domains\n";
        
        if (!empty($domains)) {
            echo "\nFirst few domains:\n";
            foreach (array_slice($domains, 0, 3) as $domain) {
                echo "- " . ($domain['name'] ?? 'Unknown') . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n================================\n";
echo "Test completed.\n";
?>
