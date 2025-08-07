<?php
require_once 'php-api/config/config.php';
require_once 'php-api/classes/PDNSAdminClient.php';

echo "Testing PDNSAdminClient directly...\n";

$client = new PDNSAdminClient($pdns_config);

echo "Config API Key: " . substr($pdns_config['api_key'], 0, 10) . "...\n";
echo "Making request to /pdnsadmin/zones with 10s timeout...\n";

// Add timeout to prevent hanging
set_time_limit(15);

$result = $client->makeRequest('/pdnsadmin/zones', 'GET');

echo "Status Code: " . $result['status_code'] . "\n";
echo "Raw Response Length: " . strlen($result['raw_response']) . " characters\n";

if ($result['status_code'] == 200) {
    $domains = $result['data'];
    if (is_array($domains)) {
        echo "Number of domains found: " . count($domains) . "\n";
        if (count($domains) > 0) {
            echo "First domain: " . json_encode($domains[0], JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "Response is not an array: " . gettype($result['data']) . "\n";
    }
} else {
    echo "Error response: " . $result['raw_response'] . "\n";
}
?>
