<?php
echo "=== JSON POST Debug ===\n";

// Check request method
echo "Request method: " . $_SERVER['REQUEST_METHOD'] . "\n";

// Check content type
echo "Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set') . "\n";

// Check raw input
$raw_input = file_get_contents('php://input');
echo "Raw input length: " . strlen($raw_input) . "\n";
echo "Raw input: " . $raw_input . "\n";

// Try to parse JSON
$json_data = json_decode($raw_input, true);
echo "JSON parsed successfully: " . ($json_data !== null ? 'YES' : 'NO') . "\n";

if ($json_data !== null) {
    echo "JSON data:\n";
    print_r($json_data);
} else {
    echo "JSON error: " . json_last_error_msg() . "\n";
}

// Check $_POST
echo "\n\$_POST data:\n";
print_r($_POST);

// Check headers
echo "\nRelevant headers:\n";
foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'HTTP_') === 0 || strpos($key, 'CONTENT_') === 0) {
        echo "$key: $value\n";
    }
}
?>
