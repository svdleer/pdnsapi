<?php
header('Content-Type: text/plain');

echo "Apache Headers Debug\n";
echo "===================\n\n";

echo "1. \$_SERVER variables containing HTTP_:\n";
foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'HTTP_') === 0) {
        echo "$key = $value\n";
    }
}

echo "\n2. Raw \$_SERVER dump (partial):\n";
$relevant_keys = ['HTTP_X_API_KEY', 'HTTP_AUTHORIZATION', 'REQUEST_METHOD', 'REQUEST_URI'];
foreach ($relevant_keys as $key) {
    echo "$key = " . ($_SERVER[$key] ?? 'NOT SET') . "\n";
}

echo "\n3. getallheaders() test:\n";
if (function_exists('getallheaders')) {
    $headers = getallheaders();
    echo "Available headers:\n";
    foreach ($headers as $name => $value) {
        if (stripos($name, 'api') !== false || stripos($name, 'auth') !== false) {
            echo "$name: $value\n";
        }
    }
} else {
    echo "getallheaders() not available\n";
}

echo "\n4. Test our getAllRequestHeaders() function:\n";
require_once 'config/config.php';
$headers = getAllRequestHeaders();
echo "Number of headers: " . count($headers) . "\n";
foreach ($headers as $name => $value) {
    if (stripos($name, 'api') !== false || stripos($name, 'auth') !== false) {
        echo "$name: $value\n";
    }
}
?>
