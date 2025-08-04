<?php
require_once 'includes/autoloader.php';

echo "=== Raw Output of Zones Endpoints ===\n\n";

$client = new PDNSAdminClient();

// Test both possible endpoints
echo "1. Testing PowerDNS Server API: /servers/localhost/zones\n";
$response = $client->request('GET', '/servers/localhost/zones');

if ($response === null) {
    echo "Request failed or returned null\n\n";
} else {
    $httpCode = $client->getLastResponseCode();
    echo "HTTP Status: " . $httpCode . "\n";
    echo "Response Length: " . strlen($response) . " bytes\n\n";
    
    if ($httpCode === 200) {
        echo "=== RAW RESPONSE ===\n";
        echo $response;
        echo "\n=== END RAW RESPONSE ===\n\n";
        
        // Also try to decode as JSON to see structure
        $data = json_decode($response, true);
        if ($data !== null) {
            echo "=== JSON DECODED STRUCTURE (first 3 zones) ===\n";
            $zones = array_slice($data, 0, 3);
            print_r($zones);
            echo "=== END STRUCTURE ===\n\n";
            
            echo "Total zones found: " . count($data) . "\n\n";
            
            // Check for account_id in zones
            $zonesWithAccount = 0;
            $zonesWithoutAccount = 0;
            foreach ($data as $zone) {
                if (!empty($zone['account'])) {
                    $zonesWithAccount++;
                } else {
                    $zonesWithoutAccount++;
                }
            }
            echo "Zones with account_id: " . $zonesWithAccount . "\n";
            echo "Zones without account_id: " . $zonesWithoutAccount . "\n";
        }
    } else {
        echo "=== RAW RESPONSE ===\n";
        echo $response;
        echo "\n=== END RAW RESPONSE ===\n";
        echo "Request failed with HTTP " . $httpCode . "\n\n";
    }
}

echo "2. Testing PowerDNS Admin API: /api/v1/servers/localhost/zones\n";
$response2 = $client->request('GET', '/api/v1/servers/localhost/zones');

if ($response2 === null) {
    echo "Request failed or returned null\n";
} else {
    $httpCode2 = $client->getLastResponseCode();
    echo "HTTP Status: " . $httpCode2 . "\n";
    echo "Response Length: " . strlen($response2) . " bytes\n\n";
    
    if ($httpCode2 === 200) {
        echo "=== RAW RESPONSE ===\n";
        echo $response2;
        echo "\n=== END RAW RESPONSE ===\n\n";
        
        // Also try to decode as JSON to see structure
        $data2 = json_decode($response2, true);
        if ($data2 !== null) {
            echo "=== JSON DECODED STRUCTURE (first 3 zones) ===\n";
            $zones2 = array_slice($data2, 0, 3);
            print_r($zones2);
            echo "=== END STRUCTURE ===\n";
        }
    } else {
        echo "Request failed with HTTP " . $httpCode2 . "\n";
    }
}
?>
