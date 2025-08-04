<?php
echo "=== EXPLORING POWERDNS ADMIN DATABASE ===\n\n";

require_once 'config/database.php';

// Connect to PowerDNS Admin database
$pdns_admin_db = new PDNSAdminDatabase();
$conn = $pdns_admin_db->getConnection();

if (!$conn) {
    echo "❌ Failed to connect to PowerDNS Admin database\n";
    exit(1);
}

echo "✅ Connected to PowerDNS Admin database\n\n";

// 1. Show all tables
echo "1. TABLES IN POWERDNS ADMIN DATABASE:\n";
$tables_query = "SHOW TABLES";
$stmt = $conn->prepare($tables_query);
$stmt->execute();
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    echo "   - $table\n";
}
echo "\n";

// 2. Check if there's a domains table
if (in_array('domain', $tables)) {
    echo "2. DOMAIN TABLE STRUCTURE:\n";
    $desc_query = "DESCRIBE domain";
    $stmt = $conn->prepare($desc_query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "   {$column['Field']} ({$column['Type']}) - {$column['Null']}\n";
    }
    echo "\n";
    
    echo "3. SAMPLE DOMAINS WITH ACCOUNT INFO:\n";
    $sample_query = "SELECT id, name, account_id FROM domain WHERE account_id IS NOT NULL LIMIT 10";
    $stmt = $conn->prepare($sample_query);
    $stmt->execute();
    $sample_domains = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($sample_domains) > 0) {
        foreach ($sample_domains as $domain) {
            echo "   ID: {$domain['id']}, Name: {$domain['name']}, Account_ID: {$domain['account_id']}\n";
        }
    } else {
        echo "   No domains with account information found\n";
    }
    echo "\n";
    
    echo "4. DOMAIN STATISTICS:\n";
    $stats_query = "SELECT 
        COUNT(*) as total_domains,
        COUNT(CASE WHEN account_id IS NOT NULL THEN 1 END) as domains_with_account_id
        FROM domain";
    $stmt = $conn->prepare($stats_query);
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   Total domains: {$stats['total_domains']}\n";
    echo "   Domains with account_id: {$stats['domains_with_account_id']}\n";
    echo "\n";
}

// 3. Check if there's an account table
if (in_array('account', $tables)) {
    echo "5. ACCOUNT TABLE STRUCTURE:\n";
    $desc_query = "DESCRIBE account";
    $stmt = $conn->prepare($desc_query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "   {$column['Field']} ({$column['Type']}) - {$column['Null']}\n";
    }
    echo "\n";
    
    echo "6. SAMPLE ACCOUNTS:\n";
    $accounts_query = "SELECT id, name, description FROM account LIMIT 10";
    $stmt = $conn->prepare($accounts_query);
    $stmt->execute();
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($accounts as $account) {
        echo "   ID: {$account['id']}, Name: {$account['name']}, Description: " . ($account['description'] ?? 'None') . "\n";
    }
    echo "\n";
}

// 4. Check if there are user-related tables
$user_tables = array_filter($tables, function($table) {
    return strpos($table, 'user') !== false;
});

if (!empty($user_tables)) {
    echo "7. USER-RELATED TABLES:\n";
    foreach ($user_tables as $table) {
        echo "   - $table\n";
        
        // Get structure
        $desc_query = "DESCRIBE $table";
        $stmt = $conn->prepare($desc_query);
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $column) {
            echo "     {$column['Field']} ({$column['Type']})\n";
        }
        echo "\n";
    }
}

echo "=== EXPLORATION COMPLETE ===\n";
?>
