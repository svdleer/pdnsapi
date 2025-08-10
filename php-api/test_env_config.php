<?php
/**
 * Test Environment Variables and Database Connections
 */

echo "Testing Environment Variables and Database Configuration\n";
echo "======================================================\n\n";

// Load environment
require_once __DIR__ . '/includes/env-loader.php';

echo "1. Environment Variables:\n";
echo "   PDNS_BASE_URL: " . ($_ENV['PDNS_BASE_URL'] ?? 'NOT SET') . "\n";
echo "   PDNS_API_KEY: " . (isset($_ENV['PDNS_API_KEY']) ? '***SET***' : 'NOT SET') . "\n";
echo "   PDNS_SERVER_KEY: " . (isset($_ENV['PDNS_SERVER_KEY']) ? '***SET***' : 'NOT SET') . "\n";
echo "   AVANT_API_KEY: " . (isset($_ENV['AVANT_API_KEY']) ? '***SET***' : 'NOT SET') . "\n\n";

echo "2. PowerDNS Admin Database Configuration:\n";
echo "   PDNS_ADMIN_DB_HOST: " . ($_ENV['PDNS_ADMIN_DB_HOST'] ?? 'NOT SET') . "\n";
echo "   PDNS_ADMIN_DB_PORT: " . ($_ENV['PDNS_ADMIN_DB_PORT'] ?? 'NOT SET') . "\n";
echo "   PDNS_ADMIN_DB_NAME: " . ($_ENV['PDNS_ADMIN_DB_NAME'] ?? 'NOT SET') . "\n";
echo "   PDNS_ADMIN_DB_USER: " . ($_ENV['PDNS_ADMIN_DB_USER'] ?? 'NOT SET') . "\n";
echo "   PDNS_ADMIN_DB_PASS: " . (isset($_ENV['PDNS_ADMIN_DB_PASS']) ? '***SET***' : 'NOT SET') . "\n\n";

echo "3. API Database Configuration:\n";
echo "   API_DB_HOST: " . ($_ENV['API_DB_HOST'] ?? 'NOT SET') . "\n";
echo "   API_DB_PORT: " . ($_ENV['API_DB_PORT'] ?? 'NOT SET') . "\n";
echo "   API_DB_NAME: " . ($_ENV['API_DB_NAME'] ?? 'NOT SET') . "\n";
echo "   API_DB_USER: " . ($_ENV['API_DB_USER'] ?? 'NOT SET') . "\n";
echo "   API_DB_PASS: " . (isset($_ENV['API_DB_PASS']) ? '***SET***' : 'NOT SET') . "\n";
echo "   API_DB_CHARSET: " . ($_ENV['API_DB_CHARSET'] ?? 'NOT SET') . "\n\n";

echo "4. Testing Database Connections:\n";

try {
    require_once __DIR__ . '/includes/autoloader.php';
    
    // Test API database connection
    echo "   API Database: ";
    $db = new Database();
    $conn = $db->getConnection();
    if ($conn) {
        echo "✅ Connected successfully\n";
    } else {
        echo "❌ Connection failed\n";
    }
    
    // Test PowerDNS Admin database connection
    echo "   PowerDNS Admin Database: ";
    $pdns_db = new PDNSAdminDatabase();
    $pdns_conn = $pdns_db->getConnection();
    if ($pdns_conn) {
        echo "✅ Connected successfully\n";
        
        // Test a simple query using the correct table name
        $stmt = $pdns_conn->query("SELECT COUNT(*) as count FROM domain LIMIT 1");
        $result = $stmt->fetch();
        echo "     Domains in PowerDNS Admin: " . $result['count'] . "\n";
    } else {
        echo "❌ Connection failed\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error testing connections: " . $e->getMessage() . "\n";
}

echo "\n5. Testing PDNSAdminClient Configuration:\n";

try {
    require_once __DIR__ . '/config/pdns-admin-database.php';
    require_once __DIR__ . '/classes/PDNSAdminClient.php';
    
    global $pdns_config;
    echo "   Base URL: " . $pdns_config['base_url'] . "\n";
    echo "   Auth Type: " . $pdns_config['auth_type'] . "\n";
    echo "   API Key: " . (isset($pdns_config['api_key']) ? '***SET***' : 'NOT SET') . "\n";
    echo "   Server Key: " . (isset($pdns_config['pdns_server_key']) ? '***SET***' : 'NOT SET') . "\n";
    
    $client = new PDNSAdminClient($pdns_config);
    echo "   Client initialized: ✅\n";
    
} catch (Exception $e) {
    echo "❌ Error testing PDNSAdminClient: " . $e->getMessage() . "\n";
}

echo "\n✅ Environment and database configuration test completed!\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
?>
