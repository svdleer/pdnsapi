<?php
// Database.php diagnostic script
echo "=== DATABASE.PHP DIAGNOSTIC ===\n\n";

$base_path = '/opt/web/pdnsapi.avant.nl/php-api';
$database_file = $base_path . '/config/database.php';

echo "Looking for: $database_file\n\n";

if (!file_exists($database_file)) {
    echo "❌ File does not exist!\n";
    
    // Check if directory exists
    $config_dir = dirname($database_file);
    echo "Config directory ($config_dir) exists: " . (is_dir($config_dir) ? "YES" : "NO") . "\n";
    
    if (is_dir($config_dir)) {
        echo "Files in config directory:\n";
        $files = scandir($config_dir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                echo "  - $file\n";
            }
        }
    }
    exit;
}

echo "✅ File exists\n";
echo "File size: " . filesize($database_file) . " bytes\n";
echo "Readable: " . (is_readable($database_file) ? "YES" : "NO") . "\n";
echo "Writable: " . (is_writable($database_file) ? "YES" : "NO") . "\n\n";

echo "=== FILE CONTENTS ===\n";
$contents = file_get_contents($database_file);
echo $contents;
echo "\n\n=== TRYING TO INCLUDE FILE ===\n";

// Try to include
try {
    include_once $database_file;
    echo "✅ File included successfully\n";
} catch (ParseError $e) {
    echo "❌ Parse Error: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getLine() . "\n";
} catch (Error $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n=== CLASS CHECK ===\n";
echo "Database class exists: " . (class_exists('Database') ? "YES" : "NO") . "\n";

if (class_exists('Database')) {
    echo "✅ Database class is available!\n";
    try {
        $db = new Database();
        echo "✅ Database class can be instantiated!\n";
    } catch (Exception $e) {
        echo "❌ Error instantiating Database class: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Database class not found\n";
    echo "Available classes containing 'database':\n";
    $classes = get_declared_classes();
    foreach ($classes as $class) {
        if (stripos($class, 'database') !== false) {
            echo "  - $class\n";
        }
    }
}

echo "\n=== INCLUDED FILES ===\n";
$included = get_included_files();
foreach ($included as $file) {
    if (strpos($file, 'database') !== false) {
        echo "  - $file\n";
    }
}
?>
