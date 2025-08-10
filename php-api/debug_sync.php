<?php
/**
 * Debug the database sync and matching
 */

ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php_debug.log');

echo "Debugging Database Sync and Matching\n";
echo "====================================\n\n";

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
    
    echo "1. Checking local database content...\n";
    $db = new Database();
    $conn = $db->getConnection();
    
    // Count total domains in local DB
    $stmt = $conn->query("SELECT COUNT(*) as count FROM domains");
    $count = $stmt->fetch()['count'];
    echo "   Total domains in local DB: {$count}\n";
    
    // Show first few domains in local DB
    $stmt = $conn->query("SELECT name, account_id, created_at FROM domains LIMIT 5");
    $local_domains = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   First 5 domains in local DB:\n";
    foreach ($local_domains as $domain) {
        echo "     - " . $domain['name'] . " (account: " . ($domain['account_id'] ?? 'none') . ")\n";
    }
    echo "\n";
    
    echo "2. Getting PowerDNS API domains...\n";
    require_once __DIR__ . '/config/pdns-admin-database.php';
    require_once __DIR__ . '/classes/PDNSAdminClient.php';
    
    global $pdns_config;
    $pdns_client = new PDNSAdminClient($pdns_config);
    
    $api_domains = $pdns_client->makeRequest('/servers/localhost/zones', 'GET');
    
    if ($api_domains['status_code'] === 200 && isset($api_domains['data'])) {
        echo "   Total domains from API: " . count($api_domains['data']) . "\n";
        echo "   First 5 domains from API:\n";
        
        for ($i = 0; $i < 5 && $i < count($api_domains['data']); $i++) {
            $domain = $api_domains['data'][$i];
            echo "     - " . $domain['name'] . "\n";
        }
        echo "\n";
        
        echo "3. Testing name matching...\n";
        // Test matching between API and local DB
        $api_name = $api_domains['data'][0]['name'];
        $api_name_clean = rtrim($api_name, '.');
        
        echo "   API domain: '{$api_name}'\n";
        echo "   Cleaned: '{$api_name_clean}'\n";
        
        // Check if this exists in local DB
        $stmt = $conn->prepare("SELECT name FROM domains WHERE name = ? OR name = ? LIMIT 1");
        $stmt->execute([$api_name, $api_name_clean]);
        $match = $stmt->fetch();
        
        if ($match) {
            echo "   ✅ Found match in local DB: '" . $match['name'] . "'\n";
        } else {
            echo "   ❌ No match found in local DB\n";
            
            // Try fuzzy search
            $stmt = $conn->prepare("SELECT name FROM domains WHERE name LIKE ? LIMIT 3");
            $stmt->execute(['%' . $api_name_clean . '%']);
            $fuzzy = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($fuzzy) {
                echo "   Fuzzy matches:\n";
                foreach ($fuzzy as $f) {
                    echo "     - '" . $f['name'] . "'\n";
                }
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\nDebug completed at " . date('Y-m-d H:i:s') . "\n";
?>
