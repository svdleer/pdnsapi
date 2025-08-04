<?php
require_once 'classes/PDNSAdminClient.php';
require_once 'config/config.php';

echo "=== Testing /zones endpoint for account_id ===\n\n";

$client = new PDNSAdminClient($pdns_config);

// Test the /zones endpoint
echo "1. Testing /zones endpoint...\n";
$zones_response = $client->makeRequest('/zones', 'GET');

echo "   📊 HTTP " . $zones_response['status_code'] . " - " . strlen($zones_response['raw_response']) . " bytes\n";

if ($zones_response['status_code'] == 200) {
    $zones = $zones_response['data'];
    echo "   ✅ Found " . count($zones) . " zones\n";
    
    if (count($zones) > 0) {
        $sample_zone = $zones[0];
        echo "   🔍 Zone fields: " . implode(', ', array_keys($sample_zone)) . "\n";
        
        // Check for account_id
        if (isset($sample_zone['account_id'])) {
            echo "   ✅ account_id field found!\n";
            
            // Analyze account_id distribution
            $account_stats = [];
            $zones_with_account = 0;
            
            foreach ($zones as $zone) {
                if (isset($zone['account_id']) && $zone['account_id']) {
                    $account_id = $zone['account_id'];
                    if (!isset($account_stats[$account_id])) {
                        $account_stats[$account_id] = [
                            'count' => 0,
                            'account_name' => $zone['account'] ?? 'Unknown'
                        ];
                    }
                    $account_stats[$account_id]['count']++;
                    $zones_with_account++;
                }
            }
            
            echo "   📊 Zones with account_id: $zones_with_account\n";
            echo "   📊 Unique accounts: " . count($account_stats) . "\n";
            
            // Show account breakdown
            if (count($account_stats) > 0) {
                echo "\n   🏢 Account breakdown:\n";
                foreach (array_slice($account_stats, 0, 15) as $account_id => $stats) {
                    echo "      Account ID $account_id ({$stats['account_name']}): {$stats['count']} zones\n";
                }
            }
            
            // Show sample zones with account_id
            echo "\n   📋 Sample zones with account_id:\n";
            $shown = 0;
            foreach ($zones as $zone) {
                if (isset($zone['account_id']) && $zone['account_id'] && $shown < 10) {
                    $name = $zone['name'] ?? 'Unknown';
                    $account_id = $zone['account_id'];
                    $account_name = $zone['account'] ?? '';
                    echo "      $name -> Account ID: $account_id ($account_name)\n";
                    $shown++;
                }
            }
            
        } else {
            echo "   ❌ No account_id field found\n";
        }
        
        // Show first zone structure
        echo "\n   🔍 First zone structure:\n";
        foreach ($sample_zone as $key => $value) {
            $preview = is_array($value) ? '[array]' : (is_string($value) ? '"' . substr($value, 0, 50) . '"' : json_encode($value));
            echo "      $key: $preview\n";
        }
    }
    
} else {
    echo "   ❌ Failed to get /zones\n";
    echo "   📝 Response: " . $zones_response['raw_response'] . "\n";
}

// Compare with /pdnsadmin/zones
echo "\n2. Comparing with /pdnsadmin/zones...\n";
$pdns_zones_response = $client->makeRequest('/pdnsadmin/zones', 'GET');
echo "   📊 /pdnsadmin/zones: HTTP " . $pdns_zones_response['status_code'];
if ($pdns_zones_response['status_code'] == 200) {
    $pdns_zones = $pdns_zones_response['data'];
    echo " - " . count($pdns_zones) . " zones";
    if (count($pdns_zones) > 0) {
        echo " (fields: " . implode(', ', array_keys($pdns_zones[0])) . ")";
    }
}
echo "\n";

echo "\n=== Test Complete ===\n";
?>
