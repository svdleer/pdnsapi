<?php
// Simple debug to see what our production API gets from PowerDNS Admin
require_once 'config/config.php';
require_once 'classes/PDNSAdminClient.php';

$pdns_client = new PDNSAdminClient($pdns_config);

echo "Testing PowerDNS Admin connection...\n";

// Make a simple request with timeout
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://dnsadmin.avant.nl/api/v1/servers/1/zones');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-API-Key: ' . $pdns_config['api_key']
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $http_code\n";
if ($error) {
    echo "cURL Error: $error\n";
}

if ($response) {
    echo "Response length: " . strlen($response) . " bytes\n";
    echo "First 500 characters:\n";
    echo substr($response, 0, 500) . "\n";
    
    $json = json_decode($response, true);
    if ($json && is_array($json)) {
        echo "\nParsed as JSON array with " . count($json) . " items\n";
        if (count($json) > 0) {
            echo "Keys in first item: " . implode(', ', array_keys($json[0])) . "\n";
        }
    }
} else {
    echo "No response received\n";
}
?>
