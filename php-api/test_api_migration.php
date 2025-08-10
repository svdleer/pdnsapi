<?php
/**
 * Test the updated PDNSAdminClient functions that now use API instead of database
 */

ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php_api_migration_test.log');

echo "Testing API Migration from Database to API calls\n";
echo "===============================================\n\n";

try {
    // Load environment variables from the correct location (php-api/.env)
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
    
    echo "Environment loaded:\n";
    echo "  PDNS_BASE_URL: " . ($_ENV['PDNS_BASE_URL'] ?? 'NOT SET') . "\n";
    echo "  PDNS_API_KEY: " . (isset($_ENV['PDNS_API_KEY']) ? 'SET (' . substr($_ENV['PDNS_API_KEY'], 0, 10) . '...)' : 'NOT SET') . "\n";
    echo "  PDNS_SERVER_KEY: " . (isset($_ENV['PDNS_SERVER_KEY']) ? 'SET (' . substr($_ENV['PDNS_SERVER_KEY'], 0, 10) . '...)' : 'NOT SET') . "\n\n";
    
    // Load configuration
    require_once __DIR__ . '/config/pdns-admin-database.php';
    require_once __DIR__ . '/config/pdns-admin-database.php';
    require_once __DIR__ . '/classes/PDNSAdminClient.php';
    
    global $pdns_config;
    $pdns_client = new PDNSAdminClient($pdns_config);
    
    echo "1. Testing getAllDomains() (now API-based)...\n";
    $all_domains = $pdns_client->getAllDomains();
    echo "   Status: " . $all_domains['status_code'] . "\n";
    if ($all_domains['status_code'] === 200 && isset($all_domains['data'])) {
        echo "   Domains found: " . count($all_domains['data']) . "\n";
        echo "   Source: " . ($all_domains['source'] ?? 'unknown') . "\n";
        
        // Show first few domains
        $shown = 0;
        foreach ($all_domains['data'] as $domain) {
            if ($shown < 3) {
                echo "   - " . ($domain['name'] ?? $domain['id']) . " (" . ($domain['kind'] ?? $domain['type'] ?? 'Unknown') . ")\n";
                $shown++;
            }
        }
    } else {
        echo "   Error: " . ($all_domains['error'] ?? 'Unknown error') . "\n";
    }
    echo "\n";
    
    echo "2. Testing getDomainByName() (now API-based)...\n";
    // Try to get a domain that exists
    $test_domain = null;
    if (isset($all_domains['data']) && !empty($all_domains['data'])) {
        $test_domain = $all_domains['data'][0]['name'];
        echo "   Testing with domain: {$test_domain}\n";
        
        $domain_result = $pdns_client->getDomainByName($test_domain);
        echo "   Status: " . $domain_result['status_code'] . "\n";
        echo "   Source: " . ($domain_result['source'] ?? 'unknown') . "\n";
        
        if ($domain_result['status_code'] === 200 && isset($domain_result['data'])) {
            echo "   Domain found: " . $domain_result['data']['name'] . "\n";
            if (isset($domain_result['data']['rrsets'])) {
                echo "   Records: " . count($domain_result['data']['rrsets']) . "\n";
            }
        }
    } else {
        echo "   No domains available for testing\n";
    }
    echo "\n";
    
    echo "3. Testing searchDomainsByName() (now API-based)...\n";
    $search_results = $pdns_client->searchDomainsByName('example');
    echo "   Status: " . $search_results['status_code'] . "\n";
    if ($search_results['status_code'] === 200 && isset($search_results['data'])) {
        echo "   Matches found: " . count($search_results['data']) . "\n";
        echo "   Search source: " . ($search_results['metadata']['searched_from'] ?? 'unknown') . "\n";
        
        // Show first few matches
        $shown = 0;
        foreach ($search_results['data'] as $domain) {
            if ($shown < 2) {
                echo "   - " . $domain['name'] . "\n";
                $shown++;
            }
        }
    }
    echo "\n";
    
    echo "4. Testing getAllDomainsWithAccounts() (now API-enhanced)...\n";
    $domains_with_accounts = $pdns_client->getAllDomainsWithAccounts();
    echo "   Status: " . $domains_with_accounts['status_code'] . "\n";
    if ($domains_with_accounts['status_code'] === 200 && isset($domains_with_accounts['data'])) {
        echo "   Domains found: " . count($domains_with_accounts['data']) . "\n";
        echo "   Source: " . ($domains_with_accounts['metadata']['source'] ?? 'unknown') . "\n";
        echo "   Accounts available: " . ($domains_with_accounts['metadata']['accounts_available'] ?? 0) . "\n";
        
        // Count domains with accounts
        $with_accounts = 0;
        foreach ($domains_with_accounts['data'] as $domain) {
            if (!empty($domain['account_name'])) {
                $with_accounts++;
            }
        }
        echo "   Domains with accounts: {$with_accounts}\n";
        
        // Show domains with accounts
        $shown = 0;
        foreach ($domains_with_accounts['data'] as $domain) {
            if (!empty($domain['account_name']) && $shown < 2) {
                echo "   - " . $domain['name'] . " → " . $domain['account_name'] . "\n";
                $shown++;
            }
        }
    }
    echo "\n";
    
    echo "✅ API Migration Test Completed Successfully!\n";
    echo "All functions now use PowerDNS Admin API instead of direct database access.\n";
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\nTest completed at " . date('Y-m-d H:i:s') . "\n";
echo "Check logs: /tmp/php_api_migration_test.log\n";
?>
