<?php
// Production Debug Script - Check what's causing the output
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== PRODUCTION DEBUG ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Error Reporting: " . error_reporting() . "\n";

// Test 1: Check if config loads cleanly
echo "\n--- Testing config.php ---\n";
ob_start();
try {
    require_once 'config/config.php';
    $config_output = ob_get_contents();
    ob_end_clean();
    
    if (empty($config_output)) {
        echo "✅ config.php loads cleanly (no output)\n";
    } else {
        echo "❌ config.php produces output:\n";
        echo "Output length: " . strlen($config_output) . " bytes\n";
        echo "First 200 chars: " . substr($config_output, 0, 200) . "\n";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ config.php error: " . $e->getMessage() . "\n";
}

// Test 2: Check if database.php loads cleanly
echo "\n--- Testing database.php ---\n";
ob_start();
try {
    require_once 'config/database.php';
    $db_output = ob_get_contents();
    ob_end_clean();
    
    if (empty($db_output)) {
        echo "✅ database.php loads cleanly (no output)\n";
    } else {
        echo "❌ database.php produces output:\n";
        echo "Output length: " . strlen($db_output) . " bytes\n";
        echo "First 200 chars: " . substr($db_output, 0, 200) . "\n";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ database.php error: " . $e->getMessage() . "\n";
}

echo "\n=== END DEBUG ===\n";
?>
