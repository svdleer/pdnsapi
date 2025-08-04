<?php
// Test the actual API endpoints by simulating requests
echo "=== Testing API Endpoints ===\n\n";

// Set up environment to simulate API calls
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = [];

echo "1. Testing /api/accounts...\n";
ob_start();
try {
    require 'api/accounts.php';
    $output = ob_get_clean();
    
    // Check if output is valid JSON
    $data = json_decode($output, true);
    if ($data && isset($data['data'])) {
        echo "✅ Accounts API working: " . count($data['data']) . " accounts returned\n";
    } else {
        echo "❌ Accounts API failed or returned invalid JSON\n";
        echo "Output: " . substr($output, 0, 200) . "\n";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ Accounts API error: " . $e->getMessage() . "\n";
}

echo "\n2. Testing /api/domains...\n";
ob_start();
try {
    // Reset environment
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = [];
    
    require 'api/domains.php';
    $output = ob_get_clean();
    
    // Check if output is valid JSON
    $data = json_decode($output, true);
    if ($data && isset($data['data'])) {
        echo "✅ Domains API working: " . count($data['data']) . " domains returned\n";
    } else {
        echo "❌ Domains API failed or returned invalid JSON\n";
        echo "Output: " . substr($output, 0, 200) . "\n";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ Domains API error: " . $e->getMessage() . "\n";
}

echo "\n3. Testing /api/domain-assignments...\n";
ob_start();
try {
    // Reset environment
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = [];
    
    require 'api/domain-assignments.php';
    $output = ob_get_clean();
    
    // Check if output is valid JSON
    $data = json_decode($output, true);
    if ($data && isset($data['data'])) {
        echo "✅ Domain Assignments API working: " . count($data['data']) . " assignments returned\n";
        if (count($data['data']) > 0) {
            $assignment = $data['data'][0];
            echo "   First assignment: {$assignment['domain_name']} → {$assignment['account_name']}\n";
        }
    } else {
        echo "❌ Domain Assignments API failed or returned invalid JSON\n";
        echo "Output: " . substr($output, 0, 200) . "\n";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ Domain Assignments API error: " . $e->getMessage() . "\n";
}

echo "\n=== API Test Complete ===\n";
?>
