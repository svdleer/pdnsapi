<?php
// Debug script to test Database class loading from API directory
echo "Testing Database class loading from API directory...\n";
echo "Current working directory: " . getcwd() . "\n";
echo "__DIR__ value: " . __DIR__ . "\n";

// Test the include path
$database_path = __DIR__ . '/../config/database.php';
echo "Database file path: " . $database_path . "\n";
echo "Resolved path: " . realpath($database_path) . "\n";
echo "Database file exists: " . (file_exists($database_path) ? 'YES' : 'NO') . "\n";

if (file_exists($database_path)) {
    echo "Database file is readable: " . (is_readable($database_path) ? 'YES' : 'NO') . "\n";
    
    // Try to include it
    try {
        require_once $database_path;
        echo "Database file included successfully\n";
        
        // Try to instantiate the class
        if (class_exists('Database')) {
            echo "Database class exists\n";
            $db = new Database();
            echo "Database class instantiated successfully\n";
        } else {
            echo "ERROR: Database class not found after include\n";
        }
    } catch (Exception $e) {
        echo "ERROR including database file: " . $e->getMessage() . "\n";
    }
} else {
    echo "ERROR: Database file does not exist\n";
    
    // Let's check what files do exist
    $config_dir = __DIR__ . '/../config/';
    echo "Config directory path: " . $config_dir . "\n";
    echo "Config directory exists: " . (is_dir($config_dir) ? 'YES' : 'NO') . "\n";
    
    if (is_dir($config_dir)) {
        echo "Files in config directory:\n";
        $files = scandir($config_dir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                echo "  - $file\n";
            }
        }
    }
}
?>
