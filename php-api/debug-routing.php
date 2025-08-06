<?php
/**
 * Debug routing script to test path parsing
 */

// Simulate the REQUEST_URI for /accounts/get
$_SERVER['REQUEST_URI'] = '/accounts/get';
$request_uri = $_SERVER['REQUEST_URI'];
$path_parts = explode('/', trim(parse_url($request_uri, PHP_URL_PATH), '/'));

echo "Request URI: $request_uri\n";
echo "Path parts: " . implode(', ', $path_parts) . "\n";

// Check for JSON-only endpoints
$accounts_index = array_search('accounts', $path_parts);
$json_endpoint = null;

if ($accounts_index !== false && isset($path_parts[$accounts_index + 1])) {
    $path_segment = $path_parts[$accounts_index + 1];
    
    echo "Accounts index: $accounts_index\n";
    echo "Path segment: $path_segment\n";
    
    // Check for JSON-only endpoints
    if (in_array($path_segment, ['get', 'update', 'delete'])) {
        $json_endpoint = $path_segment;
        echo "JSON endpoint detected: $json_endpoint\n";
    } else {
        echo "Not a JSON endpoint\n";
    }
} else {
    echo "Accounts not found or no next segment\n";
}

echo "Final json_endpoint value: " . ($json_endpoint ?? 'NULL') . "\n";
?>
