<?php
require_once 'classes/PDNSAdminClient.php';
require_once 'config/config.php';
require_once 'config/database.php';

echo "=== Domain Sync with Available Data ===\n\n";

$client = new PDNSAdminClient($pdns_config);

// Get domains we can access
echo "1. Getting domains from PowerDNS Admin...\n";
$domains_response = $client->getAllDomains();

if ($domains_response['status_code'] == 200) {
    $domains = $domains_response['data'];
    echo "   ✅ Found " . count($domains) . " domains\n";
    
    // Analyze domain structure
    if (count($domains) > 0) {
        $sample_domain = $domains[0];
        echo "   🔍 Available fields: " . implode(', ', array_keys($sample_domain)) . "\n";
        
        // Show sample domains
        echo "   📋 Sample domains:\n";
        foreach (array_slice($domains, 0, 10) as $i => $domain) {
            $name = $domain['name'] ?? 'Unknown';
            $account = $domain['account'] ?? '';
            $kind = $domain['kind'] ?? 'Unknown';
            echo "      " . ($i + 1) . ". $name (Account: '$account', Kind: $kind)\n";
        }
    }
} else {
    echo "   ❌ Failed to get domains: HTTP " . $domains_response['status_code'] . "\n";
    exit(1);
}

// Database operations
echo "\n2. Database sync...\n";

try {
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Apply migration to add pdns_account_id column
    echo "   🔧 Applying database migration...\n";
    try {
        $migration_sql = file_get_contents('database/migration_add_pdns_account_id.sql');
        $db->exec($migration_sql);
        echo "   ✅ Migration applied successfully\n";
    } catch (Exception $e) {
        // Column might already exist
        echo "   ℹ️  Migration note: " . $e->getMessage() . "\n";
    }
    
    // Sync domains to database
    echo "   💾 Syncing domains to database...\n";
    $synced = 0;
    $updated = 0;
    $errors = 0;
    
    foreach ($domains as $domain) {
        try {
            $zone_name = $domain['name'] ?? $domain['id'];
            $account_name = $domain['account'] ?? '';
            $kind = $domain['kind'] ?? 'Master';
            $dnssec = isset($domain['dnssec']) ? ($domain['dnssec'] ? 1 : 0) : 0;
            $serial = $domain['serial'] ?? 0;
            
            // Create a hash of the account name to use as account_id
            // This gives us a consistent way to group domains by account
            $account_hash = !empty($account_name) ? hash('crc32', $account_name) : null;
            
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
            
            $result = $stmt->execute([
                $zone_name,
                $zone_name, // Using zone name as pdns_zone_id
                $account_hash, // Using hash of account name as account_id
                $kind,
                $dnssec,
                $account_name
            ]);
            
            if ($result) {
                if ($stmt->rowCount() > 0) {
                    $synced++;
                } else {
                    $updated++;
                }
            }
            
        } catch (Exception $e) {
            $errors++;
            if ($errors <= 5) {
                echo "   ⚠️  Error syncing $zone_name: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "   ✅ Sync complete:\n";
    echo "     - New domains: $synced\n";
    echo "     - Updated domains: $updated\n";
    echo "     - Errors: $errors\n";
    
    // Show account statistics
    echo "\n3. Account analysis...\n";
    $stmt = $db->prepare("
        SELECT pdns_account_id, account, COUNT(*) as domain_count 
        FROM domains 
        WHERE pdns_account_id IS NOT NULL 
        GROUP BY pdns_account_id, account 
        ORDER BY domain_count DESC 
        LIMIT 20
    ");
    $stmt->execute();
    $account_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($account_stats) > 0) {
        echo "   📊 Top accounts by domain count:\n";
        foreach ($account_stats as $stat) {
            $account_id = $stat['pdns_account_id'];
            $account_name = $stat['account'] ?: 'Unknown';
            $count = $stat['domain_count'];
            echo "      Account ID $account_id ($account_name): $count domains\n";
        }
    }
    
    // Show domains without account
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM domains WHERE pdns_account_id IS NULL OR account = ''");
    $stmt->execute();
    $no_account_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "   📊 Domains without account: $no_account_count\n";
    
} catch (Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n=== Sync Complete ===\n";
echo "Now you can use pdns_account_id to connect users and domains!\n";
?>
