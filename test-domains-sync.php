<?php
/**
 * Test Domains Sync Function
 */

$base_path = __DIR__ . '/php-api';

require_once $base_path . '/config/config.php';
require_once $base_path . '/config/database.php';
require_once $base_path . '/includes/database-compat.php';
require_once $base_path . '/models/Domain.php';
require_once $base_path . '/classes/PDNSAdminClient.php';

echo "======================================\n";
echo "  Test Domains Sync Function\n";
echo "======================================\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize PDNSAdmin client
$pdns_client = new PDNSAdminClient($pdns_config);

// Initialize domain object
$domain = new Domain($db);

echo "🔍 Testing PowerDNS Admin API call...\n";

// Test the API call directly
$pdns_response = $pdns_client->makeRequest('/pdnsadmin/zones', 'GET');

echo "HTTP Code: " . $pdns_response['status_code'] . "\n";
echo "Data type: " . gettype($pdns_response['data']) . "\n";

if ($pdns_response['status_code'] == 200) {
    $pdns_domains = $pdns_response['data'];
    
    echo "✅ API call successful!\n";
    echo "Domains count: " . (is_array($pdns_domains) ? count($pdns_domains) : 'Not array') . "\n";
    
    if (is_array($pdns_domains) && count($pdns_domains) > 0) {
        echo "First domain: " . json_encode($pdns_domains[0]) . "\n";
        echo "Sample domain names: ";
        for ($i = 0; $i < min(5, count($pdns_domains)); $i++) {
            echo $pdns_domains[$i]['name'] ?? 'unnamed';
            if ($i < min(4, count($pdns_domains) - 1)) echo ", ";
        }
        echo "\n";
        
        echo "\n🚀 Testing sync logic...\n";
        
        // Test the sync logic manually
        $synced_count = 0;
        $updated_count = 0;
        
        // Just test the first few domains to avoid overwhelming output
        $test_domains = array_slice($pdns_domains, 0, 3);
        
        foreach($test_domains as $pdns_domain) {
            $domain_name = $pdns_domain['name'] ?? '';
            $pdns_zone_id = $pdns_domain['id'] ?? null;
            
            echo "Processing: $domain_name (ID: $pdns_zone_id)\n";
            
            if (empty($domain_name)) {
                echo "  ⚠️  Skipped - no name\n";
                continue;
            }
            
            // Check if domain exists
            $check_query = "SELECT id FROM domains WHERE name = ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$domain_name]);
            $existing_domain = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_domain) {
                echo "  ✅ Domain exists in local DB (ID: {$existing_domain['id']})\n";
                $updated_count++;
            } else {
                echo "  ➕ Domain would be created\n";
                $synced_count++;
            }
        }
        
        echo "\nSummary for first 3 domains:\n";
        echo "Would sync: $synced_count new domains\n";
        echo "Would update: $updated_count existing domains\n";
        
    } else {
        echo "❌ No domains found or invalid format\n";
    }
    
} else {
    echo "❌ API call failed\n";
    $error_msg = isset($pdns_response['data']['message']) ? $pdns_response['data']['message'] : 'Unknown error';
    echo "Error: $error_msg\n";
    echo "Raw response: " . substr($pdns_response['raw_response'], 0, 500) . "\n";
}

echo "\nTest completed at: " . date('Y-m-d H:i:s') . "\n";
?>
