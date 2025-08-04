<?php
// Test script to verify domain-user linking from PowerDNS Admin
$base_path = __DIR__;
require_once $base_path . '/config/config.php';
require_once $base_path . '/config/database.php';
require_once $base_path . '/models/Domain.php';
require_once $base_path . '/models/Account.php';

// Initialize database
try {
    $database = new Database();
    $db = $database->getConnection();
    echo "Database connection successful\n\n";
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Testing domain-user relationships...\n\n";

// Check current users in the database
echo "=== Current Users ===\n";
$users_query = "SELECT id, name FROM users ORDER BY id";
$users_stmt = $db->prepare($users_query);
$users_stmt->execute();
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($users)) {
    echo "No users found in database\n";
} else {
    foreach ($users as $user) {
        echo "User ID {$user['id']}: {$user['name']}\n";
    }
}

echo "\n=== Current Domains ===\n";

// Check current domains and their user assignments
$domains_query = "
    SELECT d.id, d.name, d.account, d.account_id, u.name as user_name 
    FROM domains d 
    LEFT JOIN users u ON d.account_id = u.id 
    ORDER BY d.id
";
$domains_stmt = $db->prepare($domains_query);
$domains_stmt->execute();
$domains = $domains_stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($domains)) {
    echo "No domains found in database\n";
} else {
    foreach ($domains as $domain) {
        $account_info = $domain['account'] ?? 'No account';
        $user_link = $domain['account_id'] ? "Linked to User ID {$domain['account_id']} ({$domain['user_name']})" : "Not linked to user";
        echo "Domain ID {$domain['id']}: {$domain['name']}\n";
        echo "  Account: {$account_info}\n";
        echo "  User Link: {$user_link}\n\n";
    }
}

echo "=== Testing User Lookup ===\n";

// Test the user lookup functionality used in domain sync
function testUserLookup($db, $account_name) {
    echo "Looking up user: '{$account_name}'\n";
    $user_query = "SELECT id FROM users WHERE name = ? LIMIT 1";
    $user_stmt = $db->prepare($user_query);
    $user_stmt->bindParam(1, $account_name);
    $user_stmt->execute();
    $user_result = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_result) {
        echo "  Found: User ID {$user_result['id']}\n";
        return $user_result['id'];
    } else {
        echo "  Not found\n";
        return null;
    }
}

// Test with existing account names from domains
$account_names = array_unique(array_filter(array_column($domains, 'account')));
foreach ($account_names as $account_name) {
    testUserLookup($db, $account_name);
}

echo "\n=== Recommendations ===\n";

$domains_without_users = array_filter($domains, function($d) { return !empty($d['account']) && empty($d['account_id']); });
$domains_with_users = array_filter($domains, function($d) { return !empty($d['account_id']); });

echo "Domains with account but not linked to users: " . count($domains_without_users) . "\n";
echo "Domains properly linked to users: " . count($domains_with_users) . "\n";

if (!empty($domains_without_users)) {
    echo "\nDomains that need user linking:\n";
    foreach ($domains_without_users as $domain) {
        echo "- {$domain['name']} (account: {$domain['account']})\n";
    }
    echo "\nRun domain sync to automatically link these domains to users.\n";
}

echo "\nTest completed!\n";
?>
