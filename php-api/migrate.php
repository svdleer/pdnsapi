<?php
/**
 * Database Migration Script
 * Migrates from users table to accounts table structure
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/database-compat.php';

echo "=== PowerDNS Admin PHP API Database Migration ===\n";
echo "Starting migration at " . date('Y-m-d H:i:s') . "\n\n";

// Get database connection
try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Failed to connect to database");
    }
    
    echo "✓ Database connection established\n";
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Check if migration is needed
try {
    // Check if accounts table exists
    $check_accounts = $db->prepare("SHOW TABLES LIKE 'accounts'");
    $check_accounts->execute();
    $accounts_exists = $check_accounts->rowCount() > 0;
    
    // Check if users table exists
    $check_users = $db->prepare("SHOW TABLES LIKE 'users'");
    $check_users->execute();
    $users_exists = $check_users->rowCount() > 0;
    
    echo "Accounts table exists: " . ($accounts_exists ? 'YES' : 'NO') . "\n";
    echo "Users table exists: " . ($users_exists ? 'YES' : 'NO') . "\n\n";
    
    if ($accounts_exists && $users_exists) {
        echo "Both tables exist. Checking data consistency...\n";
        
        // Count records in both tables
        $users_count = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $accounts_count = $db->query("SELECT COUNT(*) FROM accounts")->fetchColumn();
        
        echo "Users table records: $users_count\n";
        echo "Accounts table records: $accounts_count\n";
        
        if ($users_count > $accounts_count) {
            echo "Users table has more records. Syncing data...\n";
            migrateUserData($db);
        } else {
            echo "Accounts table is up to date. No migration needed.\n";
        }
        
    } elseif ($users_exists && !$accounts_exists) {
        echo "Migration needed: Creating accounts table and migrating data...\n";
        createAccountsTable($db);
        migrateUserData($db);
        
    } elseif (!$users_exists && !$accounts_exists) {
        echo "No existing tables found. Creating accounts table...\n";
        createAccountsTable($db);
        
    } else {
        echo "Accounts table exists, users table doesn't. Migration already completed.\n";
    }
    
} catch (Exception $e) {
    echo "✗ Migration check failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== Migration completed successfully at " . date('Y-m-d H:i:s') . " ===\n";

function createAccountsTable($db) {
    echo "Creating accounts table...\n";
    
    $create_sql = "
    CREATE TABLE IF NOT EXISTS accounts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL UNIQUE,
        description TEXT,
        contact TEXT,
        mail VARCHAR(255),
        ip_addresses JSON,
        pdns_account_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_name (name),
        INDEX idx_pdns_account_id (pdns_account_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($db->exec($create_sql) !== false) {
        echo "✓ Accounts table created successfully\n";
    } else {
        throw new Exception("Failed to create accounts table");
    }
}

function migrateUserData($db) {
    echo "Migrating data from users to accounts table...\n";
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Get all users data
        $users_query = "SELECT * FROM users ORDER BY id";
        $users_stmt = $db->prepare($users_query);
        $users_stmt->execute();
        $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $migrated_count = 0;
        
        foreach ($users as $user) {
            // Check if account already exists
            $check_query = "SELECT id FROM accounts WHERE name = ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$user['name']]);
            
            if ($check_stmt->rowCount() == 0) {
                // Insert into accounts table
                $insert_query = "
                INSERT INTO accounts (name, description, contact, mail, ip_addresses, pdns_account_id, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                
                $insert_stmt = $db->prepare($insert_query);
                
                // Map old field names to new ones
                $pdns_account_id = isset($user['pdns_user_id']) ? $user['pdns_user_id'] : $user['pdns_account_id'];
                
                $result = $insert_stmt->execute([
                    $user['name'],
                    $user['description'],
                    $user['contact'],
                    $user['mail'],
                    $user['ip_addresses'],
                    $pdns_account_id,
                    $user['created_at'],
                    $user['updated_at']
                ]);
                
                if ($result) {
                    $migrated_count++;
                } else {
                    throw new Exception("Failed to migrate user: " . $user['name']);
                }
            }
        }
        
        // Commit transaction
        $db->commit();
        
        echo "✓ Successfully migrated $migrated_count users to accounts table\n";
        
    } catch (Exception $e) {
        $db->rollback();
        throw new Exception("Migration failed: " . $e->getMessage());
    }
}
?>
