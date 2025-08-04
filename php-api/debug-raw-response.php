<?php
require_once 'classes/PDNSAdminClient.php';
require_once 'config/config.php';

// Initialize PDNSAdmin client
$pdns_client = new PDNSAdminClient($pdns_config);

echo "Getting domains from PowerDNS Admin...\n";

// Test the getAllDomains method
$response = $pdns_client->getAllDomains();

echo "HTTP Status Code: " . $response['status_code'] . "\n";
echo "Raw response: " . $response['body'] . "\n";

if ($response['status_code'] == 200) {
    $domains = $response['data'];
    if (!empty($domains)) {
        echo "\nFirst domain structure:\n";
        print_r($domains[0]);
    }
}
?>
