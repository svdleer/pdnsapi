<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/database-compat.php';

$database = new Database();
$db = $database->getConnection();

echo "=== DATA ANALYSIS ===\n\n";

// Check accounts table
echo "1. ACCOUNTS TABLE:\n";
$accounts_query = "SELECT id, name, mail FROM accounts LIMIT 5";
$stmt = $db->prepare($accounts_query);
$stmt->execute();
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Sample accounts:\n";
foreach ($accounts as $account) {
    echo "  ID: {$account['id']}, Name: {$account['name']}, Email: {$account['mail']}\n";
}

// Check users table
echo "\n2. USERS TABLE:\n";
$users_query = "SELECT id, username, email FROM users LIMIT 5";
try {
    $stmt = $db->prepare($users_query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Sample users:\n";
    foreach ($users as $user) {
        echo "  ID: {$user['id']}, Username: " . ($user['username'] ?? 'NULL') . ", Email: " . ($user['email'] ?? 'NULL') . "\n";
    }
} catch (Exception $e) {
    echo "Error reading users table: " . $e->getMessage() . "\n";
}

// Check domains with account_id
echo "\n3. DOMAINS WITH ACCOUNT_ID:\n";
$domains_query = "SELECT id, name, account_id, account FROM domains WHERE account_id IS NOT NULL LIMIT 10";
$stmt = $db->prepare($domains_query);
$stmt->execute();
$domains = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Domains with account_id:\n";
foreach ($domains as $domain) {
    echo "  {$domain['name']} -> account_id: {$domain['account_id']}, account: {$domain['account']}\n";
}

// Check if account_id values match existing accounts
echo "\n4. CHECKING ACCOUNT_ID REFERENCES:\n";
$check_query = "
    SELECT 
        d.name as domain_name,
        d.account_id,
        d.account as domain_account_name,
        a.name as actual_account_name
    FROM domains d
    LEFT JOIN accounts a ON d.account_id = a.id
    WHERE d.account_id IS NOT NULL
    LIMIT 10
";

$stmt = $db->prepare($check_query);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Account ID validation:\n";
foreach ($results as $result) {
    $match = ($result['domain_account_name'] === $result['actual_account_name']) ? "✅" : "❌";
    echo "  {$match} {$result['domain_name']} -> ID: {$result['account_id']}, Stored: '{$result['domain_account_name']}', Actual: '{$result['actual_account_name']}'\n";
}

echo "\n=== ANALYSIS COMPLETE ===\n";
?>
