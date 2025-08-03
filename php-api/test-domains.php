<?php
// Test domains endpoint
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/php-api/domains';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['HTTPS'] = 'on';
$_SERVER['SERVER_PORT'] = 443;

echo "Testing domains endpoint...\n";

try {
    ob_start();
    require_once 'index.php';
    $output = ob_get_clean();
    echo "Domains result:\n";
    echo $output . "\n";
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
?>
