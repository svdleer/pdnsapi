<?php
require_once 'classes/PDNSAdminClient.php';
require_once 'config/config.php';
require_once 'config/database.php';

echo "=== PowerDNS Admin Full Sync with Account ID ===\n\n";

$client = new PDNSAdminClient($pdns_config);

// Step 1: Test if we can access PowerDNS Admin endpoints
echo "1. Testing PowerDNS Admin connectivity...\n";

// Try to get zones with account information from PowerDNS Admin
$zones_response = $client->getAllZones();
if ($zones_response['status_code'] == 200) {
    $zones = $zones_response['data'];
    echo "   ✅ PowerDNS Admin zones: " . count($zones) . " zones found\n";
    
    // Analyze zone structure for account_id
    if (count($zones) > 0) {
        $sample_zone = $zones[0];
        echo "   🔍 Zone fields: " . implode(', ', array_keys($sample_zone)) . "\n";
        
        // Check for account_id field
        if (isset($sample_zone['account_id'])) {
            echo "   ✅ account_id field found in zones\n";
        } else {
            echo "   ❌ account_id field NOT found in zones\n";
        }
    }
} else {
    echo "   ❌ Failed to get PowerDNS Admin zones: HTTP " . $zones_response['status_code'] . "\n";
    echo "   📝 Response: " . $zones_response['raw_response'] . "\n";
    
    // Fallback to regular domains endpoint
    echo "\n   🔄 Falling back to regular domains endpoint...\n";
    $domains_response = $client->getAllDomains();
    if ($domains_response['status_code'] == 200) {
        $domains = $domains_response['data'];
        echo "   ✅ Regular domains: " . count($domains) . " domains found\n";
        
        // Use domains data for now
        $zones = $domains;
        
        // Check structure
        if (count($zones) > 0) {
            $sample_zone = $zones[0];
            echo "   🔍 Domain fields: " . implode(', ', array_keys($sample_zone)) . "\n";
        }
    } else {
        echo "   ❌ Both endpoints failed. Cannot proceed.\n";
        exit(1);
    }
}

// Step 2: Get users and accounts if possible
echo "\n2. Attempting to get users and accounts...\n";

$users_response = $client->getAllUsers();
if ($users_response['status_code'] == 200) {
    $users = $users_response['data'];
    echo "   ✅ Users found: " . count($users) . "\n";
} else {
    echo "   ❌ Cannot get users: HTTP " . $users_response['status_code'] . "\n";
    $users = [];
}

$accounts_response = $client->getAllAccounts();
if ($accounts_response['status_code'] == 200) {
    $accounts = $accounts_response['data'];
    echo "   ✅ Accounts found: " . count($accounts) . "\n";
} else {
    echo "   ❌ Cannot get accounts: HTTP " . $accounts_response['status_code'] . "\n";
    $accounts = [];
}

// Step 3: Analyze account relationships
echo "\n3. Analyzing account relationships in zones...\n";

$account_stats = [];
$zones_with_account = 0;
$zones_without_account = 0;

foreach ($zones as $zone) {
    $zone_name = $zone['name'] ?? $zone['id'] ?? 'Unknown';
    
    if (isset($zone['account_id']) && $zone['account_id']) {
        $account_id = $zone['account_id'];
        if (!isset($account_stats[$account_id])) {
            $account_stats[$account_id] = [
                'account_id' => $account_id,
                'account_name' => $zone['account'] ?? 'Unknown',
                'zones' => []
            ];
        }
        $account_stats[$account_id]['zones'][] = $zone_name;
        $zones_with_account++;
    } else {
        $zones_without_account++;
    }
}

echo "   📊 Zones with account_id: $zones_with_account\n";
echo "   📊 Zones without account_id: $zones_without_account\n";
echo "   📊 Unique accounts found: " . count($account_stats) . "\n";

if (count($account_stats) > 0) {
    echo "\n   🏢 Account breakdown:\n";
    foreach (array_slice($account_stats, 0, 10) as $account_id => $stats) {
        $zone_count = count($stats['zones']);
        $account_name = $stats['account_name'];
        echo "      Account ID $account_id ($account_name): $zone_count zones\n";
    }
    
    if (count($account_stats) > 10) {
        echo "      ... and " . (count($account_stats) - 10) . " more accounts\n";
    }
}

// Step 4: Database operations (if we have a working connection)
echo "\n4. Database operations...\n";

try {
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Apply migration first
    echo "   🔧 Applying database migration...\n";
    $migration_sql = file_get_contents('database/migration_add_pdns_account_id.sql');
    $db->exec($migration_sql);
    echo "   ✅ Migration applied\n";
    
    // Sync zones to database
    echo "   💾 Syncing zones to database...\n";
    $synced_zones = 0;
    
    foreach (array_slice($zones, 0, 50) as $zone) { // Limit to first 50 for testing
        $zone_name = $zone['name'] ?? $zone['id'];
        $pdns_account_id = $zone['account_id'] ?? null;
        $account_name = $zone['account'] ?? '';
        $kind = $zone['kind'] ?? 'Master';
        $dnssec = isset($zone['dnssec']) ? ($zone['dnssec'] ? 1 : 0) : 0;
        
        // Insert or update domain
        $stmt = $db->prepare("
            INSERT INTO domains (name, pdns_zone_id, pdns_account_id, kind, dnssec, account, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                pdns_account_id = VALUES(pdns_account_id),
                kind = VALUES(kind),
                dnssec = VALUES(dnssec),
                account = VALUES(account),
                updated_at = NOW()
        ");
        
        $stmt->execute([
            $zone_name,
            $zone_name, // Using zone name as pdns_zone_id for now
            $pdns_account_id,
            $kind,
            $dnssec,
            $account_name
        ]);
        
        $synced_zones++;
    }
    
    echo "   ✅ Synced $synced_zones zones to database\n";
    
} catch (Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n=== Sync Complete ===\n";
echo "Next steps:\n";
echo "1. Verify account relationships in database\n";
echo "2. Connect users to accounts via account_id\n";
echo "3. Update API endpoints to use account_id for user-domain relationships\n";
?>
