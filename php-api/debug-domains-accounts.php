<?php
// Debug script to see the actual PowerDNS Admin domains response
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'classes/PDNSAdminClient.php';

// Initialize PDNSAdmin client
$pdns_client = new PDNSAdminClient($pdns_config);

echo "Fetching domains from PowerDNS Admin to debug account linking...\n\n";

// Get all domains from PDNSAdmin
$pdns_response = $pdns_client->getAllDomains();

if($pdns_response['status_code'] == 200) {
    $pdns_domains = $pdns_response['data'];
    
    echo "Found " . count($pdns_domains) . " domains in PowerDNS Admin\n\n";
    
    // Show the first few domains with full structure
    $count = 0;
    foreach($pdns_domains as $pdns_domain) {
        if ($count >= 3) break; // Only show first 3 for debugging
        
        echo "Domain " . ($count + 1) . ":\n";
        echo "Raw domain data: " . json_encode($pdns_domain, JSON_PRETTY_PRINT) . "\n";
        echo "---\n";
        echo "Domain name: " . ($pdns_domain['name'] ?? 'NOT SET') . "\n";
        echo "Account field: " . ($pdns_domain['account'] ?? 'NOT SET') . "\n";
        echo "ID: " . ($pdns_domain['id'] ?? 'NOT SET') . "\n";
        echo "Kind: " . ($pdns_domain['kind'] ?? 'NOT SET') . "\n";
        echo "Type: " . ($pdns_domain['type'] ?? 'NOT SET') . "\n";
        
        // Check for other possible account fields
        echo "Other fields that might contain account info:\n";
        foreach($pdns_domain as $key => $value) {
            if (stripos($key, 'account') !== false || stripos($key, 'user') !== false || stripos($key, 'owner') !== false) {
                echo "  $key: " . json_encode($value) . "\n";
            }
        }
        
        echo "\n=====================================\n\n";
        $count++;
    }
    
    // Show summary of all account values
    echo "Summary of account values across all domains:\n";
    $account_values = [];
    foreach($pdns_domains as $domain) {
        $account = $domain['account'] ?? 'EMPTY';
        if (!isset($account_values[$account])) {
            $account_values[$account] = 0;
        }
        $account_values[$account]++;
    }
    
    foreach($account_values as $account => $count) {
        echo "  '$account': $count domains\n";
    }
    
} else {
    echo "Failed to fetch domains from PDNSAdmin: " . $pdns_response['status_code'] . "\n";
    echo "Response: " . json_encode($pdns_response, JSON_PRETTY_PRINT) . "\n";
}
?>
