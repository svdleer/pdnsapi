<?php
/**
 * Test the Template API endpoint directly
 */

// Test endpoint: POST /api/templates?id=22&action=create-domain
// This simulates how the API will actually be used

ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php_template_api_test.log');

// Start output buffering to capture any output
ob_start();

echo "Testing Template API Endpoint\n";
echo "=============================\n\n";

try {
    // Simulate the API request data
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_GET['id'] = '22'; // Aron template
    $_GET['action'] = 'create-domain';
    
    // Mock API key authentication by setting environment
    $_ENV['AVANT_API_KEY'] = '46b3d78c557cd66a047a38897914d203ab5c359719161e836ecce5508e57b1a9';
    $_SERVER['HTTP_X_API_KEY'] = $_ENV['AVANT_API_KEY'];
    
    // Create test domain data
    $test_domain = 'api-test-' . date('YmdHis') . '.example.com';
    $domain_data = [
        'name' => $test_domain,
        'kind' => 'Native'
    ];
    
    echo "1. Test domain: {$test_domain}\n";
    echo "2. Template ID: 22 (Aron)\n";
    echo "3. Calling API endpoint...\n\n";
    
    // Simulate the POST data
    global $HTTP_RAW_POST_DATA;
    $HTTP_RAW_POST_DATA = json_encode($domain_data);
    
    // Capture output
    ob_start();
    
    // Include the API endpoint file
    include __DIR__ . '/api/templates.php';
    
    // Get the output
    $api_output = ob_get_clean();
    
    echo "4. API Response:\n";
    echo $api_output . "\n";
    
    // Try to decode as JSON to see if it's a proper API response
    $json_response = json_decode($api_output, true);
    if ($json_response) {
        echo "\n5. Parsed Response:\n";
        if (isset($json_response['message'])) {
            echo "   Message: " . $json_response['message'] . "\n";
        }
        if (isset($json_response['data'])) {
            echo "   Data:\n";
            foreach ($json_response['data'] as $key => $value) {
                echo "     {$key}: {$value}\n";
            }
        }
        
        if (isset($json_response['data']['domain_name'])) {
            echo "\n✅ API TEST SUCCESSFUL!\n";
            echo "   Created domain: " . $json_response['data']['domain_name'] . "\n";
            echo "   Template: " . $json_response['data']['template_name'] . "\n";
            echo "   Records applied: " . $json_response['data']['records_applied'] . "\n";
        }
    } else {
        echo "\n⚠️  Response is not valid JSON, raw output above\n";
    }

} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

// Clean up output buffer
ob_end_clean();

echo "\nTest completed at " . date('Y-m-d H:i:s') . "\n";
echo "Check logs: /tmp/php_template_api_test.log\n";
?>
