<?php
/**
 * Comprehensive Database Test
 * Tests both PowerDNS Admin database (read-only) and API database (read-write)
 */

ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php_debug.log');

echo "Comprehensive Database Configuration Test\n";
echo "========================================\n\n";

try {
    // Load environment
    require_once __DIR__ . '/includes/env-loader.php';
    require_once __DIR__ . '/includes/autoloader.php';
    
    echo "1. Environment Variables Status:\n";
    $env_vars = [
        'PDNS_BASE_URL' => 'PowerDNS Admin API Base URL',
        'PDNS_API_KEY' => 'PowerDNS Admin API Key',
        'PDNS_SERVER_KEY' => 'PowerDNS Server API Key',
        'API_DB_HOST' => 'API Database Host',
        'API_DB_NAME' => 'API Database Name', 
        'PDNS_ADMIN_DB_HOST' => 'PowerDNS Admin Database Host',
        'PDNS_ADMIN_DB_NAME' => 'PowerDNS Admin Database Name'
    ];
    
    foreach ($env_vars as $var => $description) {
        $status = isset($_ENV[$var]) && !empty($_ENV[$var]) ? '✅ SET' : '❌ NOT SET';
        $value = isset($_ENV[$var]) && !empty($_ENV[$var]) ? 
                 (strpos($var, 'KEY') !== false || strpos($var, 'PASS') !== false ? '***HIDDEN***' : $_ENV[$var]) : 'NOT SET';
        echo "   {$description}: {$status} ({$value})\n";
    }
    
    echo "\n2. API Database (Read-Write) - Our Business Logic:\n";
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        if ($conn) {
            echo "   Connection: ✅ Connected successfully\n";
            
            // Check our domains table
            $stmt = $conn->query("SELECT COUNT(*) as count FROM domains");
            $domain_count = $stmt->fetch()['count'];
            echo "   Domains: {$domain_count} total\n";
            
            // Check account associations
            $stmt = $conn->query("SELECT COUNT(*) as count FROM domains WHERE account_id IS NOT NULL");
            $account_count = $stmt->fetch()['count'];
            echo "   With accounts: {$account_count} domains\n";
            
            // Check accounts table
            $stmt = $conn->query("SELECT COUNT(*) as count FROM accounts");
            $accounts_total = $stmt->fetch()['count'];
            echo "   Total accounts: {$accounts_total}\n";
            
        } else {
            echo "   Connection: ❌ Failed to connect\n";
        }
        
    } catch (Exception $e) {
        echo "   Connection: ❌ Error - " . $e->getMessage() . "\n";
    }
    
    echo "\n3. PowerDNS Admin Database (Read-Only) - Official Data:\n";
    
    try {
        $pdns_db = new PDNSAdminDatabase(); 
        $pdns_conn = $pdns_db->getConnection();
        
        if ($pdns_conn) {
            echo "   Connection: ✅ Connected successfully\n";
            
            // Check PowerDNS Admin domain table (singular 'domain')
            $stmt = $pdns_conn->query("SELECT COUNT(*) as count FROM domain");
            $pdns_domain_count = $stmt->fetch()['count'];
            echo "   PowerDNS domains: {$pdns_domain_count} total\n";
            
            // Check PowerDNS Admin accounts
            $stmt = $pdns_conn->query("SELECT COUNT(*) as count FROM account");
            $pdns_account_count = $stmt->fetch()['count'];
            echo "   PowerDNS accounts: {$pdns_account_count} total\n";
            
            // Check domain-account associations in PowerDNS Admin
            $stmt = $pdns_conn->query("SELECT COUNT(*) as count FROM domain WHERE account_id IS NOT NULL");
            $pdns_with_accounts = $stmt->fetch()['count'];
            echo "   With account associations: {$pdns_with_accounts} domains\n";
            
            // Show sample data
            echo "   Sample domains with accounts:\n";
            $stmt = $pdns_conn->query("
                SELECT d.name, a.name as account_name, a.description 
                FROM domain d 
                LEFT JOIN account a ON d.account_id = a.id 
                WHERE d.account_id IS NOT NULL 
                LIMIT 3
            ");
            $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($samples as $sample) {
                echo "     - {$sample['name']} → {$sample['account_name']}\n";
            }
            
        } else {
            echo "   Connection: ❌ Failed to connect\n";
        }
        
    } catch (Exception $e) {
        echo "   Connection: ❌ Error - " . $e->getMessage() . "\n";
    }
    
    echo "\n4. API Integration Test:\n";
    
    try {
        require_once __DIR__ . '/config/pdns-admin-database.php';
        require_once __DIR__ . '/classes/PDNSAdminClient.php';
        
        global $pdns_config;
        $client = new PDNSAdminClient($pdns_config);
        
        echo "   PDNSAdminClient: ✅ Initialized successfully\n";
        echo "   Base URL: " . $pdns_config['base_url'] . "\n";
        echo "   Auth type: " . $pdns_config['auth_type'] . "\n";
        
        // Test a simple API call
        $domains = $client->getAllDomains();
        if ($domains['status_code'] === 200) {
            echo "   API call: ✅ Successfully retrieved " . count($domains['data']) . " domains\n";
        } else {
            echo "   API call: ❌ Failed with status " . $domains['status_code'] . "\n";
        }
        
    } catch (Exception $e) {
        echo "   API Integration: ❌ Error - " . $e->getMessage() . "\n";
    }
    
    echo "\n5. Hybrid Architecture Validation:\n";
    
    if (isset($domain_count) && isset($pdns_domain_count)) {
        if (abs($domain_count - $pdns_domain_count) <= 5) { // Allow small variance
            echo "   Sync status: ✅ API database in sync (±5 domains)\n";
            echo "     API DB: {$domain_count}, PowerDNS Admin: {$pdns_domain_count}\n";
        } else {
            echo "   Sync status: ⚠️ Databases may need sync\n";
            echo "     API DB: {$domain_count}, PowerDNS Admin: {$pdns_domain_count}\n";
        }
    }
    
    echo "\n✅ Database configuration test completed!\n";
    echo "Date: " . date('Y-m-d H:i:s') . "\n";
    
} catch (Exception $e) {
    echo "❌ Critical error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
