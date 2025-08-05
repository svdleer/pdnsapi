<?php
// Test the domains sync functionality
$base_path = __DIR__;
require_once $base_path . '/config/config.php';
require_once $base_path . '/config/database.php';
require_once $base_path . '/models/Domain.php';
require_once $base_path . '/classes/PDNSAdminClient.php';

// Define constants if not defined in config
if (!defined('PDNS_ADMIN_URL')) {
    define('PDNS_ADMIN_URL', 'http://127.0.0.1:9191');
}
if (!defined('PDNS_ADMIN_API_KEY')) {
    define('PDNS_ADMIN_API_KEY', 'your-api-key-here');
}

// Initialize database
try {
    $database = new Database();
    $db = $database->getConnection();
    echo "Database connection successful\n";
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Initialize PDNSAdmin client
$pdns_config = [
    'base_url' => PDNS_ADMIN_URL,
    'api_key' => PDNS_ADMIN_API_KEY,
    'auth_type' => 'apikey'
];
$pdns_client = new PDNSAdminClient($pdns_config);

// Create domain object
$domain = new Domain($db);

echo "Testing domains sync...\n\n";

// Test the sync function manually
function syncDomainsFromPDNS($domain, $pdns_client) {
    global $db;
    
    // Get all domains from PDNSAdmin
    $pdns_response = $pdns_client->getAllDomains();
    
    if($pdns_response['status_code'] == 200) {
        $pdns_domains = $pdns_response['data'];
        $synced_count = 0;
        $updated_count = 0;
        
        echo "Found " . count($pdns_domains) . " domains in PowerDNS Admin\n\n";
        
        foreach($pdns_domains as $pdns_domain) {
            $domain_name = $pdns_domain['name'] ?? '';
            
            if (empty($domain_name)) {
                continue; // Skip domains without a name
            }
            
            echo "Processing domain: {$domain_name}\n";
            
            // Create a new domain object for each domain to avoid conflicts
            $domain_obj = new Domain($db);
            $domain_obj->name = $domain_name;
            
            // Check if domain exists using a specific method
            if ($domain_obj->readByName()) {
                echo "  - Domain exists, updating...\n";
                // Domain exists, update it
                $domain_obj->type = $pdns_domain['type'] ?? 'Zone';
                $domain_obj->pdns_zone_id = $pdns_domain['id'];
                $domain_obj->kind = $pdns_domain['kind'] ?? 'Master';
                $domain_obj->masters = isset($pdns_domain['masters']) ? implode(',', $pdns_domain['masters']) : '';
                $domain_obj->dnssec = $pdns_domain['dnssec'] ?? false;
                $domain_obj->account = $pdns_domain['account'] ?? '';
                
                try {
                    if ($domain_obj->update()) {
                        $updated_count++;
                        echo "  - Updated successfully\n";
                    } else {
                        echo "  - Update failed\n";
                    }
                } catch (Exception $e) {
                    echo "  - Update error: " . $e->getMessage() . "\n";
                    error_log("Failed to update domain {$domain_name}: " . $e->getMessage());
                }
            } else {
                echo "  - Domain doesn't exist, creating...\n";
                // Domain doesn't exist, create it
                $domain_obj->name = $domain_name;
                $domain_obj->type = $pdns_domain['type'] ?? 'Zone';
                $domain_obj->pdns_zone_id = $pdns_domain['id'];
                $domain_obj->kind = $pdns_domain['kind'] ?? 'Master';
                $domain_obj->masters = isset($pdns_domain['masters']) ? implode(',', $pdns_domain['masters']) : '';
                $domain_obj->dnssec = $pdns_domain['dnssec'] ?? false;
                $domain_obj->account = $pdns_domain['account'] ?? '';
                $domain_obj->account_id = null; // Will be set later when we implement account linking
                
                try {
                    if ($domain_obj->create()) {
                        $synced_count++;
                        echo "  - Created successfully\n";
                    } else {
                        echo "  - Create failed\n";
                    }
                } catch (Exception $e) {
                    echo "  - Create error: " . $e->getMessage() . "\n";
                    error_log("Failed to create domain {$domain_name}: " . $e->getMessage());
                    
                    // If it's a duplicate key error, try to update instead
                    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        echo "  - Duplicate key error, trying to update...\n";
                        if ($domain_obj->readByName()) {
                            $domain_obj->type = $pdns_domain['type'] ?? 'Zone';
                            $domain_obj->pdns_zone_id = $pdns_domain['id'];
                            $domain_obj->kind = $pdns_domain['kind'] ?? 'Master';
                            $domain_obj->masters = isset($pdns_domain['masters']) ? implode(',', $pdns_domain['masters']) : '';
                            $domain_obj->dnssec = $pdns_domain['dnssec'] ?? false;
                            $domain_obj->account = $pdns_domain['account'] ?? '';
                            
                            try {
                                if ($domain_obj->update()) {
                                    $updated_count++;
                                    echo "  - Updated successfully after duplicate error\n";
                                }
                            } catch (Exception $update_e) {
                                echo "  - Update after duplicate error failed: " . $update_e->getMessage() . "\n";
                                error_log("Failed to update domain {$domain_name} after duplicate error: " . $update_e->getMessage());
                            }
                        }
                    }
                }
            }
        }
        
        echo "\nSync completed:\n";
        echo "- Created: {$synced_count} domains\n";
        echo "- Updated: {$updated_count} domains\n";
        echo "- Total PowerDNS domains: " . count($pdns_domains) . "\n";
        
        return [
            'synced_count' => $synced_count, 
            'updated_count' => $updated_count,
            'total_pdns_domains' => count($pdns_domains)
        ];
    } else {
        echo "Failed to fetch domains from PDNSAdmin: " . $pdns_response['status_code'] . "\n";
        return false;
    }
}

// Run the sync
$result = syncDomainsFromPDNS($domain, $pdns_client);

if ($result) {
    echo "\nDomains sync test completed successfully!\n";
} else {
    echo "\nDomains sync test failed!\n";
}
?>
