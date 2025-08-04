<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

echo "Starting test...\n";

require_once "classes/PDNSAdminClient.php";
require_once "config/config.php";

echo "=== Domain Sync Test ===\n\n";

$client = new PDNSAdminClient($pdns_config);

echo "1. Testing connection to PowerDNS Admin...\n";
$response = $client->makeRequest("/servers", "GET");
if ($response["status_code"] == 200) {
    echo "   ✅ Connected successfully\n";
} else {
    echo "   ❌ Connection failed: HTTP " . $response["status_code"] . "\n";
    exit(1);
}

echo "\n2. Fetching domains...\n";
$domains_response = $client->getAllDomains();
if ($domains_response["status_code"] == 200) {
    $domains = $domains_response["data"];
    echo "   ✅ Found " . count($domains) . " domains\n";
} else {
    echo "   ❌ Failed to fetch domains: HTTP " . $domains_response["status_code"] . "\n";
    echo "   Error: " . $domains_response["raw_response"] . "\n";
}

echo "\n=== Test Complete ===\n";

