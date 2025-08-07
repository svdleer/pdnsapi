<?php
/**
 * Debug Domain Creation - PowerDNS Admin API Testing
 * 
 * This script tests the actual API calls being made to PowerDNS Admin
 * to diagnose the domain creation issue
 */

require_once 'php-api/config/config.php';
require_once 'php-api/config/database.php';
require_once 'php-api/includes/database-compat.php';
require_once 'php-api/classes/PDNSAdminClient.php';

echo "🔍 DEBUG: PowerDNS Admin Domain Creation\n";
echo "=========================================\n\n";

// Initialize PDNSAdmin client
$pdns_client = new PDNSAdminClient($pdns_config);

echo "📋 Configuration Details:\n";
echo "- PowerDNS Admin URL: {$pdns_config['base_url']}\n";
echo "- Auth Type: {$pdns_config['auth_type']}\n";
echo "- API Key (first 10 chars): " . substr($pdns_config['api_key'], 0, 10) . "...\n\n";

// Test 1: Test connection by getting existing zones
echo "TEST 1: Testing PowerDNS Admin API Connection\n";
echo "==============================================\n";

$test_response = $pdns_client->makeRequest('/pdnsadmin/zones', 'GET');
echo "Status Code: {$test_response['status_code']}\n";

if ($test_response['status_code'] === 200) {
    $zone_count = count($test_response['data'] ?? []);
    echo "✅ Connection successful! Found $zone_count zones.\n";
    
    // Show first 3 zones as examples
    if (!empty($test_response['data']) && is_array($test_response['data'])) {
        echo "Sample zones:\n";
        foreach (array_slice($test_response['data'], 0, 3) as $zone) {
            echo "  - {$zone['name']} (ID: {$zone['id']})\n";
        }
    }
} else {
    echo "❌ Connection failed!\n";
    echo "Response: " . ($test_response['raw_response'] ?? 'No response') . "\n";
    
    // Check if it's an authentication issue
    if ($test_response['status_code'] === 401) {
        echo "🔍 Authentication issue detected. Checking credentials...\n";
        
        // Try to decode the API key
        $decoded_key = base64_decode($pdns_config['api_key']);
        echo "Decoded API key: $decoded_key\n";
    }
}

echo "\n";

// Test 2: Test domain creation with minimal data
echo "TEST 2: Testing Domain Creation\n";
echo "===============================\n";

$timestamp = time();
$test_domain = "debug-test-$timestamp.example.com";

echo "Creating test domain: $test_domain\n";

$domain_data = [
    'name' => $test_domain,
    'kind' => 'Master',
    'type' => 'Native'
];

echo "Domain data to send:\n";
echo json_encode($domain_data, JSON_PRETTY_PRINT) . "\n\n";

$create_response = $pdns_client->makeRequest('/pdnsadmin/zones', 'POST', $domain_data);

echo "Create Response:\n";
echo "Status Code: {$create_response['status_code']}\n";
echo "Raw Response: " . ($create_response['raw_response'] ?? 'No response') . "\n";

if ($create_response['data']) {
    echo "Parsed Data:\n";
    echo json_encode($create_response['data'], JSON_PRETTY_PRINT) . "\n";
}

// Test 3: Try alternative endpoint
echo "\nTEST 3: Testing Alternative Endpoint\n";
echo "=====================================\n";

$alt_response = $pdns_client->makeRequest('/servers/1/zones', 'POST', $domain_data);

echo "Alternative Endpoint Response:\n";
echo "Status Code: {$alt_response['status_code']}\n";
echo "Raw Response: " . ($alt_response['raw_response'] ?? 'No response') . "\n";

if ($alt_response['data']) {
    echo "Parsed Data:\n";
    echo json_encode($alt_response['data'], JSON_PRETTY_PRINT) . "\n";
}

// Test 4: Check template functionality
echo "\nTEST 4: Testing Template Conversion\n";
echo "===================================\n";

// Get PowerDNS Admin database connection
$pdns_admin_conn = null;
if (class_exists('PDNSAdminDatabase')) {
    $pdns_admin_db = new PDNSAdminDatabase();
    $pdns_admin_conn = $pdns_admin_db->getConnection();
}

if ($pdns_admin_conn) {
    echo "✅ Template database connection successful\n";
    
    // Include the domain functions to access getTemplateAsRrsets
    require_once 'php-api/api/domains.php';
    
    if (function_exists('getTemplateAsRrsets')) {
        echo "Testing template ID 22 conversion...\n";
        $template_rrsets = getTemplateAsRrsets(22, $test_domain);
        
        if ($template_rrsets !== false) {
            echo "✅ Template conversion successful!\n";
            echo "Generated " . count($template_rrsets) . " rrsets:\n";
            foreach ($template_rrsets as $rrset) {
                echo "  - {$rrset['name']} {$rrset['type']} (" . count($rrset['records']) . " records)\n";
            }
        } else {
            echo "❌ Template conversion failed\n";
        }
    } else {
        echo "❌ getTemplateAsRrsets function not available\n";
    }
} else {
    echo "❌ Template database connection failed\n";
}

echo "\n🎯 DEBUG SUMMARY\n";
echo "================\n";
echo "1. PowerDNS Admin API Connection: " . ($test_response['status_code'] === 200 ? "✅ Working" : "❌ Failed") . "\n";
echo "2. Domain Creation via /pdnsadmin/zones: " . ($create_response['status_code'] === 201 ? "✅ Working" : "❌ Failed") . "\n";
echo "3. Domain Creation via /servers/1/zones: " . ($alt_response['status_code'] === 201 ? "✅ Working" : "❌ Failed") . "\n";
echo "4. Template System: " . (isset($template_rrsets) && $template_rrsets !== false ? "✅ Working" : "❌ Failed") . "\n";

echo "\nDebug completed at: " . date('Y-m-d H:i:s') . "\n";
?>
