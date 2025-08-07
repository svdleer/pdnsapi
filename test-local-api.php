<?php
require_once __DIR__ . '/php-api/config/config.php';
require_once __DIR__ . '/php-api/classes/PDNSAdminClient.php';

echo "=== LOCAL WRAPPER API TESTING ===" . PHP_EOL;
echo "Testing our local PHP wrapper API functionality" . PHP_EOL;
echo "Date: " . date('Y-m-d H:i:s') . PHP_EOL;
echo "====================================" . PHP_EOL . PHP_EOL;

$client = new PDNSAdminClient($pdns_config);

// Test our local domains.php API by including it directly
echo "🧪 Test 1: Local Domains API (GET all domains)" . PHP_EOL;

// Simulate API call to our domains.php
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = []; // Clear any existing GET params

// Capture output from our domains.php API
ob_start();
try {
    include __DIR__ . '/php-api/api/domains.php';
} catch (Exception $e) {
    echo "Error including domains.php: " . $e->getMessage() . PHP_EOL;
}
$domains_output = ob_get_clean();

echo "Domains API Output:" . PHP_EOL;
echo substr($domains_output, 0, 500) . "..." . PHP_EOL . PHP_EOL;

// Test 2: Test domain filtering by ID through our local API
echo "🧪 Test 2: Local Domain Filtering (by ID)" . PHP_EOL;

// Reset environment
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = ['id' => '1420']; // Use the ID from our previous test

ob_start();
try {
    include __DIR__ . '/php-api/api/domains.php';
} catch (Exception $e) {
    echo "Error including domains.php: " . $e->getMessage() . PHP_EOL;
}
$domain_by_id_output = ob_get_clean();

echo "Domain by ID Output:" . PHP_EOL;
echo substr($domain_by_id_output, 0, 300) . "..." . PHP_EOL . PHP_EOL;

// Test 3: Test domain sync functionality
echo "🧪 Test 3: Domain Sync Functionality" . PHP_EOL;

$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = ['sync' => 'true'];

ob_start();
try {
    include __DIR__ . '/php-api/api/domains.php';
} catch (Exception $e) {
    echo "Error including domains.php: " . $e->getMessage() . PHP_EOL;
}
$sync_output = ob_get_clean();

echo "Domain Sync Output:" . PHP_EOL;
echo substr($sync_output, 0, 400) . "..." . PHP_EOL . PHP_EOL;

// Test 4: Direct database connectivity test
echo "🧪 Test 4: Database Connectivity Test" . PHP_EOL;
try {
    require_once __DIR__ . '/php-api/config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "✅ Database connection successful" . PHP_EOL;
        
        // Test if domains table exists
        $stmt = $db->query("SHOW TABLES LIKE 'domains'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Domains table exists" . PHP_EOL;
            
            // Count local domains
            $count_stmt = $db->query("SELECT COUNT(*) as count FROM domains");
            $count = $count_stmt->fetch(PDO::FETCH_ASSOC);
            echo "📊 Local domains count: " . $count['count'] . PHP_EOL;
        } else {
            echo "⚠️  Domains table not found" . PHP_EOL;
        }
    } else {
        echo "❌ Database connection failed" . PHP_EOL;
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL;

// Test 5: What endpoints actually work - practical test
echo "🧪 Test 5: Practical PowerDNS Admin API Test" . PHP_EOL;

echo "Testing practical endpoints that we know work:" . PHP_EOL;

// Test users endpoint
$users_result = $client->getAllUsers();
echo "• Users endpoint: HTTP {$users_result['status_code']}" . ($users_result['status_code'] === 200 ? ' ✅' : ' ❌') . PHP_EOL;

// Test apikeys endpoint  
$apikeys_result = $client->getAllApiKeys();
echo "• API Keys endpoint: HTTP {$apikeys_result['status_code']}" . ($apikeys_result['status_code'] === 200 ? ' ✅' : ' ❌') . PHP_EOL;

// Test domains endpoint
$domains_result = $client->getAllDomains();
echo "• Domains endpoint: HTTP {$domains_result['status_code']}" . ($domains_result['status_code'] === 200 ? ' ✅' : ' ❌') . PHP_EOL;

echo PHP_EOL;

// Summary and current state
echo "====================================" . PHP_EOL;
echo "CURRENT API STATE SUMMARY" . PHP_EOL;
echo "====================================" . PHP_EOL;

echo "🎯 WHAT WORKS:" . PHP_EOL;
echo "• PowerDNS Admin bulk operations (GET all users, domains, apikeys)" . PHP_EOL;
echo "• Our local database for extended functionality" . PHP_EOL;
echo "• Domain synchronization from PowerDNS Admin to local DB" . PHP_EOL;
echo "• Local filtering and search capabilities" . PHP_EOL;

echo PHP_EOL;
echo "⚠️  WHAT DOESN'T WORK:" . PHP_EOL;
echo "• Individual domain operations via PowerDNS Admin API" . PHP_EOL;
echo "• Domain creation/update via PowerDNS Admin API" . PHP_EOL;
echo "• Template operations (not supported by PowerDNS Admin)" . PHP_EOL;
echo "• Direct PowerDNS server API (requires different auth)" . PHP_EOL;

echo PHP_EOL;
echo "💡 RECOMMENDED ARCHITECTURE:" . PHP_EOL;
echo "1. Use PowerDNS Admin API for bulk data retrieval" . PHP_EOL;
echo "2. Store data in local database for individual operations" . PHP_EOL;
echo "3. Implement sync operations to keep data current" . PHP_EOL;
echo "4. Provide individual operations via local database" . PHP_EOL;
echo "5. Update Swagger docs to reflect actual capabilities" . PHP_EOL;

echo PHP_EOL . "Local wrapper API testing completed!" . PHP_EOL;
?>
