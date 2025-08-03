<?php
// Simple diagnostic - check each file individually
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== FILE DIAGNOSTIC ===\n";

// Test 1: Check config.php
echo "1. Testing config/config.php:\n";
ob_start();
include 'config/config.php';
$output1 = ob_get_clean();
if (strlen($output1) > 0) {
    echo "❌ Produces output (" . strlen($output1) . " bytes)\n";
    echo "Output: " . substr($output1, 0, 100) . "...\n";
} else {
    echo "✅ No output\n";
}

// Test 2: Check database.php
echo "\n2. Testing config/database.php:\n";
ob_start();
include 'config/database.php';
$output2 = ob_get_clean();
if (strlen($output2) > 0) {
    echo "❌ Produces output (" . strlen($output2) . " bytes)\n";
    echo "Output: " . substr($output2, 0, 100) . "...\n";
} else {
    echo "✅ No output\n";
}

echo "\n=== END DIAGNOSTIC ===\n";
?>
