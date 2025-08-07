<?php
/**
 * Class Autoloader
 * Ensures all required classes are available
 */

function ensureClassesLoaded() {
    // Determine base path
    $base_paths = [
        realpath(__DIR__ . '/..'),
        __DIR__ . '/..',
        dirname(__FILE__) . '/..',
        realpath(dirname(__FILE__) . '/..')
    ];
    
    $base_path = null;
    foreach ($base_paths as $path) {
        if ($path && is_dir($path . '/config')) {
            $base_path = $path;
            break;
        }
    }
    
    if (!$base_path) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Unable to locate base directory',
            'debug' => [
                'tried_paths' => $base_paths,
                'current_dir' => getcwd(),
                '__DIR__' => __DIR__
            ]
        ]);
        exit;
    }
    
    // Required files
    $required_files = [
        '/includes/env-loader.php',  // Load environment variables first
        '/config/config.php',
        '/config/database.php'
    ];
    
    foreach ($required_files as $file) {
        $full_path = $base_path . $file;
        if (file_exists($full_path)) {
            require_once $full_path;
        }
    }
    
    // Verify Database class is available
    if (!class_exists('Database')) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Database class not found after includes',
            'debug' => [
                'base_path' => $base_path,
                'database_file' => $base_path . '/config/database.php',
                'file_exists' => file_exists($base_path . '/config/database.php'),
                'included_files' => get_included_files()
            ]
        ]);
        exit;
    }
    
    return $base_path;
}

// Actually call the function to ensure classes are loaded
ensureClassesLoaded();
?>
