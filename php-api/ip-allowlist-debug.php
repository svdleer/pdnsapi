<?php
echo "=== IP Allowlist Debug ===\n";

try {
    echo "1. Testing basic includes...\n";
    $base_path = realpath(__DIR__ . '/..');
    echo "Base path: $base_path\n";
    
    require_once $base_path . '/config/config.php';
    echo "✅ Config loaded\n";
    
    require_once $base_path . '/config/database.php';
    echo "✅ Database config loaded\n";
    
    require_once $base_path . '/includes/database-compat.php';
    echo "✅ Database compat loaded\n";
    
    echo "\n2. Testing database connection...\n";
    if (!class_exists('Database')) {
        echo "❌ Database class not available\n";
    } else {
        echo "✅ Database class exists\n";
        
        $db = Database::getInstance();
        echo "✅ Database instance created\n";
        
        $conn = $db->getConnection();
        if (!$conn) {
            echo "❌ Database connection failed\n";
        } else {
            echo "✅ Database connection successful\n";
            
            // Test the ip_allowlist table
            $query = "SELECT COUNT(*) as count FROM ip_allowlist";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "✅ IP allowlist table accessible, " . $result['count'] . " entries\n";
        }
    }
    
    echo "\n3. Testing authentication functions...\n";
    $api_key = getApiKeyFromRequest();
    echo "API key from request: " . ($api_key ? substr($api_key, 0, 10) . "..." : "NONE") . "\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>
