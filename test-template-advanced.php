<?php
/**
 * Focused Template Domain Creation Test Cases
 * Tests specific scenarios and edge cases for template-based domain creation
 */

require_once 'php-api/config/database.php';

// Test configuration
$api_base_url = 'https://pdnsapi.avant.nl';
$api_key = 'your_api_key_here'; // Replace with actual API key
$timestamp = time();

// Test scenarios
$test_scenarios = [
    [
        'name' => 'Standard Office365 Template',
        'domain' => "office365-test-$timestamp.example.com",
        'template_name' => 'Office365',
        'account_id' => 1,
        'expected_records' => ['A', 'MX', 'TXT', 'CNAME']
    ],
    [
        'name' => 'Redirect Template',
        'domain' => "redirect-test-$timestamp.example.com",
        'template_name' => 'Redirect',
        'account_id' => 1,
        'expected_records' => ['A', 'CNAME']
    ],
    [
        'name' => 'Full Service Template (Aron)',
        'domain' => "full-service-test-$timestamp.example.com",
        'template_id' => 22,
        'account_id' => 1,
        'expected_records' => ['A', 'MX', 'NS', 'SOA', 'TXT']
    ],
    [
        'name' => 'Different Account ID',
        'domain' => "different-account-$timestamp.example.com",
        'template_id' => 22,
        'account_id' => 2,
        'expected_records' => ['A', 'MX', 'NS', 'SOA', 'TXT']
    ],
    [
        'name' => 'Subdomain with Template',
        'domain' => "sub.domain-test-$timestamp.example.com",
        'template_id' => 22,
        'account_id' => 1,
        'expected_records' => ['A', 'MX', 'NS', 'SOA', 'TXT']
    ]
];

// Error test scenarios
$error_scenarios = [
    [
        'name' => 'Invalid Template ID',
        'domain' => "invalid-template-id-$timestamp.example.com",
        'template_id' => 99999,
        'account_id' => 1,
        'expect_error' => true
    ],
    [
        'name' => 'Invalid Template Name',
        'domain' => "invalid-template-name-$timestamp.example.com",
        'template_name' => 'NonExistentTemplate',
        'account_id' => 1,
        'expect_error' => true
    ],
    [
        'name' => 'Missing Domain Name',
        'template_id' => 22,
        'account_id' => 1,
        'expect_error' => true
    ],
    [
        'name' => 'Invalid Account ID',
        'domain' => "invalid-account-$timestamp.example.com",
        'template_id' => 22,
        'account_id' => -1,
        'expect_error' => true
    ]
];

class AdvancedTemplateTester {
    private $base_url;
    private $api_key;
    private $created_domains = [];
    
    public function __construct($base_url, $api_key) {
        $this->base_url = rtrim($base_url, '/');
        $this->api_key = $api_key;
    }
    
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
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        return [
            'status_code' => $http_code,
            'body' => $response,
            'data' => json_decode($response, true),
            'curl_error' => $curl_error
        ];
    }
    
    public function testScenario($scenario) {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "Testing: {$scenario['name']}\n";
        echo str_repeat("=", 60) . "\n";
        
        if (isset($scenario['expect_error']) && $scenario['expect_error']) {
            return $this->testErrorScenario($scenario);
        }
        
        // Build request data
        $data = [];
        if (isset($scenario['domain'])) {
            $data['name'] = $scenario['domain'];
        }
        if (isset($scenario['account_id'])) {
            $data['account_id'] = $scenario['account_id'];
        }
        if (isset($scenario['template_id'])) {
            $data['template_id'] = $scenario['template_id'];
        }
        if (isset($scenario['template_name'])) {
            $data['template_name'] = $scenario['template_name'];
        }
        
        echo "Request data: " . json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
        
        // Make request
        $response = $this->makeRequest('/domains', 'POST', $data);
        
        // Check response
        if ($response['curl_error']) {
            echo "❌ CURL Error: {$response['curl_error']}\n";
            return false;
        }
        
        $success = $response['status_code'] === 200 || $response['status_code'] === 201;
        
        if ($success) {
            echo "✅ Domain creation successful (HTTP {$response['status_code']})\n";
            
            if (isset($scenario['domain'])) {
                $this->created_domains[] = $scenario['domain'];
                
                // Verify domain and records
                $this->verifyDomainRecords($scenario['domain'], $scenario['expected_records'] ?? []);
            }
        } else {
            echo "❌ Domain creation failed (HTTP {$response['status_code']})\n";
            echo "Response: {$response['body']}\n";
        }
        
        return $success;
    }
    
    private function testErrorScenario($scenario) {
        echo "Expected: Error response\n";
        
        // Build request data
        $data = [];
        if (isset($scenario['domain'])) {
            $data['name'] = $scenario['domain'];
        }
        if (isset($scenario['account_id'])) {
            $data['account_id'] = $scenario['account_id'];
        }
        if (isset($scenario['template_id'])) {
            $data['template_id'] = $scenario['template_id'];
        }
        if (isset($scenario['template_name'])) {
            $data['template_name'] = $scenario['template_name'];
        }
        
        echo "Request data: " . json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
        
        $response = $this->makeRequest('/domains', 'POST', $data);
        
        if ($response['curl_error']) {
            echo "❌ CURL Error: {$response['curl_error']}\n";
            return false;
        }
        
        $is_error = $response['status_code'] >= 400;
        
        if ($is_error) {
            echo "✅ Correctly returned error (HTTP {$response['status_code']})\n";
            echo "Error response: {$response['body']}\n";
        } else {
            echo "❌ Expected error but got success (HTTP {$response['status_code']})\n";
            echo "Response: {$response['body']}\n";
        }
        
        return $is_error;
    }
    
    private function verifyDomainRecords($domainName, $expectedRecordTypes) {
        echo "\n--- Verifying Domain Records ---\n";
        
        $response = $this->makeRequest("/domains?name=" . urlencode($domainName));
        
        if ($response['status_code'] !== 200) {
            echo "❌ Failed to retrieve domain for verification\n";
            return false;
        }
        
        $domains = $response['data'];
        if (!$domains || !is_array($domains)) {
            echo "❌ No domains returned\n";
            return false;
        }
        
        $domain = null;
        foreach ($domains as $d) {
            if ($d['name'] === $domainName) {
                $domain = $d;
                break;
            }
        }
        
        if (!$domain) {
            echo "❌ Domain not found in response\n";
            return false;
        }
        
        echo "✅ Domain found: {$domain['name']}\n";
        echo "Account ID: {$domain['account']}\n";
        echo "Kind: {$domain['kind']}\n";
        echo "Type: {$domain['type']}\n";
        
        if (isset($domain['rrsets']) && is_array($domain['rrsets'])) {
            $recordTypes = [];
            echo "\nRecord sets found:\n";
            
            foreach ($domain['rrsets'] as $rrset) {
                $type = $rrset['type'];
                $name = $rrset['name'];
                $recordCount = isset($rrset['records']) ? count($rrset['records']) : 0;
                
                echo "  - $name $type ($recordCount records)\n";
                $recordTypes[] = $type;
                
                // Show first record content for verification
                if ($recordCount > 0 && isset($rrset['records'][0]['content'])) {
                    echo "    Content: {$rrset['records'][0]['content']}\n";
                }
            }
            
            // Check if expected record types are present
            if (!empty($expectedRecordTypes)) {
                echo "\nExpected record types: " . implode(', ', $expectedRecordTypes) . "\n";
                $missing = array_diff($expectedRecordTypes, $recordTypes);
                if (empty($missing)) {
                    echo "✅ All expected record types found\n";
                } else {
                    echo "⚠️  Missing record types: " . implode(', ', $missing) . "\n";
                }
            }
            
            echo "Total record sets: " . count($domain['rrsets']) . "\n";
        } else {
            echo "❌ No rrsets found in domain\n";
        }
        
        return true;
    }
    
    public function testTemplateContent($templateId) {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "Analyzing Template Content (ID: $templateId)\n";
        echo str_repeat("=", 60) . "\n";
        
        $response = $this->makeRequest("/templates?id=$templateId");
        
        if ($response['status_code'] === 200 && isset($response['data']['records'])) {
            $records = $response['data']['records'];
            echo "✅ Template has " . count($records) . " records:\n\n";
            
            $recordsByType = [];
            foreach ($records as $record) {
                $type = $record['type'];
                if (!isset($recordsByType[$type])) {
                    $recordsByType[$type] = [];
                }
                $recordsByType[$type][] = $record;
            }
            
            foreach ($recordsByType as $type => $typeRecords) {
                echo "$type Records (" . count($typeRecords) . "):\n";
                foreach ($typeRecords as $record) {
                    echo "  {$record['name']} -> {$record['content']}\n";
                    if (isset($record['ttl'])) {
                        echo "    TTL: {$record['ttl']}\n";
                    }
                }
                echo "\n";
            }
        } else {
            echo "❌ Failed to retrieve template content\n";
            echo "Response: {$response['body']}\n";
        }
    }
    
    public function cleanupCreatedDomains() {
        if (empty($this->created_domains)) {
            echo "\nNo domains to clean up.\n";
            return;
        }
        
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "Cleaning Up Created Domains\n";
        echo str_repeat("=", 60) . "\n";
        
        foreach ($this->created_domains as $domain) {
            echo "Deleting: $domain\n";
            $response = $this->makeRequest("/domains/" . urlencode($domain), 'DELETE');
            
            if ($response['status_code'] === 200 || $response['status_code'] === 204) {
                echo "✅ Successfully deleted\n";
            } else {
                echo "❌ Failed to delete (HTTP {$response['status_code']})\n";
                if ($response['body']) {
                    echo "Response: {$response['body']}\n";
                }
            }
            echo "\n";
        }
    }
    
    public function runAllTests($test_scenarios, $error_scenarios) {
        $start_time = microtime(true);
        $total_tests = 0;
        $passed_tests = 0;
        
        echo "🚀 Starting Advanced Template Domain Creation Tests\n";
        echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
        echo "API Endpoint: {$this->base_url}\n";
        
        // First, analyze some template content
        echo "\n📋 Analyzing Template Content...\n";
        $this->testTemplateContent(22); // Aron template
        $this->testTemplateContent(14); // Office365 template
        
        // Run success scenarios
        echo "\n✅ Running Success Scenarios...\n";
        foreach ($test_scenarios as $scenario) {
            if ($this->testScenario($scenario)) {
                $passed_tests++;
            }
            $total_tests++;
            
            // Small delay between tests
            sleep(1);
        }
        
        // Run error scenarios
        echo "\n❌ Running Error Scenarios...\n";
        foreach ($error_scenarios as $scenario) {
            if ($this->testScenario($scenario)) {
                $passed_tests++;
            }
            $total_tests++;
            
            // Small delay between tests
            sleep(1);
        }
        
        // Cleanup
        $this->cleanupCreatedDomains();
        
        // Final summary
        $end_time = microtime(true);
        $duration = round($end_time - $start_time, 2);
        
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "🎯 FINAL TEST RESULTS\n";
        echo str_repeat("=", 60) . "\n";
        echo "Total Tests: $total_tests\n";
        echo "Passed: $passed_tests\n";
        echo "Failed: " . ($total_tests - $passed_tests) . "\n";
        echo "Success Rate: " . round(($passed_tests / $total_tests) * 100, 1) . "%\n";
        echo "Total Duration: {$duration}s\n";
        echo "Average per Test: " . round($duration / $total_tests, 2) . "s\n";
        echo "\nTest completed at: " . date('Y-m-d H:i:s') . "\n";
        echo str_repeat("=", 60) . "\n";
        
        return $passed_tests === $total_tests;
    }
}

// Run the tests
echo "🧪 Advanced Template Domain Creation Test Suite\n";
echo "=" . str_repeat("=", 59) . "\n";

$tester = new AdvancedTemplateTester($api_base_url, $api_key);
$success = $tester->runAllTests($test_scenarios, $error_scenarios);

exit($success ? 0 : 1);

?>
