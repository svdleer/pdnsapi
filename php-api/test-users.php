<?php
// Test the users endpoint
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/php-api/users';
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
echo "Testing /users endpoint:\n";
echo $output;
echo "\n";
?>
