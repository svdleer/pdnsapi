<?php
/**
 * Test Script: Delete Account via API, then Sync to verify cleanup
 */

require_once 'includes/autoloader.php';
require_once 'config/database.php';
require_once 'config/config.php';

echo "=== Testing Delete → Sync Workflow ===\n\n";

// Configuration
$api_base = 'https://pdnsapi.avant.nl/api';
$test_username = 'test_delete_' . time();

// Step 1: Create a test account via API
echo "Step 1: Creating test account '$test_username'...\n";
$create_data = [
    'username' => $test_username,
    'plain_text_password' => 'TestPass123!',
    'firstname' => 'Test',
    'lastname' => 'Delete',
    'email' => $test_username . '@test.com',
    'role_id' => 2,
    'ip_addresses' => ['192.168.1.100'],
    'customer_id' => 123
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_base . '/accounts');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($create_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$create_response = curl_exec($ch);
$create_result = json_decode($create_response, true);

if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 201) {
    $account_id = $create_result['data']['id'];
    echo "✓ Account created successfully (ID: $account_id)\n\n";
} else {
    echo "✗ Failed to create account: " . $create_response . "\n";
    curl_close($ch);
    exit(1);
}

// Step 2: Verify account exists in local database
echo "Step 2: Checking account exists in local database...\n";
try {
    $stmt = $db->prepare("SELECT id, username FROM accounts WHERE username = ?");
    $stmt->execute([$test_username]);
    $local_account = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($local_account) {
        echo "✓ Account found in local database (ID: {$local_account['id']})\n\n";
    } else {
        echo "✗ Account not found in local database\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 3: Delete account via API
echo "Step 3: Deleting account via API (should only delete from PowerDNS Admin)...\n";
curl_setopt($ch, CURLOPT_URL, $api_base . '/accounts/' . $account_id);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_POSTFIELDS, '');

$delete_response = curl_exec($ch);
$delete_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($delete_code == 200) {
    echo "✓ Account deleted via API\n\n";
} else {
    echo "✗ Failed to delete account via API: " . $delete_response . "\n";
    curl_close($ch);
    exit(1);
}

// Step 4: Check if account still exists in local database
echo "Step 4: Checking if account still exists in local database (should still be there)...\n";
try {
    $stmt = $db->prepare("SELECT id, username FROM accounts WHERE username = ?");
    $stmt->execute([$test_username]);
    $local_account_after_delete = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($local_account_after_delete) {
        echo "✓ Account still exists in local database (as expected)\n\n";
    } else {
        echo "✗ Account was deleted from local database (unexpected!)\n";
        curl_close($ch);
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 5: Run sync to clean up local database
echo "Step 5: Running sync to clean up local database...\n";
curl_setopt($ch, CURLOPT_URL, $api_base . '/accounts/sync');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, '');

$sync_response = curl_exec($ch);
$sync_result = json_decode($sync_response, true);
$sync_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($sync_code == 200) {
    echo "✓ Sync completed successfully\n";
    echo "  - Synced: " . ($sync_result['data']['synced'] ?? 0) . " accounts\n";
    echo "  - Updated: " . ($sync_result['data']['updated'] ?? 0) . " accounts\n";
    echo "  - Deleted: " . ($sync_result['data']['deleted'] ?? 0) . " accounts\n";
    echo "  - Message: " . ($sync_result['message'] ?? 'No message') . "\n\n";
} else {
    echo "✗ Sync failed: " . $sync_response . "\n";
    curl_close($ch);
    exit(1);
}

curl_close($ch);

// Step 6: Verify account is now removed from local database
echo "Step 6: Checking if account was removed from local database...\n";
try {
    $stmt = $db->prepare("SELECT id, username FROM accounts WHERE username = ?");
    $stmt->execute([$test_username]);
    $local_account_after_sync = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$local_account_after_sync) {
        echo "✓ Account successfully removed from local database by sync\n\n";
    } else {
        echo "✗ Account still exists in local database after sync (unexpected!)\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "=== TEST COMPLETED SUCCESSFULLY ===\n";
echo "✓ DELETE via API only removes from PowerDNS Admin\n";
echo "✓ Account persists in local database after DELETE\n";
echo "✓ SYNC properly removes orphaned accounts from local database\n";
?>
