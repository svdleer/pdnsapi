<?php
/**
 * Test PowerDNS Server API /servers/1/zones endpoint
 */

$base_path = __DIR__ . '/php-api';

require_once $base_path . '/config/config.php';
require_once $base_path . '/classes/PDNSAdminClient.php';

echo "======================================\n";
echo "  PowerDNS Server API Test\n";
echo "======================================\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

try {
    $client = new PDNSAdminClient($pdns_config);
    echo "PDNSAdminClient created successfully\n";
    echo "Config auth_type: " . $pdns_config['auth_type'] . "\n";
    echo "Config base_url: " . $pdns_config['base_url'] . "\n";
    echo "Using PowerDNS server API key for /servers/1/zones\n\n";
    
    // Test the PowerDNS server API endpoint
    $result = $client->makeRequest('/servers/1/zones', 'GET');
    
    echo "📊 PowerDNS Server API results:\n";
    echo "HTTP Code: " . $result['status_code'] . "\n";
    echo "Response data type: " . gettype($result['data']) . "\n";
    
    if ($result['status_code'] == 200 && $result['data']) {
        if (is_array($result['data'])) {
            echo "✅ Success! Data is an array with " . count($result['data']) . " zones\n";
            if (count($result['data']) > 0) {
                echo "First zone keys: " . implode(', ', array_keys($result['data'][0])) . "\n";
                echo "Sample zone: " . $result['data'][0]['name'] . "\n";
            }
        } else {
            echo "❌ Unexpected data structure\n";
            echo "Data: " . print_r($result['data'], true) . "\n";
        }
    } else {
        echo "❌ Error response\n";
        echo "HTTP Code: " . $result['status_code'] . "\n";
        if (isset($result['data'])) {
            echo "Error data: " . print_r($result['data'], true) . "\n";
        }
    }
    
    if ($result['raw_response']) {
        echo "Raw response (first 200 chars): " . substr($result['raw_response'], 0, 200) . "...\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\nTest completed at: " . date('Y-m-d H:i:s') . "\n";
?>
