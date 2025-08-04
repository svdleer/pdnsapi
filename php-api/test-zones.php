<?php
require_once 'classes/PDNSAdminClient.php';
require_once 'config/config.php';

echo "=== Testing Zones Endpoint ===\n\n";

$client = new PDNSAdminClient($pdns_config);

// Test the correct zones endpoint
$endpoint = '/servers/localhost/zones';
echo "Testing: $endpoint\n";

$response = $client->makeRequest($endpoint, 'GET');
$status_code = $response['status_code'];
$data = $response['data'];
$raw_response = $response['raw_response'];

echo "HTTP $status_code - " . strlen($raw_response) . " bytes\n";

if ($status_code == 200) {
    echo "✅ SUCCESS!\n";
    if (is_array($data) && count($data) > 0) {
        echo "Found " . count($data) . " zones:\n";
        foreach (array_slice($data, 0, 5) as $i => $zone) {
            if (is_array($zone) && isset($zone['name'])) {
                echo "  " . ($i + 1) . ". " . $zone['name'] . "\n";
            }
        }
        if (count($data) > 5) {
            echo "  ... and " . (count($data) - 5) . " more\n";
        }
    }
} else {
    echo "❌ FAILED\n";
    echo "Response: " . $raw_response . "\n";
}
?>
