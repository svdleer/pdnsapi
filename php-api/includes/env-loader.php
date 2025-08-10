<?php
/**
 * Simple .env file loader
 * Loads environment variables from .env file
 */
function loadEnv($filePath) {
    if (!file_exists($filePath)) {
        return false;
    }
    
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    if ($lines === false) {
        return false;
    }
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            if (preg_match('/^"(.*)"$/', $value, $matches)) {
                $value = $matches[1];
            } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
                $value = $matches[1];
            }
            
            // Set environment variable
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
    
    return true;
}

// Load .env file from project root (parent of php-api)
$env_path = dirname(dirname(__DIR__)) . '/.env';

if (file_exists($env_path)) {
    loadEnv($env_path);
} else {
    // Fallback: try php-api directory
    $env_path_fallback = dirname(__DIR__) . '/.env';
    if (file_exists($env_path_fallback)) {
        loadEnv($env_path_fallback);
    }
}
?>
