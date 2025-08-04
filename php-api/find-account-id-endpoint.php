<?php
require_once 'classes/PDNSAdminClient.php';
require_once 'config/config.php';

echo "=== Testing Various Zone Endpoints for account_id ===\n\n";

$client = new PDNSAdminClient($pdns_config);

// Test various zone endpoints
$endpoints_to_test = [
    '/zones',
    '/api/v1/zones', 
    '/pdnsadmin/zones',
    '/servers/localhost/zones',
    '/servers/localhost/zones?expand=true',
    '/servers/localhost/zones?expand=1',
];

foreach ($endpoints_to_test as $endpoint) {
    echo "Testing: $endpoint\n";
    
    $response = $client->makeRequest($endpoint, 'GET');
    $status_code = $response['status_code'];
    $raw_response = $response['raw_response'];
    
    echo "  📊 HTTP $status_code - " . strlen($raw_response) . " bytes\n";
    
    if ($status_code == 200) {
        $data = $response['data'];
        if (is_array($data) && count($data) > 0) {
            echo "  ✅ SUCCESS - " . count($data) . " zones\n";
            
            // Check first zone structure
            $first_zone = $data[0];
            if (is_array($first_zone)) {
                $fields = array_keys($first_zone);
                echo "  🔍 Fields: " . implode(', ', $fields) . "\n";
                
                // Check for account-related fields
                $account_fields = array_filter($fields, function($field) {
                    return stripos($field, 'account') !== false;
                });
                
                if (!empty($account_fields)) {
                    echo "  ✅ ACCOUNT FIELDS FOUND: " . implode(', ', $account_fields) . "\n";
                    
                    // Show sample with account info
                    foreach (array_slice($data, 0, 5) as $zone) {
                        $name = $zone['name'] ?? $zone['id'] ?? 'Unknown';
                        echo "    - $name";
                        foreach ($account_fields as $field) {
                            if (isset($zone[$field])) {
                                echo " | $field: " . json_encode($zone[$field]);
                            }
                        }
                        echo "\n";
                    }
                } else {
                    echo "  ❌ No account fields found\n";
                }
            }
        } else {
            echo "  📄 Empty or non-array response\n";
        }
    } else {
        echo "  ❌ FAILED\n";
        if ($status_code == 404) {
            echo "  📝 Not found\n";
        } else {
            echo "  📝 Error: " . substr($raw_response, 0, 200) . "...\n";
        }
    }
    
    echo "\n";
}

// Also check if we can get detailed zone information for a specific zone
echo "Testing detailed zone info for a specific zone...\n";
$zones_response = $client->makeRequest('/servers/localhost/zones', 'GET');
if ($zones_response['status_code'] == 200 && count($zones_response['data']) > 0) {
    $first_zone_name = $zones_response['data'][0]['name'] ?? $zones_response['data'][0]['id'];
    
    $detailed_endpoints = [
        "/servers/localhost/zones/$first_zone_name",
        "/pdnsadmin/zones/$first_zone_name",
    ];
    
    foreach ($detailed_endpoints as $endpoint) {
        echo "  Testing: $endpoint\n";
        $response = $client->makeRequest($endpoint, 'GET');
        echo "    📊 HTTP " . $response['status_code'] . "\n";
        
        if ($response['status_code'] == 200) {
            $data = $response['data'];
            if (is_array($data)) {
                $fields = array_keys($data);
                echo "    🔍 Fields: " . implode(', ', $fields) . "\n";
                
                // Check for account fields
                $account_fields = array_filter($fields, function($field) {
                    return stripos($field, 'account') !== false;
                });
                
                if (!empty($account_fields)) {
                    echo "    ✅ ACCOUNT FIELDS: " . implode(', ', $account_fields) . "\n";
                    foreach ($account_fields as $field) {
                        echo "      $field: " . json_encode($data[$field]) . "\n";
                    }
                }
            }
        }
        echo "\n";
    }
}

echo "=== Test Complete ===\n";
?>
