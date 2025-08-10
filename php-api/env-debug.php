<?php
echo "=== Environment Debug ===\n";
echo "Current working directory: " . getcwd() . "\n";
echo ".env file exists: " . (file_exists('.env') ? 'YES' : 'NO') . "\n";
echo ".env file exists in parent: " . (file_exists('../.env') ? 'YES' : 'NO') . "\n";

// Test loading env manually
echo "\n=== Manual .env loading test ===\n";
if (file_exists('.env')) {
    echo ".env file contents (first 5 lines):\n";
    $lines = file('.env', FILE_IGNORE_NEW_LINES);
    for ($i = 0; $i < min(10, count($lines)); $i++) {
        if (trim($lines[$i]) && strpos(trim($lines[$i]), '#') !== 0) {
            echo "Line " . ($i+1) . ": " . $lines[$i] . "\n";
        }
    }
    
    // Try loading manually
    echo "\nLoading manually...\n";
    $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if ($key === 'AVANT_API_KEY') {
                echo "Found AVANT_API_KEY: " . substr($value, 0, 20) . "... (length: " . strlen($value) . ")\n";
                $_ENV[$key] = $value;
                putenv("$key=$value");
                break;
            }
        }
    }
}

echo "\nAfter manual loading:\n";
echo "AVANT_API_KEY: " . ($_ENV['AVANT_API_KEY'] ?? 'NOT SET') . "\n";
echo "Full length: " . strlen($_ENV['AVANT_API_KEY'] ?? '') . "\n";

echo "\nEnvironment variables (relevant ones):\n";
foreach ($_ENV as $key => $value) {
    if (strpos($key, 'AVANT') !== false || strpos($key, 'API') !== false || strpos($key, 'PDNS') !== false) {
        echo "$key: " . (strlen($value) > 50 ? substr($value, 0, 20) . '...' : $value) . "\n";
    }
}
?>
