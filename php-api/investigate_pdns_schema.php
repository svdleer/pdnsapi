<?php
/**
 * Investigate PowerDNS Admin Database Schema
 */

echo "Investigating PowerDNS Admin Database Schema\n";
echo "===========================================\n\n";

require_once __DIR__ . '/includes/env-loader.php';
require_once __DIR__ . '/includes/autoloader.php';

try {
    // Connect to PowerDNS Admin database
    $pdns_db = new PDNSAdminDatabase();
    $conn = $pdns_db->getConnection();
    
    if (!$conn) {
        echo "❌ Failed to connect to PowerDNS Admin database\n";
        exit;
    }
    
    echo "✅ Connected to PowerDNS Admin database successfully\n\n";
    
    // List all tables
    echo "1. Available tables in PowerDNS Admin database:\n";
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        echo "   - {$table}\n";
    }
    
    echo "\n2. Checking for domain-related tables:\n";
    
    // Check if there's a different domains table or similar
    $domain_tables = array_filter($tables, function($table) {
        return stripos($table, 'domain') !== false || stripos($table, 'zone') !== false;
    });
    
    if ($domain_tables) {
        foreach ($domain_tables as $table) {
            echo "   Found domain/zone table: {$table}\n";
            
            // Show structure of this table
            $stmt = $conn->query("DESCRIBE {$table}");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "     Columns:\n";
            foreach ($columns as $col) {
                echo "       - {$col['Field']} ({$col['Type']})\n";
            }
            
            // Show sample data
            $stmt = $conn->query("SELECT * FROM {$table} LIMIT 3");
            $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($samples) {
                echo "     Sample data:\n";
                foreach ($samples as $i => $sample) {
                    echo "       Row " . ($i + 1) . ":\n";
                    foreach ($sample as $key => $value) {
                        echo "         {$key}: " . (is_null($value) ? 'NULL' : $value) . "\n";
                    }
                    echo "\n";
                }
            } else {
                echo "     No data in table\n";
            }
            echo "\n";
        }
    } else {
        echo "   No domain/zone specific tables found\n";
    }
    
    echo "3. Checking for account-related tables:\n";
    
    $account_tables = array_filter($tables, function($table) {
        return stripos($table, 'account') !== false || stripos($table, 'user') !== false;
    });
    
    if ($account_tables) {
        foreach ($account_tables as $table) {
            echo "   Found account/user table: {$table}\n";
            
            // Show structure
            $stmt = $conn->query("DESCRIBE {$table}");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "     Columns:\n";
            foreach ($columns as $col) {
                echo "       - {$col['Field']} ({$col['Type']})\n";
            }
            
            // Count records
            $stmt = $conn->query("SELECT COUNT(*) as count FROM {$table}");
            $count = $stmt->fetch()['count'];
            echo "     Record count: {$count}\n\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "Investigation completed at " . date('Y-m-d H:i:s') . "\n";
?>
