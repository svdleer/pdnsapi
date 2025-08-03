<?php
// Simple debug script to troubleshoot routing issues
echo "<h1>PHP API Debug Information</h1>";

echo "<h2>Server Information</h2>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script Name: " . $_SERVER['SCRIPT_NAME'] . "<br>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "Query String: " . $_SERVER['QUERY_STRING'] . "<br>";
echo "HTTP Host: " . $_SERVER['HTTP_HOST'] . "<br>";

echo "<h2>Path Processing</h2>";
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$original_path = $path;
$path = trim($path, '/');

echo "Original Path: " . $original_path . "<br>";
echo "Trimmed Path: " . $path . "<br>";

// Remove base path if exists
$base_path = 'php-api';
if (strpos($path, $base_path) === 0) {
    $path = substr($path, strlen($base_path));
    $path = trim($path, '/');
    echo "After removing base path: " . $path . "<br>";
}

echo "<h2>File System</h2>";
echo "Current working directory: " . getcwd() . "<br>";
echo "This script location: " . __FILE__ . "<br>";

echo "<h2>Files Check</h2>";
$files_to_check = [
    'config/config.php',
    'config/database.php', 
    'openapi.json',
    'openapi.yaml',
    'index.php'
];

foreach ($files_to_check as $file) {
    echo $file . ": " . (file_exists($file) ? "EXISTS" : "MISSING") . "<br>";
}

echo "<h2>Config Loading Test</h2>";
try {
    if (file_exists('config/config.php')) {
        require_once 'config/config.php';
        echo "Config loaded successfully<br>";
        
        if (isset($api_settings)) {
            echo "API settings found<br>";
            echo "Exempt endpoints: " . implode(', ', $api_settings['exempt_endpoints']) . "<br>";
        } else {
            echo "API settings NOT found<br>";
        }
    } else {
        echo "Config file missing<br>";
    }
} catch (Exception $e) {
    echo "Error loading config: " . $e->getMessage() . "<br>";
}

echo "<h2>Test Links</h2>";
$base_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
echo '<a href="' . $base_url . '/">Root (/)</a><br>';
echo '<a href="' . $base_url . '/docs">Docs (/docs)</a><br>';
echo '<a href="' . $base_url . '/openapi">OpenAPI (/openapi)</a><br>';
echo '<a href="' . $base_url . '/status">Status (/status)</a><br>';
?>
