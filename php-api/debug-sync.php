<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/autoloader.php';
require_once 'config/config.php';

// Get PDNSAdminClient
$pdns_client = new PDNSAdminClient(
    $pdns_config['base_url'],
    $pdns_config['username'],
    $pdns_config['password']
);

// Get missing domains list
$missing_domains = [
    'auto-sync-test-domain.com',
    'brand-new-template-test-2025.com',
    'brand-new-test-domain.nl',
    'testapi456.com',
    'final-template-test.com'
];

echo "Debug: Getting all domains from PowerDNS Admin...\n";
$pdns_response = $pdns_client->getAllDomainsWithAccounts();

if($pdns_response['status_code'] == 200) {
    $pdns_domains = $pdns_response['data'];
    echo "Total domains from PowerDNS Admin: " . count($pdns_domains) . "\n\n";
    
    // Check each missing domain
    foreach($missing_domains as $missing_domain) {
        echo "=== Checking $missing_domain ===\n";
        
        // Find this domain in PowerDNS Admin data
        $found_domain = null;
        foreach($pdns_domains as $pdns_domain) {
            if($pdns_domain['name'] === $missing_domain) {
                $found_domain = $pdns_domain;
                break;
            }
        }
        
        if($found_domain) {
            echo "✓ Found in PowerDNS Admin data:\n";
            print_r($found_domain);
            
            // Check if it exists in our local database
            $check_query = "SELECT id, name FROM domains WHERE name = ?";
            $check_stmt = $pdo->prepare($check_query);
            $check_stmt->bindParam(1, $missing_domain);
            $check_stmt->execute();
            $existing_domain = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if($existing_domain) {
                echo "✓ Exists in local database: ID " . $existing_domain['id'] . "\n";
            } else {
                echo "✗ NOT found in local database\n";
                
                // Try to create it manually
                echo "Attempting to create domain...\n";
                $domain_obj = new Domain($pdo);
                $domain_obj->name = $found_domain['name'];
                $domain_obj->type = $found_domain['type'] ?? 'Zone';
                $domain_obj->pdns_zone_id = $found_domain['id'];
                $domain_obj->kind = $found_domain['kind'] ?? 'Master';
                $domain_obj->masters = $found_domain['masters'] ?? '';
                $domain_obj->dnssec = $found_domain['dnssec'] ?? false;
                $domain_obj->account = $found_domain['account_name'] ?? '';
                $domain_obj->account_id = 1; // Default to admin account
                
                try {
                    if ($domain_obj->create()) {
                        echo "✓ Successfully created domain\n";
                    } else {
                        echo "✗ Failed to create domain\n";
                    }
                } catch (Exception $e) {
                    echo "✗ Exception creating domain: " . $e->getMessage() . "\n";
                }
            }
        } else {
            echo "✗ NOT found in PowerDNS Admin data\n";
        }
        
        echo "\n";
    }
} else {
    echo "Failed to get domains from PowerDNS Admin\n";
    print_r($pdns_response);
}
?>
