<?php
require_once 'config/config.php';
require_once 'classes/PDNSAdminClient.php';

echo "=== Raw Output of Zones Endpoints ===\n\n";

$client = new PDNSAdminClient($pdns_config);

// Test both possible endpoints
echo "1. Testing PowerDNS Server API: /servers/localhost/zones\n";
$response = $client->makeRequest('/servers/localhost/zones', 'GET');

if ($response === null) {
    echo "Request failed or returned null\n\n";
} else {
    $httpCode = $response['status_code'];
    echo "HTTP Status: " . $httpCode . "\n";
    echo "Response Length: " . strlen($response['raw_response']) . " bytes\n\n";
    
    if ($httpCode === 200) {
        echo "=== RAW RESPONSE ===\n";
        echo $response['raw_response'];
        echo "\n=== END RAW RESPONSE ===\n\n";
        
        // Also show decoded structure
        $data = $response['data'];
        if ($data !== null && is_array($data)) {
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
        echo $response['raw_response'];
        echo "\n=== END RAW RESPONSE ===\n";
        echo "Request failed with HTTP " . $httpCode . "\n\n";
    }
}

echo "2. Testing PowerDNS Admin API: /api/v1/servers/localhost/zones\n";
$response2 = $client->makeRequest('/api/v1/servers/localhost/zones', 'GET');

if ($response2 === null) {
    echo "Request failed or returned null\n";
} else {
    $httpCode2 = $response2['status_code'];
    echo "HTTP Status: " . $httpCode2 . "\n";
    echo "Response Length: " . strlen($response2['raw_response']) . " bytes\n\n";
    
    if ($httpCode2 === 200) {
        echo "=== RAW RESPONSE ===\n";
        echo $response2['raw_response'];
        echo "\n=== END RAW RESPONSE ===\n\n";
        
        // Also show decoded structure
        $data2 = $response2['data'];
        if ($data2 !== null && is_array($data2)) {
            echo "=== JSON DECODED STRUCTURE (first 3 zones) ===\n";
            $zones2 = array_slice($data2, 0, 3);
            print_r($zones2);
            echo "=== END STRUCTURE ===\n";
        }
    } else {
        echo "Request failed with HTTP " . $httpCode2 . "\n";
    }
}

echo "\n3. Testing PowerDNS Admin Zones API: /pdnsadmin/zones\n";
$response3 = $client->makeRequest('/pdnsadmin/zones', 'GET');

if ($response3 === null) {
    echo "Request failed or returned null\n";
} else {
    $httpCode3 = $response3['status_code'];
    echo "HTTP Status: " . $httpCode3 . "\n";
    echo "Response Length: " . strlen($response3['raw_response']) . " bytes\n\n";
    
    if ($httpCode3 === 200) {
        echo "=== RAW RESPONSE ===\n";
        echo $response3['raw_response'];
        echo "\n=== END RAW RESPONSE ===\n\n";
        
        // Also show decoded structure
        $data3 = $response3['data'];
        if ($data3 !== null && is_array($data3)) {
            echo "=== JSON DECODED STRUCTURE (first 3 zones) ===\n";
            $zones3 = array_slice($data3, 0, 3);
            print_r($zones3);
            echo "=== END STRUCTURE ===\n";
        }
    } else {
        echo "Request failed with HTTP " . $httpCode3 . "\n";
    }
}
?>
