<?php
// Simple PHP test - should return JSON if PHP is working properly
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'message' => 'PHP is working correctly',
    'php_version' => phpversion(),
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
