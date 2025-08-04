<?php
// Debug script to test getting detailed domain information
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'classes/PDNSAdminClient.php';

// Initialize PDNSAdmin client
$pdns_client = new PDNSAdminClient($pdns_config);

echo "Testing detailed domain information from PowerDNS Admin...\n\n";

// First get the list of domains
$pdns_response = $pdns_client->getAllDomains();

if($pdns_response['status_code'] == 200) {
    $pdns_domains = $pdns_response['data'];
    
    echo "Found " . count($pdns_domains) . " domains total\n\n";
    
    // Get detailed info for the first few domains
    $count = 0;
    foreach($pdns_domains as $domain) {
        if ($count >= 3) break; // Only test first 3
        
        $domain_id = $domain['id'];
        $domain_name = $domain['name'];
        
        echo "Getting detailed info for domain: $domain_name (ID: $domain_id)\n";
        
        $detailed_response = $pdns_client->getDomain($domain_id);
        
        if ($detailed_response['status_code'] == 200) {
            echo "Detailed domain data:\n";
            echo json_encode($detailed_response['data'], JSON_PRETTY_PRINT) . "\n";
            
            $detailed_domain = $detailed_response['data'];
            echo "Account field: " . ($detailed_domain['account'] ?? 'NOT SET') . "\n";
            
            // Look for any account-related fields
            echo "Account-related fields:\n";
            foreach($detailed_domain as $key => $value) {
                if (stripos($key, 'account') !== false || stripos($key, 'user') !== false || stripos($key, 'owner') !== false) {
                    echo "  $key: " . json_encode($value) . "\n";
                }
            }
        } else {
            echo "Failed to get detailed info: " . $detailed_response['status_code'] . "\n";
            echo "Response: " . json_encode($detailed_response, JSON_PRETTY_PRINT) . "\n";
        }
        
        echo "\n" . str_repeat("=", 50) . "\n\n";
        $count++;
    }
    
} else {
    echo "Failed to fetch domains list: " . $pdns_response['status_code'] . "\n";
}
?>
