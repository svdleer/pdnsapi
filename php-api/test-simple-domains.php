<?php
echo "=== SIMPLE DOMAINS API TEST ===\n\n";

// Set up the environment without loading config first
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = array();

$base_path = realpath(__DIR__);

// Load only what we need
require_once $base_path . '/config/database.php';
require_once $base_path . '/includes/database-compat.php';
require_once $base_path . '/models/Domain.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize domain object
$domain = new Domain($db);

echo "TESTING BASIC DOMAIN OPERATIONS:\n\n";

echo "1. Testing read() method:\n";
try {
    $stmt = $domain->read();
    $count = $stmt->rowCount();
    echo "✅ read() returned $count domains\n";
    
    // Fetch first 3 rows
    $results = [];
    for ($i = 0; $i < 3; $i++) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $results[] = $row;
            echo "   Domain: {$row['name']} (ID: {$row['id']}, Account: " . ($row['account_name'] ?? 'NULL') . ")\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ read() failed: " . $e->getMessage() . "\n";
}

echo "\n2. Testing readOne() method:\n";
try {
    $domain->id = 1;
    if ($domain->readOne()) {
        echo "✅ readOne() successful for domain ID 1\n";
        echo "   Name: {$domain->name}\n";
        echo "   Type: {$domain->type}\n";
        echo "   Account: {$domain->account}\n";
        echo "   Account ID: " . ($domain->account_id ?? 'NULL') . "\n";
    } else {
        echo "❌ readOne() failed - domain ID 1 not found\n";
    }
} catch (Exception $e) {
    echo "❌ readOne() failed: " . $e->getMessage() . "\n";
}

echo "\n3. Testing readByAccountId() method:\n";
try {
    $stmt = $domain->readByAccountId(1);
    $count = $stmt->rowCount();
    echo "✅ readByAccountId(1) returned $count domains\n";
    
    if ($count > 0) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "   Domain: {$row['name']} (Account: " . ($row['account_name'] ?? 'NULL') . ")\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ readByAccountId() failed: " . $e->getMessage() . "\n";
}

echo "\n4. Checking actual account assignments:\n";
try {
    $query = "SELECT COUNT(*) as count FROM domains WHERE account_id IS NOT NULL";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Domains with account_id assigned: {$result['count']}\n";
    
    $query = "SELECT COUNT(*) as count FROM domains WHERE account IS NOT NULL AND account != ''";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Domains with account name assigned: {$result['count']}\n";
    
} catch (Exception $e) {
    echo "❌ Account check failed: " . $e->getMessage() . "\n";
}

echo "\n=== BASIC TEST COMPLETE ===\n";

// Now test if we can call the actual domains.php API
echo "\n=== TESTING DOMAINS.PHP API FILE ===\n";

try {
    // Temporarily redirect output to catch the API response
    ob_start();
    
    // Mock the $_SERVER environment for the API
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = array(); // Get all domains
    
    // Include the API file
    include 'api/domains.php';
    
    $output = ob_get_clean();
    echo "API Output Length: " . strlen($output) . " characters\n";
    
    if (strlen($output) > 0) {
        echo "✅ domains.php executed without fatal errors\n";
        echo "First 200 characters of output:\n";
        echo substr($output, 0, 200) . "...\n";
    } else {
        echo "⚠️  domains.php produced no output\n";
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ domains.php failed: " . $e->getMessage() . "\n";
} catch (ParseError $e) {
    ob_end_clean();
    echo "❌ domains.php parse error: " . $e->getMessage() . "\n";
} catch (Error $e) {
    ob_end_clean();
    echo "❌ domains.php error: " . $e->getMessage() . "\n";
}

echo "\n=== ALL TESTS COMPLETE ===\n";
?>
