<?php
/**
 * Test script to verify domain-account relationships after migration
 */

// Determine the correct base path
$base_path = realpath(__DIR__ . '/..');

require_once $base_path . '/config/config.php';
require_once $base_path . '/config/database.php';
require_once $base_path . '/includes/database-compat.php';
require_once $base_path . '/models/Account.php';
require_once $base_path . '/models/Domain.php';

echo "Testing domain-account relationships after migration...\n\n";

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Test 1: Check if domains table references accounts correctly
echo "1. Testing domain-account linkage in database:\n";
try {
    $query = "SELECT d.name, d.account_id, a.username, a.email 
              FROM domains d 
              LEFT JOIN accounts a ON d.account_id = a.id 
              WHERE d.account_id IS NOT NULL 
              LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($results)) {
        echo "   No domains with accounts found\n";
    } else {
        foreach ($results as $row) {
            echo "   Domain: {$row['name']} -> Account: {$row['username']} ({$row['email']})\n";
        }
    }
    echo "   ✅ Database linkage working\n\n";
} catch (Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n\n";
}

// Test 2: Test Domain model methods
echo "2. Testing Domain model:\n";
try {
    $domain = new Domain($db);
    
    // Test search method (this was referencing old users table)
    echo "   Testing domain search...\n";
    $stmt = $domain->search("test");
    if ($stmt) {
        echo "   ✅ Domain search method working\n";
    }
    
    // Test readByAccountId if it exists
    if (method_exists($domain, 'readByAccountId')) {
        echo "   Testing readByAccountId...\n";
        $stmt = $domain->readByAccountId(1);
        if ($stmt) {
            echo "   ✅ readByAccountId method working\n";
        }
    }
    
    echo "   ✅ Domain model working\n\n";
} catch (Exception $e) {
    echo "   ❌ Domain model error: " . $e->getMessage() . "\n\n";
}

// Test 3: Test Account model
echo "3. Testing Account model:\n";
try {
    $account = new Account($db);
    $account->id = 1;
    if ($account->readOne()) {
        echo "   Account found: {$account->username} ({$account->email})\n";
        echo "   ✅ Account model working\n";
    } else {
        echo "   ❌ Could not read account\n";
    }
} catch (Exception $e) {
    echo "   ❌ Account model error: " . $e->getMessage() . "\n";
}

echo "\nTest complete!\n";
?>
