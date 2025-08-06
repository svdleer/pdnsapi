<?php
/**
 * Comprehensive Test Script for Template-Based Domain Creation
 * Tests all available templates and domain creation scenarios
 */

require_once 'php-api/config/database.php';

$api_base_url = 'https://pdnsapi.avant.nl';
$api_key = 'your_api_key_here'; // Replace with actual API key

class TemplateDomainTester {
    private $base_url;
    private $api_key;
    private $test_results = [];
    private $created_domains = [];
    
    public function __construct($base_url, $api_key) {
        $this->base_url = rtrim($base_url, '/');
        $this->api_key = $api_key;
    }
    
    /**
     * Make API request
     */
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = $this->base_url . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'status_code' => $http_code,
            'body' => $response,
            'data' => json_decode($response, true)
        ];
    }
    
    /**
     * Get all available templates
     */
    public function getTemplates() {
        $response = $this->makeRequest('/templates');
        
        if ($response['status_code'] === 200 && $response['data']) {
            return $response['data'];
        }
        
        return [];
    }
    
    /**
     * Test template listing endpoint
     */
    public function testTemplatesList() {
        echo "\n=== Testing Templates List Endpoint ===\n";
        
        $response = $this->makeRequest('/templates');
        $success = $response['status_code'] === 200;
        
        $this->test_results[] = [
            'test' => 'Templates List',
            'success' => $success,
            'status_code' => $response['status_code'],
            'response_size' => strlen($response['body'])
        ];
        
        if ($success && $response['data']) {
            echo "✓ Successfully retrieved " . count($response['data']) . " templates\n";
            foreach ($response['data'] as $template) {
                echo "  - Template {$template['id']}: {$template['name']} ({$template['description']})\n";
            }
        } else {
            echo "✗ Failed to retrieve templates\n";
            echo "Response: " . $response['body'] . "\n";
        }
        
        return $success;
    }
    
    /**
     * Test specific template details
     */
    public function testTemplateDetails($templateId) {
        echo "\n=== Testing Template Details for ID: $templateId ===\n";
        
        $response = $this->makeRequest("/templates?id=$templateId");
        $success = $response['status_code'] === 200;
        
        $this->test_results[] = [
            'test' => "Template Details ID: $templateId",
            'success' => $success,
            'status_code' => $response['status_code']
        ];
        
        if ($success && isset($response['data']['records'])) {
            $records = $response['data']['records'];
            echo "✓ Successfully retrieved template with " . count($records) . " records\n";
            foreach ($records as $record) {
                echo "  - {$record['name']} {$record['type']} {$record['content']}\n";
            }
        } else {
            echo "✗ Failed to retrieve template details\n";
            echo "Response: " . $response['body'] . "\n";
        }
        
        return $success;
    }
    
    /**
     * Test domain creation with template ID
     */
    public function testDomainCreationWithTemplateId($templateId, $domainName, $accountId = 1) {
        echo "\n=== Testing Domain Creation with Template ID: $templateId ===\n";
        echo "Domain: $domainName\n";
        echo "Account ID: $accountId\n";
        
        $data = [
            'name' => $domainName,
            'account_id' => $accountId,
            'template_id' => $templateId
        ];
        
        $response = $this->makeRequest('/domains', 'POST', $data);
        $success = $response['status_code'] === 201 || $response['status_code'] === 200;
        
        $this->test_results[] = [
            'test' => "Domain Creation with Template ID: $templateId",
            'domain' => $domainName,
            'success' => $success,
            'status_code' => $response['status_code']
        ];
        
        if ($success) {
            echo "✓ Successfully created domain with template\n";
            $this->created_domains[] = $domainName;
            
            // Verify the domain was created with records
            $this->verifyDomainRecords($domainName);
        } else {
            echo "✗ Failed to create domain\n";
            echo "Response: " . $response['body'] . "\n";
        }
        
        return $success;
    }
    
    /**
     * Test domain creation with template name
     */
    public function testDomainCreationWithTemplateName($templateName, $domainName, $accountId = 1) {
        echo "\n=== Testing Domain Creation with Template Name: $templateName ===\n";
        echo "Domain: $domainName\n";
        echo "Account ID: $accountId\n";
        
        $data = [
            'name' => $domainName,
            'account_id' => $accountId,
            'template_name' => $templateName
        ];
        
        $response = $this->makeRequest('/domains', 'POST', $data);
        $success = $response['status_code'] === 201 || $response['status_code'] === 200;
        
        $this->test_results[] = [
            'test' => "Domain Creation with Template Name: $templateName",
            'domain' => $domainName,
            'success' => $success,
            'status_code' => $response['status_code']
        ];
        
        if ($success) {
            echo "✓ Successfully created domain with template name\n";
            $this->created_domains[] = $domainName;
            
            // Verify the domain was created with records
            $this->verifyDomainRecords($domainName);
        } else {
            echo "✗ Failed to create domain\n";
            echo "Response: " . $response['body'] . "\n";
        }
        
        return $success;
    }
    
    /**
     * Verify domain records after creation
     */
    public function verifyDomainRecords($domainName) {
        echo "\n--- Verifying Domain Records for: $domainName ---\n";
        
        $response = $this->makeRequest("/domains?name=$domainName");
        
        if ($response['status_code'] === 200 && $response['data']) {
            $domains = $response['data'];
            $domain = null;
            
            // Find our domain
            foreach ($domains as $d) {
                if ($d['name'] === $domainName) {
                    $domain = $d;
                    break;
                }
            }
            
            if ($domain && isset($domain['rrsets'])) {
                echo "✓ Domain found with " . count($domain['rrsets']) . " record sets\n";
                foreach ($domain['rrsets'] as $rrset) {
                    $recordCount = count($rrset['records']);
                    echo "  - {$rrset['name']} {$rrset['type']} ($recordCount records)\n";
                }
            } else {
                echo "✗ Domain not found or no records\n";
            }
        } else {
            echo "✗ Failed to verify domain records\n";
        }
    }
    
    /**
     * Test error scenarios
     */
    public function testErrorScenarios() {
        echo "\n=== Testing Error Scenarios ===\n";
        
        // Test with invalid template ID
        echo "\n--- Testing Invalid Template ID ---\n";
        $data = [
            'name' => 'test-invalid-template.example.com',
            'account_id' => 1,
            'template_id' => 99999
        ];
        
        $response = $this->makeRequest('/domains', 'POST', $data);
        $expected_error = $response['status_code'] >= 400;
        
        $this->test_results[] = [
            'test' => 'Invalid Template ID Error',
            'success' => $expected_error,
            'status_code' => $response['status_code']
        ];
        
        if ($expected_error) {
            echo "✓ Correctly returned error for invalid template ID\n";
        } else {
            echo "✗ Should have returned error for invalid template ID\n";
        }
        
        // Test with invalid template name
        echo "\n--- Testing Invalid Template Name ---\n";
        $data = [
            'name' => 'test-invalid-template-name.example.com',
            'account_id' => 1,
            'template_name' => 'NonExistentTemplate'
        ];
        
        $response = $this->makeRequest('/domains', 'POST', $data);
        $expected_error = $response['status_code'] >= 400;
        
        $this->test_results[] = [
            'test' => 'Invalid Template Name Error',
            'success' => $expected_error,
            'status_code' => $response['status_code']
        ];
        
        if ($expected_error) {
            echo "✓ Correctly returned error for invalid template name\n";
        } else {
            echo "✗ Should have returned error for invalid template name\n";
        }
        
        // Test with both template_id and template_name
        echo "\n--- Testing Both Template ID and Name Provided ---\n";
        $data = [
            'name' => 'test-both-template-params.example.com',
            'account_id' => 1,
            'template_id' => 22,
            'template_name' => 'Aron'
        ];
        
        $response = $this->makeRequest('/domains', 'POST', $data);
        // This should either work (using one parameter) or return an error
        
        $this->test_results[] = [
            'test' => 'Both Template ID and Name Provided',
            'success' => true, // We accept either outcome
            'status_code' => $response['status_code']
        ];
        
        echo "Response status: {$response['status_code']}\n";
    }
    
    /**
     * Cleanup created test domains
     */
    public function cleanupTestDomains() {
        echo "\n=== Cleaning Up Test Domains ===\n";
        
        foreach ($this->created_domains as $domain) {
            echo "Attempting to delete: $domain\n";
            $response = $this->makeRequest("/domains/$domain", 'DELETE');
            
            if ($response['status_code'] === 200 || $response['status_code'] === 204) {
                echo "✓ Successfully deleted $domain\n";
            } else {
                echo "✗ Failed to delete $domain (Status: {$response['status_code']})\n";
            }
        }
    }
    
    /**
     * Run all tests
     */
    public function runAllTests() {
        echo "Starting Comprehensive Template Domain Creation Tests...\n";
        echo "API Base URL: {$this->base_url}\n";
        echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
        
        $start_time = microtime(true);
        
        // Test 1: Templates list
        $this->testTemplatesList();
        
        // Get available templates
        $templates = $this->getTemplates();
        
        if (empty($templates)) {
            echo "\n❌ No templates available. Cannot proceed with domain creation tests.\n";
            return;
        }
        
        // Test 2: Template details for first few templates
        $test_template_ids = array_slice(array_column($templates, 'id'), 0, 3);
        foreach ($test_template_ids as $template_id) {
            $this->testTemplateDetails($template_id);
        }
        
        // Test 3: Domain creation with template ID
        $timestamp = time();
        $this->testDomainCreationWithTemplateId(
            22, // Aron template
            "test-template-id-$timestamp.example.com",
            1
        );
        
        // Test 4: Domain creation with template name
        $this->testDomainCreationWithTemplateName(
            'Office365',
            "test-template-name-$timestamp.example.com",
            1
        );
        
        // Test 5: Multiple domains with different templates
        $test_templates = array_slice($templates, 0, 3);
        foreach ($test_templates as $i => $template) {
            $this->testDomainCreationWithTemplateId(
                $template['id'],
                "test-multi-$i-$timestamp.example.com",
                1
            );
        }
        
        // Test 6: Different account IDs
        $this->testDomainCreationWithTemplateId(
            22, // Aron template
            "test-account2-$timestamp.example.com",
            2
        );
        
        // Test 7: Error scenarios
        $this->testErrorScenarios();
        
        // Clean up
        $this->cleanupTestDomains();
        
        // Generate summary
        $this->generateTestSummary($start_time);
    }
    
    /**
     * Generate test summary
     */
    public function generateTestSummary($start_time) {
        $end_time = microtime(true);
        $duration = round($end_time - $start_time, 2);
        
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "TEST SUMMARY\n";
        echo str_repeat("=", 60) . "\n";
        echo "Total Duration: {$duration}s\n";
        echo "Total Tests: " . count($this->test_results) . "\n";
        
        $passed = array_filter($this->test_results, function($result) {
            return $result['success'];
        });
        
        $failed = array_filter($this->test_results, function($result) {
            return !$result['success'];
        });
        
        echo "Passed: " . count($passed) . "\n";
        echo "Failed: " . count($failed) . "\n";
        echo "Success Rate: " . round((count($passed) / count($this->test_results)) * 100, 1) . "%\n";
        
        if (!empty($failed)) {
            echo "\nFAILED TESTS:\n";
            foreach ($failed as $test) {
                echo "❌ {$test['test']} (Status: {$test['status_code']})\n";
            }
        }
        
        echo "\nALL TESTS:\n";
        foreach ($this->test_results as $test) {
            $status = $test['success'] ? '✅' : '❌';
            $domain = isset($test['domain']) ? " ({$test['domain']})" : '';
            echo "$status {$test['test']}$domain - Status: {$test['status_code']}\n";
        }
        
        echo "\nTest completed at: " . date('Y-m-d H:i:s') . "\n";
    }
}

// Configuration
$tester = new TemplateDomainTester($api_base_url, $api_key);

// Run all tests
$tester->runAllTests();

?>
