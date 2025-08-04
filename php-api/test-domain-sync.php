<?php
echo "=== DIRECT DOMAIN SYNC TEST ===\n\n";

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/database-compat.php';
require_once 'models/Domain.php';
require_once 'models/Account.php';
require_once 'classes/PDNSAdminClient.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Check current state
echo "1. CURRENT STATE:\n";
$count_query = "SELECT COUNT(*) as total, COUNT(account_id) as with_accounts FROM domains";
$stmt = $db->prepare($count_query);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Total domains: {$stats['total']}\n";
echo "Domains with accounts: {$stats['with_accounts']}\n\n";

echo "2. TESTING POWERDNS ADMIN CONNECTION:\n";
$pdns_client = new PDNSAdminClient($pdns_config);

// Test getting zones from PowerDNS Admin
$response = $pdns_client->getAllZones();
if ($response['status_code'] == 200) {
    $zones = $response['data'];
    echo "✅ Retrieved " . count($zones) . " zones from PowerDNS Admin\n";
    
    // Check if any zones have account info
    $zones_with_accounts = 0;
    foreach ($zones as $zone) {
        if (!empty($zone['account'])) {
            $zones_with_accounts++;
        }
    }
    echo "Zones with account info: $zones_with_accounts\n\n";
    
    if ($zones_with_accounts > 0) {
        echo "3. SAMPLE ZONES WITH ACCOUNTS:\n";
        $count = 0;
        foreach ($zones as $zone) {
            if (!empty($zone['account']) && $count < 5) {
                echo "   {$zone['name']} → {$zone['account']}\n";
                $count++;
            }
        }
        echo "\n";
    }
    
} else {
    echo "❌ Failed to connect to PowerDNS Admin: " . $response['status_code'] . "\n";
    if (isset($response['data']['message'])) {
        echo "   Error: " . $response['data']['message'] . "\n";
    }
}

echo "4. CHECKING ACCOUNTS TABLE:\n";
$accounts_query = "SELECT id, name FROM accounts LIMIT 10";
$stmt = $db->prepare($accounts_query);
$stmt->execute();
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Available accounts:\n";
foreach ($accounts as $account) {
    echo "   ID: {$account['id']}, Name: {$account['name']}\n";
}

echo "\n=== ANALYSIS COMPLETE ===\n";

// If we have zones with accounts, let's try to sync a few manually
if (isset($zones) && $zones_with_accounts > 0) {
    echo "\n5. MANUAL SYNC TEST:\n";
    
    $synced = 0;
    foreach ($zones as $zone) {
        if (!empty($zone['account']) && $synced < 3) {
            $domain_name = $zone['name'];
            $account_name = $zone['account'];
            
            echo "Syncing: $domain_name → $account_name\n";
            
            // Find the account ID
            $account_query = "SELECT id FROM accounts WHERE name = ?";
            $account_stmt = $db->prepare($account_query);
            $account_stmt->execute([$account_name]);
            $account_result = $account_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($account_result) {
                $account_id = $account_result['id'];
                
                // Update the domain
                $update_query = "UPDATE domains SET account_id = ?, account = ? WHERE name = ?";
                $update_stmt = $db->prepare($update_query);
                
                if ($update_stmt->execute([$account_id, $account_name, $domain_name])) {
                    echo "   ✅ Updated successfully\n";
                    $synced++;
                } else {
                    echo "   ❌ Update failed\n";
                }
            } else {
                echo "   ⚠️  Account '$account_name' not found in accounts table\n";
            }
        }
    }
    
    if ($synced > 0) {
        echo "\n6. VERIFICATION:\n";
        $verify_query = "SELECT d.name, d.account, a.name as account_name FROM domains d JOIN accounts a ON d.account_id = a.id LIMIT 5";
        $stmt = $db->prepare($verify_query);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Domains now linked to accounts:\n";
        foreach ($results as $result) {
            echo "   {$result['name']} → {$result['account_name']}\n";
        }
    }
}

echo "\n=== TEST COMPLETE ===\n";
?>

