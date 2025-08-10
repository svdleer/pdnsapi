<?php
/**
 * Test script for template domain creation
 * Run this to debug the createDomainFromTemplate function
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set up the environment
$base_path = __DIR__;
require_once $base_path . '/includes/env-loader.php';
require_once $base_path . '/config/config.php';
require_once $base_path . '/config/pdns-admin-database.php';
require_once $base_path . '/models/Template.php';

echo "=== Template Domain Creation Test ===\n\n";

// Test database connection first
echo "Testing database connection...\n";
global $pdns_admin_pdo;
if ($pdns_admin_pdo) {
    echo "✅ PowerDNS Admin database connected successfully.\n";
    
    // Test a simple query
    try {
        $stmt = $pdns_admin_pdo->query("SELECT COUNT(*) as count FROM domain_template");
        $result = $stmt->fetch();
        echo "Found {$result['count']} templates in database.\n";
    } catch (Exception $e) {
        echo "❌ Database query failed: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "❌ PowerDNS Admin database connection failed.\n";
    exit(1);
}

try {
    // Initialize Template model
    $template_model = new Template($pdns_admin_pdo);
    
    // Test 1: List all templates
    echo "\n1. Getting all templates...\n";
    $templates = $template_model->getAllTemplates();
    
    if ($templates && count($templates) > 0) {
        echo "Found " . count($templates) . " templates:\n";
        foreach ($templates as $template) {
            echo "  - ID: {$template['id']}, Name: {$template['name']}, Records: " . count($template['records']) . "\n";
        }
    } else {
        echo "No templates found or error retrieving templates.\n";
        exit(1);
    }
    
    // Test 2: Get a specific template (use the first one)
    $template_id = $templates[0]['id'];
    echo "\n2. Testing template ID: {$template_id}\n";
    
    $template = $template_model->getTemplate($template_id);
    if ($template) {
        echo "Template details:\n";
        echo "  Name: {$template['name']}\n";
        echo "  Description: " . ($template['description'] ?? 'None') . "\n";
        echo "  Records:\n";
        foreach ($template['records'] as $record) {
            echo "    {$record['name']} {$record['type']} {$record['content']} (TTL: {$record['ttl']})\n";
        }
    } else {
        echo "Failed to get template details.\n";
        exit(1);
    }
    
    // Test 3: Create a test domain from template
    $test_domain_name = 'test-template-' . date('YmdHis') . '.example.com';
    echo "\n3. Creating test domain: {$test_domain_name}\n";
    
    $domain_data = [
        'name' => $test_domain_name,
        'kind' => 'Native'
    ];
    
    echo "Starting domain creation...\n";
    $result = $template_model->createDomainFromTemplate($template_id, $domain_data);
    
    echo "Result:\n";
    echo "  Success: " . ($result['success'] ? 'YES' : 'NO') . "\n";
    echo "  Message: {$result['message']}\n";
    
    if (isset($result['data'])) {
        echo "  Data:\n";
        foreach ($result['data'] as $key => $value) {
            if (is_array($value)) {
                echo "    {$key}: " . json_encode($value) . "\n";
            } else {
                echo "    {$key}: {$value}\n";
            }
        }
    }
    
    if (!$result['success']) {
        echo "\n❌ Domain creation failed!\n";
        echo "Check the logs above for detailed error information.\n";
    } else {
        echo "\n✅ Domain creation successful!\n";
        
        // Optional: Clean up test domain
        echo "\nNote: Test domain '{$test_domain_name}' was created. You may want to delete it manually if this was just a test.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception occurred: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
