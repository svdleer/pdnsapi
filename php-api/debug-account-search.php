<?php
/**
 * Debug Account Search Issues
 * Tests different ways of searching for accounts to identify the multiple results issue
 */

require_once 'includes/autoloader.php';
require_once 'config/config.php';
require_once 'models/Account.php';

echo "=== Debug Account Search ===\n\n";

$account = new Account($db);

// Test 1: Check if there are duplicate accounts in the database
echo "1. Checking for duplicate accounts...\n";
$query = "SELECT username, COUNT(*) as count FROM accounts GROUP BY username HAVING COUNT(*) > 1";
$stmt = $db->prepare($query);
$stmt->execute();
$duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($duplicates)) {
    echo "   ⚠️  Found duplicate usernames:\n";
    foreach ($duplicates as $duplicate) {
        echo "   - Username: {$duplicate['username']} (Count: {$duplicate['count']})\n";
    }
} else {
    echo "   ✓ No duplicate usernames found\n";
}

// Test 2: Check if there are duplicate IDs (should be impossible with AUTO_INCREMENT)
echo "\n2. Checking for duplicate IDs...\n";
$query = "SELECT id, COUNT(*) as count FROM accounts GROUP BY id HAVING COUNT(*) > 1";
$stmt = $db->prepare($query);
$stmt->execute();
$duplicate_ids = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($duplicate_ids)) {
    echo "   ⚠️  Found duplicate IDs (this should not happen):\n";
    foreach ($duplicate_ids as $duplicate) {
        echo "   - ID: {$duplicate['id']} (Count: {$duplicate['count']})\n";
    }
} else {
    echo "   ✓ No duplicate IDs found\n";
}

// Test 3: Show sample of accounts to identify patterns
echo "\n3. Sample accounts:\n";
$query = "SELECT id, username, firstname, lastname, email FROM accounts ORDER BY id LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$samples = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($samples as $sample) {
    echo "   ID: {$sample['id']}, Username: {$sample['username']}, Name: {$sample['firstname']} {$sample['lastname']}\n";
}

// Test 4: Test readOne method with a specific ID
echo "\n4. Testing readOne method...\n";
if (!empty($samples)) {
    $test_id = $samples[0]['id'];
    echo "   Testing with ID: $test_id\n";
    
    $account->id = $test_id;
    if ($account->readOne()) {
        echo "   ✓ readOne() returned: ID={$account->id}, Username={$account->username}\n";
    } else {
        echo "   ❌ readOne() failed to find account\n";
    }
}

echo "\n=== Debug Complete ===\n";
?>
