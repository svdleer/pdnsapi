<?php
// Test valid endpoints
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['HTTPS'] = 'on';
$_SERVER['SERVER_PORT'] = 443;

echo "Testing root endpoint:\n";
$_SERVER['REQUEST_URI'] = '/php-api/';
ob_start();
include 'index.php';
$output = ob_get_clean();
echo $output . "\n\n";

echo "Testing docs endpoint:\n";
$_SERVER['REQUEST_URI'] = '/php-api/docs';
ob_start();
include 'index.php';
$output = ob_get_clean();
echo $output . "\n\n";
?>
