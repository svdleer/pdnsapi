<?php
require_once 'classes/PDNSAdminClient.php';
require_once 'config/config.php';
require_once 'config/database.php';

echo "=== Complete User-Domain Sync (with and without account_id) ===\n\n";

$client = new PDNSAdminClient($pdns_config);

echo "1. Getting users from PowerDNS Admin...\n";
$users_response = $client->getAllUsers();
if ($users_response['status_code'] == 200) {
    $users = $users_response['data'];
    echo "   ✅ Found " . count($users) . " users\n";
} else {
    echo "   ❌ Failed to get users\n";
    exit(1);
}

echo "\n2. Getting zones with account information...\n";
$zones_response = $client->makeRequest('/servers/localhost/zones', 'GET');
if ($zones_response['status_code'] == 200) {
    $zones = $zones_response['data'];
    echo "   ✅ Found " . count($zones) . " zones\n";
    
    // Analyze account distribution
    $zones_with_account = 0;
    $zones_without_account = 0;
    $unique_accounts = [];
    
    foreach ($zones as $zone) {
        $account = $zone['account'] ?? '';
        if (!empty($account)) {
            $zones_with_account++;
            if (!in_array($account, $unique_accounts)) {
                $unique_accounts[] = $account;
            }
        } else {
            $zones_without_account++;
        }
    }
    
    echo "   📊 Zones with account: $zones_with_account\n";
    echo "   📊 Zones without account: $zones_without_account\n";
    echo "   📊 Unique accounts found: " . count($unique_accounts) . "\n";
    
    if (count($unique_accounts) > 0) {
        echo "   🏢 Account names: " . implode(', ', array_slice($unique_accounts, 0, 10)) . "\n";
    }
} else {
    echo "   ❌ Failed to get zones\n";
    exit(1);
}

echo "\n3. Database sync...\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Sync users to accounts table
    echo "   👥 Syncing users to accounts...\n";
    $synced_users = 0;
    
    foreach ($users as $user) {
        $stmt = $db->prepare("
            INSERT INTO accounts (name, description, contact, mail, pdns_account_id, updated_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                description = VALUES(description),
                contact = VALUES(contact),
                mail = VALUES(mail),
                pdns_account_id = VALUES(pdns_account_id),
                updated_at = NOW()
        ");
        
        $username = $user['username'] ?? '';
        $firstname = $user['firstname'] ?? '';
        $lastname = $user['lastname'] ?? '';
        $fullname = trim("$firstname $lastname");
        $description = $fullname ?: "PowerDNS Admin User ({$user['role']})";
        $email = $user['email'] ?? '';
        $pdns_user_id = $user['id'] ?? null;
        
        $stmt->execute([
            $username,
            $description,
            $fullname,
            $email,
            $pdns_user_id
        ]);
        
        $synced_users++;
    }
    
    echo "   ✅ Synced $synced_users users\n";
    
    // Sync zones to domains table
    echo "   🌐 Syncing zones to domains...\n";
    $synced_zones = 0;
    $zones_with_account_id = 0;
    
    foreach ($zones as $zone) {
        $zone_name = $zone['name'] ?? $zone['id'];
        $account_name = $zone['account'] ?? '';
        $kind = $zone['kind'] ?? 'Master';
        $dnssec = isset($zone['dnssec']) ? ($zone['dnssec'] ? 1 : 0) : 0;
        
        // Generate account_id hash if account name exists
        $pdns_account_id = null;
        if (!empty($account_name)) {
            $pdns_account_id = hash('crc32', $account_name);
            $zones_with_account_id++;
        }
        
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
            $zone_name,
            $pdns_account_id,
            $kind,
            $dnssec,
            $account_name
        ]);
        
        $synced_zones++;
    }
    
    echo "   ✅ Synced $synced_zones zones\n";
    echo "   📊 Zones with account_id: $zones_with_account_id\n";
    
    // Create a user-domain assignment table for manual assignments
    echo "   🔧 Creating user-domain assignment table...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS user_domain_assignments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            domain_id INT NOT NULL,
            assigned_by VARCHAR(255),
            assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES accounts(id) ON DELETE CASCADE,
            FOREIGN KEY (domain_id) REFERENCES domains(id) ON DELETE CASCADE,
            UNIQUE KEY unique_assignment (user_id, domain_id),
            INDEX idx_user_id (user_id),
            INDEX idx_domain_id (domain_id)
        )
    ");
    echo "   ✅ User-domain assignment table ready\n";
    
    // Show database statistics
    echo "\n4. Database statistics...\n";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM accounts");
    $user_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM domains");
    $domain_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM domains WHERE pdns_account_id IS NOT NULL");
    $domains_with_account = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM domains WHERE pdns_account_id IS NULL");
    $domains_without_account = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "   👥 Users in database: $user_count\n";
    echo "   🌐 Total domains: $domain_count\n";
    echo "   🔗 Domains with account_id: $domains_with_account\n";
    echo "   🔄 Domains without account_id: $domains_without_account\n";
    
    // Show users
    echo "\n   👥 Users available for assignment:\n";
    $stmt = $db->query("SELECT id, name, description, mail FROM accounts ORDER BY name LIMIT 10");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "      ID {$row['id']}: {$row['name']} ({$row['description']}) - {$row['mail']}\n";
    }
    
    // Show sample unassigned domains
    echo "\n   🔄 Sample domains available for assignment:\n";
    $stmt = $db->query("
        SELECT id, name 
        FROM domains 
        WHERE pdns_account_id IS NULL 
        ORDER BY name 
        LIMIT 10
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "      ID {$row['id']}: {$row['name']}\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n=== Sync Complete ===\n";
echo "Summary:\n";
echo "- Users synced to 'accounts' table\n";
echo "- Domains synced to 'domains' table with account_id where available\n";
echo "- Created 'user_domain_assignments' table for manual user-domain relationships\n";
echo "\nNext steps:\n";
echo "1. Use the API to assign users to domains manually\n";
echo "2. Query user domains via account_id (automatic) or user_domain_assignments (manual)\n";
echo "3. Update API endpoints to return domains for specific users\n";
?>
