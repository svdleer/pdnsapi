<?php
require_once 'config/config.php';

header('Content-Type: text/plain');

echo "Live API Key Debug Test\n";
echo "======================\n\n";

echo "1. Environment Variables:\n";
echo "AVANT_API_KEY: " . ($_ENV['AVANT_API_KEY'] ?? 'NOT SET') . "\n";
echo "Length: " . strlen($_ENV['AVANT_API_KEY'] ?? '') . " chars\n\n";

echo "2. Incoming Request:\n";
$headers = getAllRequestHeaders();
echo "X-API-Key header: " . ($headers['X-Api-Key'] ?? 'NOT SET') . "\n";
echo "Raw headers count: " . count($headers) . "\n\n";

echo "3. API Key Extraction:\n";
$extracted_key = getApiKeyFromRequest();
echo "Extracted key: " . ($extracted_key ?? 'NULL') . "\n";
echo "Key length: " . strlen($extracted_key ?? '') . "\n\n";

echo "4. Configured API Keys:\n";
global $api_settings;
if (isset($api_settings['api_keys'])) {
    echo "Number of keys: " . count($api_settings['api_keys']) . "\n";
    foreach ($api_settings['api_keys'] as $key => $desc) {
        echo "Key: " . substr($key, 0, 10) . "...\n";
        echo "Length: " . strlen($key) . "\n";
        echo "Match with extracted: " . ($key === $extracted_key ? "YES" : "NO") . "\n\n";
    }
} else {
    echo "ERROR: api_keys not configured!\n";
}

echo "5. Validation Test:\n";
if ($extracted_key) {
    $is_valid = isValidApiKey($extracted_key);
    echo "isValidApiKey result: " . ($is_valid ? "VALID" : "INVALID") . "\n";
} else {
    echo "No key to validate\n";
}
?>
