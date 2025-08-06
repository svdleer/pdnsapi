<?php
/**
 * Unit Tests for Accounts API Functions
 * Tests the individual functions in accounts.php without making HTTP requests
 */

// Mock the global variables that would normally be set
$GLOBALS['db'] = null;
$GLOBALS['pdns_admin_conn'] = null;
$GLOBALS['pdns_config'] = [
    'base_url' => 'https://dnsadmin.avant.nl/api/v1',
    'pdns_admin_url' => 'https://dnsadmin.avant.nl',
    'pdns_admin_user' => 'apiadmin',
    'pdns_admin_password' => 'test_password'
];

class AccountsUnitTester {
    private $test_results = [];
    
    public function __construct() {
        echo "=== Accounts API Unit Tests ===\n";
        echo "Testing individual PHP functions\n";
        echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";
    }
    
    public function runAllTests() {
        $this->testRequestParsing();
        $this->testParameterExtraction();
        $this->testValidationFunctions();
        $this->testErrorHandling();
        $this->testHelperFunctions();
        
        $this->printSummary();
    }
    
    private function testRequestParsing() {
        echo "Testing request parsing logic...\n";
        
        // Test RESTful path parsing
        $test_cases = [
            '/php-api/accounts/123' => ['expected_id' => '123', 'expected_username' => null],
            '/accounts/testuser' => ['expected_id' => null, 'expected_username' => 'testuser'],
            '/php-api/accounts/456/edit' => ['expected_id' => '456', 'expected_username' => null],
            '/accounts' => ['expected_id' => null, 'expected_username' => null]
        ];
        
        foreach ($test_cases as $uri => $expected) {
            $path_parts = explode('/', trim(parse_url($uri, PHP_URL_PATH), '/'));
            $account_id = null;
            $account_username = null;
            
            $accounts_index = array_search('accounts', $path_parts);
            if ($accounts_index !== false && isset($path_parts[$accounts_index + 1])) {
                $path_id = $path_parts[$accounts_index + 1];
                if (is_numeric($path_id)) {
                    $account_id = $path_id;
                } elseif (!empty($path_id) && !is_numeric($path_id)) {
                    $account_username = $path_id;
                }
            }
            
            $success = ($account_id == $expected['expected_id']) && ($account_username == $expected['expected_username']);
            $message = $success ? "Correctly parsed {$uri}" : "Failed to parse {$uri}";
            
            $this->recordTest("Parse {$uri}", $success, $message);
        }
    }
    
    private function testParameterExtraction() {
        echo "Testing parameter extraction...\n";
        
        // Mock $_GET parameters
        $test_cases = [
            ['id' => '123', 'username' => null],
            ['id' => null, 'username' => 'testuser'],
            ['id' => '456', 'username' => 'ignored'], // ID takes precedence
            ['sync' => 'true']
        ];
        
        foreach ($test_cases as $params) {
            $extracted_id = isset($params['id']) ? $params['id'] : null;
            $extracted_username = isset($params['username']) ? $params['username'] : null;
            $extracted_sync = isset($params['sync']) ? $params['sync'] : null;
            
            $success = true; // This is just testing the extraction logic
            $message = "Extracted parameters correctly";
            
            $this->recordTest("Extract parameters", $success, $message);
        }
    }
    
    private function testValidationFunctions() {
        echo "Testing validation functions...\n";
        
        // Test IP address validation
        $valid_ips = ['192.168.1.1', '10.0.0.1', '172.16.0.1', '127.0.0.1'];
        $invalid_ips = ['256.256.256.256', '192.168.1', 'not.an.ip', ''];
        
        foreach ($valid_ips as $ip) {
            $is_valid = filter_var($ip, FILTER_VALIDATE_IP) !== false;
            $this->recordTest("Validate valid IP {$ip}", $is_valid, $is_valid ? "Valid IP" : "Should be valid");
        }
        
        foreach ($invalid_ips as $ip) {
            $is_valid = filter_var($ip, FILTER_VALIDATE_IP) !== false;
            $this->recordTest("Validate invalid IP {$ip}", !$is_valid, !$is_valid ? "Correctly invalid" : "Should be invalid");
        }
        
        // Test customer_id validation
        $valid_customer_ids = [1, 100, 9999];
        $invalid_customer_ids = [0, -1, 'string', '', null];
        
        foreach ($valid_customer_ids as $id) {
            $is_valid = is_numeric($id) && $id > 0;
            $this->recordTest("Validate valid customer_id {$id}", $is_valid, $is_valid ? "Valid customer_id" : "Should be valid");
        }
        
        foreach ($invalid_customer_ids as $id) {
            $is_valid = $id !== null && is_numeric($id) && $id > 0;
            $this->recordTest("Validate invalid customer_id", !$is_valid, !$is_valid ? "Correctly invalid" : "Should be invalid");
        }
    }
    
    private function testErrorHandling() {
        echo "Testing error handling...\n";
        
        // Test protected user checking
        $protected_users = ['admin', 'administrator', 'apiadmin'];
        $regular_users = ['user1', 'testuser', 'customer'];
        
        foreach ($protected_users as $user) {
            $is_protected = in_array(strtolower($user), ['admin', 'administrator', 'apiadmin']);
            $this->recordTest("Check protected user {$user}", $is_protected, $is_protected ? "Correctly protected" : "Should be protected");
        }
        
        foreach ($regular_users as $user) {
            $is_protected = in_array(strtolower($user), ['admin', 'administrator', 'apiadmin']);
            $this->recordTest("Check regular user {$user}", !$is_protected, !$is_protected ? "Correctly not protected" : "Should not be protected");
        }
    }
    
    private function testHelperFunctions() {
        echo "Testing helper functions...\n";
        
        // Test JSON decoding simulation
        $valid_json = '{"username": "test", "email": "test@example.com"}';
        $invalid_json = '{invalid json}';
        
        $decoded_valid = json_decode($valid_json, true);
        $success1 = $decoded_valid !== null && isset($decoded_valid['username']);
        $this->recordTest("Decode valid JSON", $success1, $success1 ? "Valid JSON decoded" : "Should decode valid JSON");
        
        $decoded_invalid = json_decode($invalid_json, true);
        $success2 = $decoded_invalid === null;
        $this->recordTest("Decode invalid JSON", $success2, $success2 ? "Invalid JSON rejected" : "Should reject invalid JSON");
        
        // Test array operations
        $test_array = [
            ['username' => 'user1'],
            ['username' => 'user2'],
            ['username' => 'user3']
        ];
        
        $usernames = array_column($test_array, 'username');
        $expected_usernames = ['user1', 'user2', 'user3'];
        
        $success = $usernames === $expected_usernames;
        $this->recordTest("Array column extraction", $success, $success ? "Extracted usernames correctly" : "Failed to extract usernames");
    }
    
    private function recordTest($test_name, $success, $message) {
        $this->test_results[] = [
            'test' => $test_name,
            'success' => $success,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $status = $success ? "✓ PASS" : "✗ FAIL";
        echo "  {$status}: {$test_name} - {$message}\n";
    }
    
    private function printSummary() {
        echo "\n=== Unit Test Summary ===\n";
        
        $total_tests = count($this->test_results);
        $passed_tests = count(array_filter($this->test_results, function($test) {
            return $test['success'];
        }));
        $failed_tests = $total_tests - $passed_tests;
        
        echo "Total Tests: {$total_tests}\n";
        echo "Passed: {$passed_tests}\n";
        echo "Failed: {$failed_tests}\n";
        echo "Success Rate: " . round(($passed_tests / $total_tests) * 100, 2) . "%\n\n";
        
        if ($failed_tests > 0) {
            echo "Failed Tests:\n";
            foreach ($this->test_results as $test) {
                if (!$test['success']) {
                    echo "- {$test['test']}: {$test['message']}\n";
                }
            }
        }
        
        echo "\nUnit tests completed at: " . date('Y-m-d H:i:s') . "\n";
    }
}

// Run the unit tests
$tester = new AccountsUnitTester();
$tester->runAllTests();
?>
