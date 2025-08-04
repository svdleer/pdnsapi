<?php
require_once 'classes/PDNSAdminClient.php';
require_once 'config/config.php';
require_once 'config/database.php';

echo "=== User and Domain Analysis ===\n\n";

$client = new PDNSAdminClient($pdns_config);

// Get users
echo "1. Analyzing PowerDNS Admin users...\n";
$users_response = $client->getAllUsers();
if ($users_response['status_code'] == 200) {
    $users = $users_response['data'];
    echo "   ✅ Found " . count($users) . " users\n\n";
    
    echo "   👥 User details:\n";
    foreach ($users as $user) {
        $id = $user['id'] ?? 'N/A';
        $username = $user['username'] ?? 'N/A';
        $email = $user['email'] ?? 'N/A';
        $role = $user['role'] ?? 'N/A';
        $firstname = $user['firstname'] ?? '';
        $lastname = $user['lastname'] ?? '';
        $fullname = trim("$firstname $lastname");
        
        echo "      ID: $id | Username: $username | Email: $email | Role: $role";
        if ($fullname) echo " | Name: $fullname";
        echo "\n";
    }
} else {
    echo "   ❌ Failed to get users\n";
    exit(1);
}

// Get zones
echo "\n2. Getting zones from PowerDNS Admin...\n";
$zones_response = $client->getAllZones();
if ($zones_response['status_code'] == 200) {
    $zones = $zones_response['data'];
    echo "   ✅ Found " . count($zones) . " zones\n";
    
    // Show sample zones
    echo "   📋 Sample zones:\n";
    foreach (array_slice($zones, 0, 20) as $zone) {
        $id = $zone['id'] ?? 'N/A';
        $name = $zone['name'] ?? 'N/A';
        echo "      ID: $id | Name: $name\n";
    }
} else {
    echo "   ❌ Failed to get zones\n";
    exit(1);
}

// Database sync
echo "\n3. Syncing to database...\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Sync users
    echo "   👥 Syncing users...\n";
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
        $description = $fullname ?: "PowerDNS Admin User";
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
    
    // Sync zones
    echo "   🌐 Syncing zones...\n";
    $synced_zones = 0;
    
    foreach ($zones as $zone) {
        $stmt = $db->prepare("
            INSERT INTO domains (name, pdns_zone_id, updated_at) 
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                pdns_zone_id = VALUES(pdns_zone_id),
                updated_at = NOW()
        ");
        
        $zone_name = $zone['name'] ?? '';
        $zone_id = $zone['id'] ?? $zone_name;
        
        $stmt->execute([
            $zone_name,
            $zone_id
        ]);
        
        $synced_zones++;
    }
    
    echo "   ✅ Synced $synced_zones zones\n";
    
    // Show statistics
    echo "\n4. Database statistics...\n";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM accounts");
    $account_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   👥 Total accounts (users): $account_count\n";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM domains");
    $domain_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   🌐 Total domains: $domain_count\n";
    
    // Show accounts
    echo "\n   📋 Accounts in database:\n";
    $stmt = $db->query("SELECT name, description, mail FROM accounts LIMIT 10");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "      - {$row['name']} ({$row['description']}) - {$row['mail']}\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n=== Analysis Complete ===\n";
echo "Next: Create user-domain assignment system since PowerDNS Admin doesn't use accounts.\n";
?>
