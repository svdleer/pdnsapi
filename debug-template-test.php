<?php
echo "=== TEMPLATE SYSTEM DEBUG TEST ===" . PHP_EOL;

try {
    echo "1. Testing autoloader..." . PHP_EOL;
    require_once __DIR__ . '/php-api/config/config.php';
    echo "✅ Config loaded" . PHP_EOL;

    echo "2. Testing database connection..." . PHP_EOL;
    require_once __DIR__ . '/php-api/config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "✅ Database connected" . PHP_EOL;
    } else {
        echo "❌ Database connection failed" . PHP_EOL;
        exit(1);
    }

    echo "3. Testing Template class..." . PHP_EOL;
    require_once __DIR__ . '/php-api/models/Template.php';
    $template = new Template($db);
    echo "✅ Template class loaded" . PHP_EOL;

    echo "4. Testing basic template operations..." . PHP_EOL;
    
    // Test getAllTemplates
    $templates = $template->getAllTemplates();
    echo "✅ getAllTemplates: " . (is_array($templates) ? count($templates) . " templates" : "Failed") . PHP_EOL;
    
    // Test createTemplate
    $test_template = [
        'name' => 'Debug Template ' . time(),
        'description' => 'Debug test template',
        'records' => [
            ['name' => '@', 'type' => 'A', 'content' => '192.168.1.1', 'ttl' => 3600]
        ],
        'account_id' => 1,
        'is_active' => true
    ];
    
    $created_template = $template->createTemplate($test_template);
    if ($created_template && isset($created_template['id'])) {
        echo "✅ Template created: ID " . $created_template['id'] . PHP_EOL;
        
        // Clean up
        $template->deleteTemplate($created_template['id']);
        echo "✅ Template cleaned up" . PHP_EOL;
    } else {
        echo "❌ Template creation failed" . PHP_EOL;
    }

    echo PHP_EOL . "Debug test completed successfully!" . PHP_EOL;

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace:" . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}
?>
