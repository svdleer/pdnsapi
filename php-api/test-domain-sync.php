<?php
// Test domain sync functionality directly
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'classes/PDNSAdminClient.php';
require_once 'models/Domain.php';

echo "=== Testing Domain Sync Functionality ===\n";

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    echo "✓ Database connection successful\n";
    
    // Initialize PowerDNS Admin client
    $pdns_client = new PDNSAdminClient($pdns_config);
    echo "✓ PowerDNS Admin client initialized\n";
    
    // Test PowerDNS Admin API connection
    $response = $pdns_client->getAllDomains();
    if ($response['status_code'] !== 200) {
        throw new Exception("PowerDNS Admin API error: " . json_encode($response['data']));
    }
    
    $all_domains = $response['data'];
    echo "✓ Retrieved " . count($all_domains) . " domains from PowerDNS Admin\n";
    
    // Test with first 3 domains
    $test_domains = array_slice($all_domains, 0, 3);
    
    $synced_count = 0;
    $updated_count = 0;
    
    // Start transaction
    $db->beginTransaction();
    
    foreach ($test_domains as $pdns_domain) {
        $domain_name = $pdns_domain['name'];
        $pdns_zone_id = $pdns_domain['id'];
        
        echo "Processing domain: $domain_name (zone_id: $pdns_zone_id)\n";
        
        // Check if domain exists
        $check_stmt = $db->prepare("SELECT id FROM domains WHERE name = ?");
        $check_stmt->execute([$domain_name]);
        $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update existing domain
            $update_stmt = $db->prepare("UPDATE domains SET pdns_zone_id = ?, updated_at = NOW() WHERE id = ?");
            if ($update_stmt->execute([$pdns_zone_id, $existing['id']])) {
                $updated_count++;
                echo "  ✓ Updated existing domain: $domain_name\n";
            } else {
                echo "  ✗ Failed to update domain: $domain_name\n";
            }
        } else {
            // Create new domain
            $insert_stmt = $db->prepare("
                INSERT INTO domains (name, type, pdns_zone_id, kind, masters, dnssec, account, account_id, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            if ($insert_stmt->execute([
                $domain_name, 
                'Zone', 
                $pdns_zone_id, 
                'Master', 
                '', 
                false, 
                '', 
                null
            ])) {
                $synced_count++;
                echo "  ✓ Created new domain: $domain_name\n";
            } else {
                echo "  ✗ Failed to create domain: $domain_name\n";
            }
        }
    }
    
    // Commit transaction
    $db->commit();
    
    echo "\n=== Sync Results ===\n";
    echo "Domains created: $synced_count\n";
    echo "Domains updated: $updated_count\n";
    echo "Total processed: " . count($test_domains) . "\n";
    echo "✓ Domain sync test completed successfully!\n";
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollback();
    }
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
