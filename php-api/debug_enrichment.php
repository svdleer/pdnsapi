<?php
/**
 * Enhanced Debug for Enrichment Process
 */

ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php_debug.log');

echo "Enhanced Enrichment Debug\n";
echo "========================\n\n";

try {
    // Load environment
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
    
    require_once __DIR__ . '/includes/autoloader.php';
    require_once __DIR__ . '/config/pdns-admin-database.php';
    require_once __DIR__ . '/classes/PDNSAdminClient.php';
    
    global $pdns_config;
    $pdns_client = new PDNSAdminClient($pdns_config);
    
    echo "1. Testing direct enrichment...\n";
    $enhanced_result = $pdns_client->getAllDomainsWithAccounts();
    
    if ($enhanced_result['status_code'] === 200) {
        $total = count($enhanced_result['data']);
        $with_local = count(array_filter($enhanced_result['data'], function($d) { 
            return $d['has_local_data']; 
        }));
        
        echo "   Total domains from API: {$total}\n";
        echo "   Domains with local data: {$with_local}\n";
        echo "   Coverage: " . round(($with_local / $total) * 100, 1) . "%\n\n";
        
        echo "2. Sample of enhanced domains:\n";
        $count = 0;
        foreach ($enhanced_result['data'] as $domain) {
            if ($count < 3) {
                echo "   Domain: " . $domain['name'] . "\n";
                echo "     Has local data: " . ($domain['has_local_data'] ? 'YES' : 'NO') . "\n";
                if ($domain['has_local_data']) {
                    echo "     Account ID: " . ($domain['account_id'] ?? 'none') . "\n";
                    echo "     Account Name: " . ($domain['account_name'] ?? 'none') . "\n";
                }
                echo "\n";
                $count++;
            }
            
            // Stop after finding some with local data
            if ($count >= 3 && $domain['has_local_data']) {
                break;
            }
        }
        
        echo "3. Detailed local database check:\n";
        $db = new Database();
        $conn = $db->getConnection();
        
        // Get first API domain
        $first_api_domain = $enhanced_result['data'][0]['name'];
        $clean_name = rtrim($first_api_domain, '.');
        
        echo "   API domain: '{$first_api_domain}' (cleaned: '{$clean_name}')\n";
        
        // Look for it in local DB
        $stmt = $conn->prepare("SELECT name, account_id FROM domains WHERE name = ? OR name = ?");
        $stmt->execute([$clean_name, $first_api_domain]);
        $local_match = $stmt->fetch();
        
        if ($local_match) {
            echo "   ✅ Found in local DB: '" . $local_match['name'] . "' (account: " . ($local_match['account_id'] ?? 'none') . ")\n";
        } else {
            echo "   ❌ Not found in local DB\n";
            
            // Show what's in the local DB
            $stmt = $conn->query("SELECT name, account_id FROM domains ORDER BY name LIMIT 3");
            $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "   Local DB samples:\n";
            foreach ($samples as $sample) {
                echo "     - '" . $sample['name'] . "' (account: " . ($sample['account_id'] ?? 'none') . ")\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\nDebug completed at " . date('Y-m-d H:i:s') . "\n";
?>
