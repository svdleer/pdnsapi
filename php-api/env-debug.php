<?php
echo "=== Environment Debug ===\n";
echo "Current working directory: " . getcwd() . "\n";
echo ".env file exists: " . (file_exists('.env') ? 'YES' : 'NO') . "\n";
echo ".env file exists in parent: " . (file_exists('../.env') ? 'YES' : 'NO') . "\n";

if (file_exists('.env')) {
    echo ".env file contents (first 5 lines):\n";
    $lines = file('.env', FILE_IGNORE_NEW_LINES);
    for ($i = 0; $i < min(5, count($lines)); $i++) {
        echo "Line " . ($i+1) . ": " . $lines[$i] . "\n";
    }
}

echo "\nEnvironment variables:\n";
foreach ($_ENV as $key => $value) {
    if (strpos($key, 'AVANT') !== false || strpos($key, 'API') !== false || strpos($key, 'PDNS') !== false) {
        echo "$key: " . (strlen($value) > 50 ? substr($value, 0, 20) . '...' : $value) . "\n";
    }
}
?>
