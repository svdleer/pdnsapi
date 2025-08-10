<?php
echo "=== Routing Debug ===\n";

echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'not set') . "\n";
echo "PATH_INFO: " . ($_SERVER['PATH_INFO'] ?? 'not set') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'not set') . "\n";
echo "PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'not set') . "\n";

// Parse the path like index.php does
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = trim($path, '/');

echo "Parsed path: '$path'\n";

// Remove base path if exists
$base_path = 'php-api';
if (strpos($path, $base_path) === 0) {
    $path = substr($path, strlen($base_path));
    $path = trim($path, '/');
}

echo "After base path removal: '$path'\n";

// Test our regex pattern
if (preg_match('/^(api\/)?ip-allowlist(\/.*)?$/', $path, $matches)) {
    echo "IP allowlist pattern MATCHED!\n";
    echo "Match 1 (api prefix): '" . ($matches[1] ?? '') . "'\n";
    echo "Match 2 (remaining path): '" . ($matches[2] ?? '') . "'\n";
    
    $remaining_path = $matches[2] ?? '';
    echo "Remaining path to set as PATH_INFO: '$remaining_path'\n";
} else {
    echo "IP allowlist pattern did NOT match\n";
}
?>
