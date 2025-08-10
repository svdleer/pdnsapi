<?php
echo "=== Config Loading Test ===\n";

echo "Step 1: Before including anything\n";
echo "AVANT_API_KEY in \$_ENV: " . ($_ENV['AVANT_API_KEY'] ?? 'NOT SET') . "\n";

echo "\nStep 2: Including env-loader directly\n";
require_once __DIR__ . '/includes/env-loader.php';
echo "AVANT_API_KEY after env-loader: " . ($_ENV['AVANT_API_KEY'] ?? 'NOT SET') . "\n";

echo "\nStep 3: Including config.php\n";
require_once __DIR__ . '/config/config.php';
echo "AVANT_API_KEY after config: " . ($_ENV['AVANT_API_KEY'] ?? 'NOT SET') . "\n";

global $api_settings;
echo "\nStep 4: Checking API keys in config\n";
if (isset($api_settings['api_keys'])) {
    echo "Number of configured API keys: " . count($api_settings['api_keys']) . "\n";
    foreach ($api_settings['api_keys'] as $key => $desc) {
        echo "- Key: " . substr($key, 0, 10) . "... (Length: " . strlen($key) . ") -> $desc\n";
    }
} else {
    echo "api_settings['api_keys'] not set\n";
}
?>
