<?php
// Test the accounts.php includes
echo "Testing accounts.php includes...\n";

$base_path = realpath(__DIR__ . '/..');
echo "Base path: $base_path\n";

$files_to_test = [
    'config/config.php',
    'config/database.php',
    'models/Account.php',
    'classes/PDNSAdminClient.php'
];

foreach ($files_to_test as $file) {
    $full_path = $base_path . '/' . $file;
    echo "Testing: $full_path\n";
    echo "  Exists: " . (file_exists($full_path) ? 'YES' : 'NO') . "\n";
    if (file_exists($full_path)) {
        echo "  Readable: " . (is_readable($full_path) ? 'YES' : 'NO') . "\n";
    }
}

// Try to include them
try {
    require_once $base_path . '/config/config.php';
    echo "config.php included successfully\n";
    
    require_once $base_path . '/config/database.php';
    echo "database.php included successfully\n";
    
    // Test Database class
    if (class_exists('Database')) {
        $database = new Database();
        echo "Database class instantiated successfully\n";
    } else {
        echo "ERROR: Database class not found\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
