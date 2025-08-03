<?php
// Test the sync function fix
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/php-api/users';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['HTTPS'] = 'on';
$_SERVER['SERVER_PORT'] = 443;

echo "Testing users endpoint with error handling...\n";

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Capture output
    ob_start();
    
    // Include the main API file
    require_once 'index.php';
    
    // Get the output
    $output = ob_get_clean();
    
    // Display the output
    echo "Output received:\n";
    echo $output;
    echo "\n";
} catch (Exception $e) {
    echo "Exception caught: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
