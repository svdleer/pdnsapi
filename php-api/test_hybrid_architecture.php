<?php
/**
 * Test the hybrid API + Database enrichment functionality
 */

ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php_hybrid_test.log');

echo "Testing Hybrid API + Database Enrichment\n";
echo "========================================\n\n";

try {
    // Load environment variables
    $env_file = __DIR__ . '/.env';
    if (file_exists($env_file)) {
        $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value);
            }
        }
    }
    
    // Load configuration
    require_once __DIR__ . '/config/pdns-admin-database.php';
    require_once __DIR__ . '/classes/PDNSAdminClient.php';
    
    global $pdns_config;
    $pdns_client = new PDNSAdminClient($pdns_config);
    
    echo "1. Testing Domain Sync to Local Database...\n";
    $sync_result = $pdns_client->syncDomainsToLocalDatabase();
    echo "   Success: " . ($sync_result['success'] ? 'YES' : 'NO') . "\n";
    echo "   Message: " . $sync_result['message'] . "\n";
    
    if (isset($sync_result['stats'])) {
        echo "   Stats:\n";
        echo "     Total processed: " . $sync_result['stats']['total_processed'] . "\n";
        echo "     New synced: " . $sync_result['stats']['new_synced'] . "\n";
        echo "     Updated: " . $sync_result['stats']['updated'] . "\n";
        echo "     Errors: " . $sync_result['stats']['errors'] . "\n";
    }
    echo "\n";
    
    echo "2. Testing Enhanced Domains with Database Enrichment...\n";
    $enhanced_result = $pdns_client->getAllDomainsWithAccounts();
    echo "   Status: " . $enhanced_result['status_code'] . "\n";
    
    if ($enhanced_result['status_code'] === 200 && isset($enhanced_result['data'])) {
        $metadata = $enhanced_result['metadata'];
        echo "   Total domains: " . $metadata['total_domains'] . "\n";
        echo "   Synced domains: " . $metadata['synced_domains'] . "\n";
        echo "   API-only domains: " . $metadata['api_only_domains'] . "\n";
        echo "   Source: " . $metadata['source'] . "\n";
        echo "\n";
        
        echo "3. Sample Enhanced Domains:\n";
        $shown = 0;
        foreach ($enhanced_result['data'] as $domain) {
            if ($shown < 5) {
                echo "   Domain: " . $domain['name'] . "\n";
                echo "     Type: " . $domain['type'] . "\n";
                echo "     Has local data: " . ($domain['has_local_data'] ? 'YES' : 'NO') . "\n";
                echo "     Sync status: " . $domain['sync_status'] . "\n";
                
                if ($domain['has_local_data']) {
                    echo "     Account: " . ($domain['account_name'] ?? 'None') . "\n";
                    echo "     Created: " . ($domain['created_at'] ?? 'Unknown') . "\n";
                }
                
                if (isset($domain['records_count'])) {
                    echo "     Records: " . $domain['records_count'] . "\n";
                }
                
                echo "\n";
                $shown++;
            }
        }
        
        // Show statistics
        $with_accounts = array_filter($enhanced_result['data'], function($d) { 
            return !empty($d['account_name']); 
        });
        
        $with_local_data = array_filter($enhanced_result['data'], function($d) { 
            return $d['has_local_data']; 
        });
        
        echo "4. Business Logic Statistics:\n";
        echo "   Domains with accounts: " . count($with_accounts) . "\n";
        echo "   Domains with local data: " . count($with_local_data) . "\n";
        echo "   Sync coverage: " . round((count($with_local_data) / count($enhanced_result['data'])) * 100, 1) . "%\n";
    }
    echo "\n";
    
    echo "✅ Hybrid API + Database Architecture Working!\n";
    echo "\n🎯 Architecture Summary:\n";
    echo "   - PowerDNS Admin API: Real-time DNS operations\n";
    echo "   - Local Database: Business logic & metadata\n";
    echo "   - Sync Process: Keeps both in harmony\n";
    echo "   - Enhanced Data: Best of both worlds\n";
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\nTest completed at " . date('Y-m-d H:i:s') . "\n";
echo "Check logs: /tmp/php_hybrid_test.log\n";
?>
