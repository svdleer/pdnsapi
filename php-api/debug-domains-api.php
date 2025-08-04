<?php
echo "=== DEBUGGING DOMAINS API ===\n\n";

// Test if we can load the domains API without errors
echo "1. TESTING API LOADING\n";

try {
    // Set up minimal environment
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = array();
    
    // Capture any output/errors
    ob_start();
    
    // Include the domains API file
    $base_path = realpath(__DIR__);
    
    // Check if required files exist
    $required_files = [
        $base_path . '/config/config.php',
        $base_path . '/config/database.php',
        $base_path . '/includes/database-compat.php',
        $base_path . '/models/Domain.php',
        $base_path . '/models/Account.php',
        $base_path . '/classes/PDNSAdminClient.php'
    ];
    
    foreach ($required_files as $file) {
        if (!file_exists($file)) {
            echo "❌ Missing required file: $file\n";
        } else {
            echo "✅ Found: " . basename($file) . "\n";
        }
    }
    
    // Try to load config
    echo "\n2. TESTING CONFIG LOADING\n";
    require_once $base_path . '/config/config.php';
    echo "✅ Config loaded successfully\n";
    
    // Try to load database
    echo "\n3. TESTING DATABASE CONNECTION\n";
    require_once $base_path . '/config/database.php';
    require_once $base_path . '/includes/database-compat.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "✅ Database connection successful\n";
        
        // Test basic query
        $test_query = $db->query("SELECT COUNT(*) as count FROM domains");
        $count = $test_query->fetch(PDO::FETCH_ASSOC)['count'];
        echo "✅ Domains table accessible: $count records\n";
    } else {
        echo "❌ Database connection failed\n";
    }
    
    // Try to load models
    echo "\n4. TESTING MODEL LOADING\n";
    require_once $base_path . '/models/Domain.php';
    require_once $base_path . '/models/Account.php';
    
    $domain = new Domain($db);
    $account = new Account($db);
    echo "✅ Models loaded successfully\n";
    
    // Try to load PDNSAdmin client
    echo "\n5. TESTING PDNSADMIN CLIENT\n";
    require_once $base_path . '/classes/PDNSAdminClient.php';
    
    if (isset($pdns_config)) {
        $pdns_client = new PDNSAdminClient($pdns_config);
        echo "✅ PDNSAdmin client created successfully\n";
    } else {
        echo "❌ PDNSAdmin config not found\n";
    }
    
    // Get any captured output
    $output = ob_get_clean();
    if (!empty($output)) {
        echo "\n6. CAPTURED OUTPUT/ERRORS:\n";
        echo $output . "\n";
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n7. TESTING API FUNCTIONS DIRECTLY\n";

// Test the actual API functions
try {
    // Mock the sendResponse function if it doesn't exist
    if (!function_exists('sendResponse')) {
        function sendResponse($code, $data, $message = '') {
            echo "API Response: HTTP $code\n";
            if (!empty($message)) echo "Message: $message\n";
            echo "Data: " . (is_array($data) ? count($data) . " items" : $data) . "\n";
        }
    }
    
    if (!function_exists('sendError')) {
        function sendError($code, $message) {
            echo "API Error: HTTP $code - $message\n";
        }
    }
    
    if (!function_exists('logApiRequest')) {
        function logApiRequest($endpoint, $method, $code) {
            echo "Log: $method $endpoint -> HTTP $code\n";
        }
    }
    
    // Test getAllDomains function
    echo "Testing getAllDomains()...\n";
    $stmt = $domain->read();
    $num = $stmt->rowCount();
    echo "✅ Domain read query returned $num rows\n";
    
    // Test a few rows
    $count = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC) && $count < 3) {
        echo "   Domain: " . $row['name'] . " (ID: " . $row['id'] . ")\n";
        $count++;
    }
    
} catch (Exception $e) {
    echo "❌ Function test error: " . $e->getMessage() . "\n";
}

echo "\n=== DEBUGGING COMPLETE ===\n";
?>
