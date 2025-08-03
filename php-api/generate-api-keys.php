<?php
/**
 * API Key Generator
 * 
 * Generates secure API keys for the PDNSAdmin PHP API
 */

function generateApiKey($length = 32) {
    return bin2hex(random_bytes($length));
}

function generateMultipleKeys($count = 5, $length = 32) {
    $keys = [];
    for ($i = 0; $i < $count; $i++) {
        $keys[] = generateApiKey($length);
    }
    return $keys;
}

// Generate keys
echo "PDNSAdmin PHP API - Secure API Key Generator\n";
echo "==========================================\n\n";

echo "Single API Key (64 characters):\n";
echo generateApiKey(32) . "\n\n";

echo "Multiple API Keys:\n";
$keys = generateMultipleKeys(3, 32);
foreach ($keys as $index => $key) {
    echo "Key " . ($index + 1) . ": " . $key . "\n";
}

echo "\nTo use these keys:\n";
echo "1. Copy the keys to your config/config.php file\n";
echo "2. Update the 'api_keys' array with your chosen keys\n";
echo "3. Use the keys in your API requests:\n";
echo "   - Header: X-API-Key: your-key-here\n";
echo "   - Bearer: Authorization: Bearer your-key-here\n";
echo "   - Query: ?api_key=your-key-here (dev only)\n\n";

echo "Example config entry:\n";
echo "'api_keys' => [\n";
foreach ($keys as $index => $key) {
    $description = ($index === 0) ? 'Production API Key' : 'Development Key ' . $index;
    echo "    '" . $key . "' => '" . $description . "',\n";
}
echo "],\n";
?>
