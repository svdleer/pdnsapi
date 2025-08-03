<?php
/**
 * Simple setup script to test the API configuration
 */

echo "PDNSAdmin PHP API Setup Script\n";
echo "==============================\n\n";

// Check PHP version
echo "1. Checking PHP version...\n";
if (version_compare(PHP_VERSION, '7.4.0') >= 0) {
    echo "   ✓ PHP version: " . PHP_VERSION . " (OK)\n";
} else {
    echo "   ✗ PHP version: " . PHP_VERSION . " (Requires 7.4+)\n";
    exit(1);
}

// Check required extensions
echo "\n2. Checking required PHP extensions...\n";
$required_extensions = ['pdo', 'pdo_mysql', 'curl', 'json'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "   ✓ {$ext} extension loaded\n";
    } else {
        echo "   ✗ {$ext} extension missing\n";
        $missing_extensions[] = $ext;
    }
}

if (!empty($missing_extensions)) {
    echo "\nPlease install missing extensions: " . implode(', ', $missing_extensions) . "\n";
    exit(1);
}

// Check database configuration
echo "\n3. Testing database connection...\n";
try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "   ✓ Database connection successful\n";
        
        // Check if tables exist
        $tables = ['accounts', 'domains', 'api_logs', 'domain_sync'];
        foreach ($tables as $table) {
            $stmt = $db->query("SHOW TABLES LIKE '{$table}'");
            if ($stmt->rowCount() > 0) {
                echo "   ✓ Table '{$table}' exists\n";
            } else {
                echo "   ✗ Table '{$table}' missing\n";
                $missing_tables[] = $table;
            }
        }
        
        if (!empty($missing_tables)) {
            echo "\nPlease import database/schema.sql to create missing tables\n";
        }
    } else {
        echo "   ✗ Database connection failed\n";
    }
} catch (Exception $e) {
    echo "   ✗ Database error: " . $e->getMessage() . "\n";
}

// Test PDNSAdmin connection
echo "\n4. Testing PDNSAdmin connection...\n";
try {
    require_once 'config/config.php';
    require_once 'classes/PDNSAdminClient.php';
    
    $pdns_client = new PDNSAdminClient($pdns_config);
    $response = $pdns_client->getAllDomains();
    
    if ($response['status_code'] == 200) {
        echo "   ✓ PDNSAdmin connection successful\n";
        echo "   ✓ Found " . count($response['data']) . " domains in PDNSAdmin\n";
    } else {
        echo "   ✗ PDNSAdmin connection failed (HTTP " . $response['status_code'] . ")\n";
        if (isset($response['data']['error'])) {
            echo "     Error: " . $response['data']['error'] . "\n";
        }
    }
} catch (Exception $e) {
    echo "   ✗ PDNSAdmin error: " . $e->getMessage() . "\n";
}

echo "\n5. Directory permissions...\n";
$writable_dirs = ['config', 'api', 'models', 'classes'];
foreach ($writable_dirs as $dir) {
    if (is_readable($dir)) {
        echo "   ✓ {$dir} directory readable\n";
    } else {
        echo "   ✗ {$dir} directory not readable\n";
    }
}

echo "\nSetup complete!\n\n";
echo "Next steps:\n";
echo "1. Configure your web server to serve the php-api directory\n";
echo "2. Access the API documentation at http://your-domain/\n";
echo "3. Test the API endpoints as described in README.md\n";
?>
