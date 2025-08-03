<?php
// Test the old accounts endpoint to verify it returns 404
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/php-api/accounts';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['HTTPS'] = 'on';
$_SERVER['SERVER_PORT'] = 443;

// Capture output
ob_start();

// Include the main API file
require_once 'index.php';

// Get the output
$output = ob_get_clean();

// Display the output
echo "Testing old /accounts endpoint (should return 404):\n";
echo $output;
echo "\n";
?>
