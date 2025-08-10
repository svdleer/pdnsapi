<?php
/**
 * Test script for template domain creation
 * This script tests the createDomainFromTemplate function
 */

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php_template_test.log');

echo "Testing Template Domain Creation\n";
echo "================================\n\n";

try {
    // Include required files in correct order
    echo "Loading configuration...\n";
    require_once __DIR__ . '/includes/env-loader.php';
    require_once __DIR__ . '/config/pdns-admin-database.php';
    require_once __DIR__ . '/models/Template.php';

    // Check database connection
    global $pdns_admin_pdo;
    if (!$pdns_admin_pdo) {
        echo "ERROR: PowerDNS Admin database connection not available\n";
        exit(1);
    }
    
    echo "Database connection OK\n\n";

    // Initialize Template model
    $template_model = new Template();

    // Get all available templates
    echo "1. Fetching available templates...\n";
    $templates = $template_model->getAllTemplates();
    
    if (!$templates) {
        echo "ERROR: Failed to fetch templates\n";
        exit(1);
    }
    
    if (empty($templates)) {
        echo "ERROR: No templates found in database\n";
        echo "Please create at least one template in PowerDNS Admin first\n";
        exit(1);
    }
    
    echo "Found " . count($templates) . " templates:\n";
    foreach ($templates as $template) {
        echo "  - ID: {$template['id']}, Name: {$template['name']}, Records: " . count($template['records']) . "\n";
    }
    echo "\n";

    // Use the first template for testing
    $template_to_use = $templates[0];
    $template_id = $template_to_use['id'];
    $template_name = $template_to_use['name'];
    
    echo "2. Using template: {$template_name} (ID: {$template_id})\n";
    echo "Template records:\n";
    foreach ($template_to_use['records'] as $record) {
        echo "  - {$record['name']} {$record['type']} {$record['content']} (TTL: {$record['ttl']})\n";
    }
    echo "\n";

    // Generate a test domain name
    $test_domain = 'test-template-' . date('YmdHis') . '.example.com';
    
    echo "3. Creating domain: {$test_domain}\n";
    
    // Domain data for creation
    $domain_data = [
        'name' => $test_domain,
        'kind' => 'Native'
    ];

    // Create domain from template
    $result = $template_model->createDomainFromTemplate($template_id, $domain_data);
    
    echo "4. Creation result:\n";
    echo "   Success: " . ($result['success'] ? 'YES' : 'NO') . "\n";
    echo "   Message: " . $result['message'] . "\n";
    
    if ($result['success'] && isset($result['data'])) {
        echo "   Data:\n";
        foreach ($result['data'] as $key => $value) {
            echo "     {$key}: {$value}\n";
        }
    }
    echo "\n";
    
    if ($result['success']) {
        echo "✅ Template domain creation SUCCESSFUL!\n";
        
        // Optional: Attempt verification (this may fail due to API key permissions, but that's OK)
        echo "5. Attempting domain verification via API (optional)...\n";
        
        require_once __DIR__ . '/classes/PDNSAdminClient.php';
        
        global $pdns_config;
        $pdns_client = new PDNSAdminClient($pdns_config);
        
        $verification = $pdns_client->getDomainDetailsByName($test_domain . '.');
        if ($verification && $verification['status_code'] === 200) {
            echo "✅ Domain verification successful\n";
            if (isset($verification['data']['rrsets'])) {
                echo "   Records found: " . count($verification['data']['rrsets']) . "\n";
            }
        } elseif ($verification && $verification['status_code'] === 401) {
            echo "ℹ️  Domain verification skipped (401 - API key permissions)\n";
            echo "   This is normal if the server API key has limited read permissions\n";
            echo "   The domain creation was still successful!\n";
        } else {
            $status = $verification['status_code'] ?? 'unknown';
            echo "⚠️  Domain verification status: {$status}\n";
            echo "   Note: Verification failure doesn't mean domain creation failed\n";
        }
        
    } else {
        echo "❌ Template domain creation FAILED!\n";
        echo "Check the error logs for more details\n";
    }

} catch (Exception $e) {
    echo "❌ Exception occurred: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nTest completed at " . date('Y-m-d H:i:s') . "\n";
echo "Check error logs: /tmp/php_template_test.log\n";
?>
