<?php
$base_path = __DIR__ . '/php-api';
require_once $base_path . '/config/config.php';
require_once $base_path . '/classes/PDNSAdminClient.php';

$client = new PDNSAdminClient($pdns_config);

echo "Testing corrected endpoint...\n";
echo "Base URL: " . $pdns_config['base_url'] . "\n";
echo "Calling: /pdnsadmin/zones\n";
echo "Full URL will be: " . $pdns_config['base_url'] . "/pdnsadmin/zones\n\n";

$result = $client->makeRequest('/pdnsadmin/zones', 'GET');

echo "Status Code: " . $result['status_code'] . "\n";
echo "Data Type: " . gettype($result['data']) . "\n";

if (is_array($result['data'])) {
    echo "Array Count: " . count($result['data']) . "\n";
    if (count($result['data']) > 0) {
        echo "First item: " . json_encode($result['data'][0]) . "\n";
    }
} else {
    echo "Data: " . json_encode($result['data']) . "\n";
}

echo "Raw Response (first 200 chars): " . substr($result['raw_response'], 0, 200) . "\n";
?>
