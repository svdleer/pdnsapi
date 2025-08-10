<?php
echo "=== Simple AVANT_API_KEY Test ===\n";

// Check if .env exists
if (!file_exists('.env')) {
    echo "ERROR: .env file not found\n";
    exit;
}

// Read all lines
$lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
echo "Total lines in .env: " . count($lines) . "\n";

$found = false;
foreach ($lines as $lineNum => $line) {
    if (strpos(trim($line), '#') === 0) continue; // Skip comments
    if (strpos($line, 'AVANT_API_KEY') !== false) {
        echo "Found AVANT_API_KEY on line " . ($lineNum + 1) . ": " . $line . "\n";
        
        // Parse it
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            echo "Parsed - Key: '$key', Value length: " . strlen($value) . "\n";
            echo "Value preview: " . substr($value, 0, 30) . "...\n";
            
            // Set it
            $_ENV[$key] = $value;
            putenv("$key=$value");
            echo "Set in environment\n";
        }
        $found = true;
        break;
    }
}

if (!$found) {
    echo "AVANT_API_KEY not found in .env file\n";
    echo "Showing all lines containing 'API':\n";
    foreach ($lines as $lineNum => $line) {
        if (stripos($line, 'API') !== false) {
            echo "Line " . ($lineNum + 1) . ": $line\n";
        }
    }
} else {
    echo "Final check - AVANT_API_KEY in \$_ENV: " . ($_ENV['AVANT_API_KEY'] ?? 'NOT SET') . "\n";
}
?>
