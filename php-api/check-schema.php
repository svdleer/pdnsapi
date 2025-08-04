<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/database-compat.php';

$database = new Database();
$db = $database->getConnection();

echo "=== DATABASE SCHEMA CHECK ===\n\n";

// Show tables
$tables_query = "SHOW TABLES";
$stmt = $db->prepare($tables_query);
$stmt->execute();
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Available tables:\n";
foreach ($tables as $table) {
    echo "  - $table\n";
}

echo "\n=== CHECKING DOMAIN MODEL QUERY ===\n";

// Test the problematic query from Domain model
$test_query = "SELECT d.*, a.name as account_name 
                FROM domains d
                LEFT JOIN users a ON d.account_id = a.id
                ORDER BY d.created_at DESC
                LIMIT 5";

echo "Testing Domain model query (with 'users' table):\n";
try {
    $stmt = $db->prepare($test_query);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ Query executed successfully, " . count($results) . " results\n";
    
    if (count($results) > 0) {
        $sample = $results[0];
        echo "Sample record: " . $sample['name'] . " (account: " . ($sample['account_name'] ?? 'NULL') . ")\n";
    }
} catch (Exception $e) {
    echo "❌ Query failed: " . $e->getMessage() . "\n";
}

echo "\n=== TESTING CORRECTED QUERY ===\n";

// Test with correct table name
$corrected_query = "SELECT d.*, a.name as account_name 
                FROM domains d
                LEFT JOIN accounts a ON d.account_id = a.id
                ORDER BY d.created_at DESC
                LIMIT 5";

echo "Testing corrected query (with 'accounts' table):\n";
try {
    $stmt = $db->prepare($corrected_query);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ Query executed successfully, " . count($results) . " results\n";
    
    if (count($results) > 0) {
        echo "Sample records:\n";
        foreach (array_slice($results, 0, 3) as $record) {
            echo "  - " . $record['name'] . " (account: " . ($record['account_name'] ?? 'NULL') . ")\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Query failed: " . $e->getMessage() . "\n";
}

echo "\n=== SCHEMA CHECK ===\n";
?>
