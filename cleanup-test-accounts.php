<?php
/**
 * Test Account Cleanup Script
 * 
 * This script safely deletes test accounts that were created during testing.
 * It identifies test accounts by common patterns and provides confirmation before deletion.
 */

// Set up the environment
$base_path = __DIR__ . '/php-api';

require_once $base_path . '/config/config.php';
require_once $base_path . '/config/database.php';
require_once $base_path . '/includes/database-compat.php';
require_once $base_path . '/models/Account.php';
require_once $base_path . '/classes/PDNSAdminClient.php';

echo "======================================\n";
echo "  Test Account Cleanup Script\n";
echo "======================================\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

// Get database connections
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get PowerDNS Admin database connection
    $pdns_admin_conn = null;
    if (class_exists('PDNSAdminDatabase')) {
        $pdns_admin_db = new PDNSAdminDatabase();
        $pdns_admin_conn = $pdns_admin_db->getConnection();
    }
    
    if (!$pdns_admin_conn) {
        echo "❌ ERROR: Cannot connect to PowerDNS Admin database\n";
        exit(1);
    }
    
    echo "✅ Database connections established\n\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Define test account patterns
$test_patterns = [
    'testuser%',           // testuser, testuser1, testuser123, etc.
    'test_%',             // test_user, test_account, etc.
    '%test%',             // anytest, testany, etc.
    'demo%',              // demo accounts
    'sample%',            // sample accounts
    'temp%',              // temporary accounts
    'example%',           // example accounts
];

// Protected accounts that should never be deleted
$protected_accounts = [
    'admin',
    'administrator', 
    'apiadmin',
    'root',
    'pdnsadmin'
];

echo "🔍 Scanning for test accounts...\n\n";

// Find test accounts in local database
$test_accounts = [];
$protected_found = [];

foreach ($test_patterns as $pattern) {
    $query = "SELECT id, username, firstname, lastname, email, pdns_account_id, created_at 
              FROM accounts 
              WHERE username LIKE ? 
              ORDER BY created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$pattern]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($results as $account) {
        // Check if it's a protected account
        if (in_array(strtolower($account['username']), array_map('strtolower', $protected_accounts))) {
            $protected_found[] = $account;
            continue;
        }
        
        $test_accounts[] = $account;
    }
}

// Remove duplicates
$test_accounts = array_values(array_unique($test_accounts, SORT_REGULAR));

echo "📊 Scan Results:\n";
echo "   Test accounts found: " . count($test_accounts) . "\n";
echo "   Protected accounts found (will NOT be deleted): " . count($protected_found) . "\n\n";

if (count($protected_found) > 0) {
    echo "🛡️  Protected accounts (SAFE from deletion):\n";
    foreach ($protected_found as $account) {
        echo "   - {$account['username']} ({$account['email']}) - Created: {$account['created_at']}\n";
    }
    echo "\n";
}

if (count($test_accounts) == 0) {
    echo "✅ No test accounts found to delete.\n";
    echo "Script completed at: " . date('Y-m-d H:i:s') . "\n";
    exit(0);
}

echo "🗑️  Test accounts that will be deleted:\n";
foreach ($test_accounts as $account) {
    $pdns_id = $account['pdns_account_id'] ? "PDNS ID: {$account['pdns_account_id']}" : "No PDNS ID";
    echo "   - {$account['username']} ({$account['email']}) - {$pdns_id} - Created: {$account['created_at']}\n";
}
echo "\n";

// Ask for confirmation
echo "⚠️  WARNING: This will permanently delete these accounts from both:\n";
echo "   1. PowerDNS Admin\n";
echo "   2. Local database\n\n";

echo "Are you sure you want to proceed? (yes/no): ";
$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if (strtolower($confirmation) !== 'yes') {
    echo "❌ Operation cancelled by user.\n";
    echo "Script completed at: " . date('Y-m-d H:i:s') . "\n";
    exit(0);
}

echo "\n🚀 Starting deletion process...\n\n";

// Initialize PowerDNS Admin client
$client = new PDNSAdminClient($pdns_config);
$account = new Account($db);

$success_count = 0;
$error_count = 0;
$errors = [];

foreach ($test_accounts as $test_account) {
    echo "Deleting account: {$test_account['username']}... ";
    
    try {
        // If account has PowerDNS Admin ID, delete from PowerDNS Admin first
        if ($test_account['pdns_account_id']) {
            $response = $client->deleteUser($test_account['pdns_account_id']);
            
            if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
                echo "✅ Deleted from PowerDNS Admin";
                $success_count++;
            } elseif ($response['status_code'] == 404) {
                echo "⚠️  Not found in PowerDNS Admin (already deleted)";
                $success_count++;
            } else {
                $error_msg = "PowerDNS Admin deletion failed (HTTP {$response['status_code']})";
                echo "❌ $error_msg";
                $errors[] = "{$test_account['username']}: $error_msg";
                $error_count++;
                continue;
            }
        } else {
            echo "⚠️  No PowerDNS Admin ID, deleting from local DB only";
            $success_count++;
        }
        
        // Delete from local database
        $delete_query = "DELETE FROM accounts WHERE id = ?";
        $delete_stmt = $db->prepare($delete_query);
        if ($delete_stmt->execute([$test_account['id']])) {
            echo " + local DB\n";
        } else {
            echo " - Failed to delete from local DB\n";
            $errors[] = "{$test_account['username']}: Failed to delete from local database";
            $error_count++;
        }
        
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
        $errors[] = "{$test_account['username']}: Exception - " . $e->getMessage();
        $error_count++;
    }
    
    // Small delay to avoid overwhelming the API
    usleep(100000); // 0.1 seconds
}

echo "\n======================================\n";
echo "  Cleanup Summary\n";
echo "======================================\n";
echo "Successfully deleted: $success_count accounts\n";
echo "Errors encountered: $error_count accounts\n";

if (count($errors) > 0) {
    echo "\n❌ Errors:\n";
    foreach ($errors as $error) {
        echo "   - $error\n";
    }
}

// Sync accounts from PowerDNS Admin to ensure consistency
echo "\n🔄 Syncing accounts from PowerDNS Admin to ensure database consistency...\n";

try {
    // Get all users from PowerDNS Admin database
    $users_query = "SELECT id, username, firstname, lastname, email, role_id FROM user";
    $stmt = $pdns_admin_conn->prepare($users_query);
    $stmt->execute();
    $pdns_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($pdns_users)) {
        $pdns_usernames = array_column($pdns_users, 'username');
        
        // Remove accounts that exist locally but not in PowerDNS Admin
        $placeholders = str_repeat('?,', count($pdns_usernames) - 1) . '?';
        $cleanup_query = "DELETE FROM accounts WHERE username NOT IN ($placeholders)";
        $cleanup_stmt = $db->prepare($cleanup_query);
        if ($cleanup_stmt->execute($pdns_usernames)) {
            $cleaned_count = $cleanup_stmt->rowCount();
            echo "✅ Sync completed: $cleaned_count orphaned local accounts removed\n";
        }
    }
    
} catch (Exception $e) {
    echo "⚠️  Warning: Sync failed: " . $e->getMessage() . "\n";
}

echo "\nScript completed at: " . date('Y-m-d H:i:s') . "\n";
echo "======================================\n";

?>
