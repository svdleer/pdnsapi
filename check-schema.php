<?php
require_once '/home/admin/python/pdnsadminuser/php-api/config/config.php';
require_once '/home/admin/python/pdnsadminuser/php-api/config/database.php';

// Test templates functionality
echo "Checking database schema...\n";

// Get PowerDNS Admin database connection
$pdns_admin_db = new PDNSAdminDatabase();
$pdns_admin_conn = $pdns_admin_db->getConnection();

if (!$pdns_admin_conn) {
    echo "Failed to connect to PowerDNS Admin database\n";
    exit(1);
}

try {
    // Check domain_template_record table structure
    echo "Checking domain_template_record table structure:\n";
    $stmt = $pdns_admin_conn->prepare("DESCRIBE domain_template_record");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "Column: " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    // Sample query to see actual data
    echo "\nSample records from domain_template_record:\n";
    $stmt = $pdns_admin_conn->prepare("SELECT * FROM domain_template_record LIMIT 5");
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($records as $record) {
        echo "Record: " . json_encode($record) . "\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nSchema check completed.\n";
?>
