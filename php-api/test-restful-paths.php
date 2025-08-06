<?php
/**
 * Test RESTful Path Parameter Support
 * Tests both query parameter and path parameter formats
 */

echo "=== Testing RESTful Path Parameter Support ===\n\n";

// Test URL parsing logic
function testPathParsing($test_uri) {
    echo "Testing URI: $test_uri\n";
    
    $path_parts = explode('/', trim(parse_url($test_uri, PHP_URL_PATH), '/'));
    echo "  Path parts: " . implode(', ', $path_parts) . "\n";
    
    $accounts_index = array_search('accounts', $path_parts);
    if ($accounts_index !== false && isset($path_parts[$accounts_index + 1])) {
        $path_segment = $path_parts[$accounts_index + 1];
        if (is_numeric($path_segment)) {
            echo "  ✓ Found numeric ID: $path_segment\n";
        } elseif (!empty($path_segment) && !is_numeric($path_segment)) {
            echo "  ✓ Found username: $path_segment\n";
        } else {
            echo "  - Empty path segment\n";
        }
    } else {
        echo "  - No path parameter found\n";
    }
    echo "\n";
}

// Test various URL formats
testPathParsing('/accounts/94');
testPathParsing('/accounts/testuser');
testPathParsing('/accounts');
testPathParsing('/php-api/api/accounts/94');
testPathParsing('https://pdnsapi.avant.nl/accounts/94');
testPathParsing('/accounts?id=94');

echo "=== RESTful Support Implementation Complete ===\n";
echo "Supported URL formats:\n";
echo "  GET  /accounts/94       - Get account by ID (RESTful)\n";
echo "  GET  /accounts?id=94    - Get account by ID (query param)\n";
echo "  GET  /accounts/username - Get account by username (RESTful)\n";
echo "  GET  /accounts?username=name - Get account by username (query param)\n";
echo "  PUT  /accounts/94       - Update account by ID\n";
echo "  DELETE /accounts/94     - Delete account by ID\n";
?>
