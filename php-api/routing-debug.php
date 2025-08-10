<?php
echo "=== Routing Debug ===\n";

$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = trim($path, '/');

echo "Request URI: $request_uri\n";
echo "Parsed path: '$path'\n";

// Remove base path if exists
$base_path = 'php-api';
if (strpos($path, $base_path) === 0) {
    $path = substr($path, strlen($base_path));
    $path = trim($path, '/');
    echo "After removing base path: '$path'\n";
}

// Test patterns
$patterns = [
    '/^(api\/)?templates\/(\d+)\/create-domain$/' => 'Templates pattern',
    '/^(api\/)?ip-allowlist(\/.*)?$/' => 'IP allowlist pattern',
];

foreach ($patterns as $pattern => $name) {
    if (preg_match($pattern, $path, $matches)) {
        echo "✅ MATCHES: $name\n";
        echo "Matches: " . print_r($matches, true) . "\n";
    } else {
        echo "❌ NO MATCH: $name\n";
    }
}

// Test specific cases
$test_paths = [
    'api/ip-allowlist',
    'ip-allowlist', 
    'api/ip-allowlist/test',
    'ip-allowlist/123',
];

echo "\nTesting specific paths:\n";
foreach ($test_paths as $test_path) {
    echo "Testing: '$test_path' -> ";
    if (preg_match('/^(api\/)?ip-allowlist(\/.*)?$/', $test_path, $matches)) {
        echo "MATCH (remaining: '" . ($matches[2] ?? '') . "')\n";
    } else {
        echo "NO MATCH\n";
    }
}
?>
