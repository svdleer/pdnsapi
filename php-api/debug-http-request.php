<?php
require_once 'classes/PDNSAdminClient.php';
require_once 'config/config.php';

echo "=== HTTP Request Debug ===\n\n";

$client = new PDNSAdminClient($pdns_config);

// Test the problematic endpoint
$test_url = '/servers/1/zones';
echo "Testing endpoint: $test_url\n";

// Get the selected key for this endpoint
$reflection = new ReflectionClass($client);
$method = $reflection->getMethod('isServerEndpoint');
$method->setAccessible(true);
$use_server_key = $method->invoke($client, $test_url);

$selected_key = $use_server_key ? $pdns_config['pdns_server_key'] : $pdns_config['api_key'];

echo "Uses server key: " . ($use_server_key ? 'Yes' : 'No') . "\n";
echo "Selected API key: '$selected_key'\n";
echo "Key length: " . strlen($selected_key) . " characters\n";
echo "Key contains special chars: " . (preg_match('/[^a-zA-Z0-9]/', $selected_key) ? 'Yes' : 'No') . "\n";

// Check if key looks like base64
$is_base64_like = (bool) preg_match('/^[a-zA-Z0-9+\/]*={0,2}$/', $selected_key);
echo "Key looks like base64: " . ($is_base64_like ? 'Yes' : 'No') . "\n";

// Test if key is valid base64
$decoded = base64_decode($selected_key, true);
echo "Key is valid base64: " . ($decoded !== false ? 'Yes' : 'No') . "\n";

echo "\n=== Raw HTTP Headers ===\n";

// Create a test curl request to see exactly what headers are sent
$full_url = $pdns_config['base_url'] . $test_url;
echo "Full URL: $full_url\n\n";

// Prepare headers
$headers = [
    'Content-Type: application/json',
    'X-API-KEY: ' . $selected_key
];

echo "Headers to be sent:\n";
foreach ($headers as $header) {
    echo "  $header\n";
}

echo "\n=== cURL Debug ===\n";

// Create curl handle with verbose output
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $full_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_STDERR, fopen('php://output', 'w'));
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

echo "Executing request...\n";
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "\n=== Response ===\n";
echo "HTTP Code: $http_code\n";
if ($error) {
    echo "cURL Error: $error\n";
}
echo "Response: $response\n";

echo "\n=== Key Analysis ===\n";
echo "Raw key bytes: ";
for ($i = 0; $i < strlen($selected_key); $i++) {
    echo sprintf('%02x ', ord($selected_key[$i]));
}
echo "\n";

echo "Key as escaped string: " . addslashes($selected_key) . "\n";
?>
