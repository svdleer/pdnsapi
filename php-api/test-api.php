<?php
// Simulate web environment for testing
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/php-api/nonexistent';
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
echo "Output:\n";
echo $output;
echo "\n";
?>
