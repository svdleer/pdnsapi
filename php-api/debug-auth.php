<?php
require_once 'config/config.php';

echo "=== API Key Debug Information ===\n";
echo "AVANT_API_KEY from env: " . ($_ENV['AVANT_API_KEY'] ?? 'NOT SET') . "\n";
echo "Expected API key: " . ($_ENV['AVANT_API_KEY'] ?? 'your-api-key-here') . "\n";

global $api_settings;
echo "\nConfigured API keys:\n";
foreach ($api_settings['api_keys'] as $key => $desc) {
    echo "- Key: " . substr($key, 0, 10) . "... (Length: " . strlen($key) . ") -> $desc\n";
}

echo "\nTesting authentication headers:\n";
$headers = getAllRequestHeaders();
echo "All headers:\n";
foreach ($headers as $name => $value) {
    if (stripos($name, 'auth') !== false || stripos($name, 'api') !== false) {
        echo "- $name: $value\n";
    }
}

$api_key = getApiKeyFromRequest();
echo "\nExtracted API key: " . ($api_key ? substr($api_key, 0, 10) . "... (Length: " . strlen($api_key) . ")" : 'NONE') . "\n";

if ($api_key) {
    $is_valid = isValidApiKey($api_key);
    echo "Is valid: " . ($is_valid ? 'YES' : 'NO') . "\n";
}

// Check IP allowlist
echo "\nIP Check:\n";
$client_ip = getClientIpAddress();
echo "Client IP: $client_ip\n";
$ip_allowed = isIpAllowed();
echo "IP Allowed: " . ($ip_allowed ? 'YES' : 'NO') . "\n";
?>
