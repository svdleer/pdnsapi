<?php
/**
 * PowerDNS Admin Database Connection Diagnostic
 */
require_once __DIR__ . '/config/pdns-admin-database.php';

echo "<h2>PowerDNS Admin Database Connection Test</h2>\n";

// Test the current configuration
echo "<h3>Testing current configuration:</h3>\n";
echo "Host: cora.avant.nl<br>\n";
echo "Database: powerdnsadmin<br>\n";
echo "Username: pdns_api_db<br>\n";

$pdns_admin_db = new PDNSAdminDatabase();
$conn = $pdns_admin_db->getConnection();

if ($conn) {
    echo "<span style='color: green;'>✓ Connection successful!</span><br>\n";
    
    // Test if the 'user' table exists
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM user");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        echo "<span style='color: green;'>✓ 'user' table found with {$count} records</span><br>\n";
    } catch (PDOException $e) {
        echo "<span style='color: red;'>✗ 'user' table not found: " . $e->getMessage() . "</span><br>\n";
    }
    
} else {
    echo "<span style='color: red;'>✗ Connection failed</span><br>\n";
    echo "Check the error log for details.<br>\n";
    
    // Let's try to list available databases
    echo "<h3>Trying to list available databases:</h3>\n";
    
    $possible_names = ['powerdnsadmin', 'pdnsadmin', 'pdns_admin', 'powerdns_admin', 'pdns', 'powerdns'];
    
    foreach ($possible_names as $db_name) {
        try {
            $test_conn = new PDO(
                "mysql:host=cora.avant.nl;dbname={$db_name};charset=utf8mb4",
                'pdns_api_db',
                '8swoajKuchij]'
            );
            $test_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            echo "<span style='color: green;'>✓ Database '{$db_name}' exists and is accessible</span><br>\n";
            
            // Check if it has a 'user' table
            try {
                $stmt = $test_conn->prepare("SELECT COUNT(*) FROM user");
                $stmt->execute();
                $count = $stmt->fetchColumn();
                echo "<span style='color: blue;'>  → Has 'user' table with {$count} records (likely PowerDNS Admin DB)</span><br>\n";
            } catch (PDOException $e) {
                echo "<span style='color: orange;'>  → No 'user' table found</span><br>\n";
            }
            
        } catch (PDOException $e) {
            echo "<span style='color: red;'>✗ Database '{$db_name}' not accessible</span><br>\n";
        }
    }
}

// Also test the main API database connection
echo "<h3>Testing main API database:</h3>\n";
require_once __DIR__ . '/config/database.php';
$main_db = new Database();
$main_conn = $main_db->getConnection();

if ($main_conn) {
    echo "<span style='color: green;'>✓ Main API database connection successful</span><br>\n";
} else {
    echo "<span style='color: red;'>✗ Main API database connection failed</span><br>\n";
}
?>
