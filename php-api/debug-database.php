<?php
// Debug script to test Database class loading
echo "Testing Database class loading...\n";
echo "Current working directory: " . getcwd() . "\n";
echo "__DIR__ value: " . __DIR__ . "\n";

// Test the include path
$database_path = __DIR__ . '/config/database.php';
echo "Database file path: " . $database_path . "\n";
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
}

// Test from api subdirectory perspective
$api_database_path = __DIR__ . '/../config/database.php';
echo "\nFrom API directory perspective:\n";
echo "API database path: " . $api_database_path . "\n";
echo "API database file exists: " . (file_exists($api_database_path) ? 'YES' : 'NO') . "\n";
?>
