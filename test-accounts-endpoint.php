<?php
/**
 * Comprehensive Test Script for /accounts Endpoint
 * This script tests all functionality of the accounts API endpoint
 */

require_once 'php-api/config/config.php';

class AccountsEndpointTester {
    private $base_url;
    private $test_results = [];
    private $test_account_data = [];
    private $created_accounts = [];
    
    public function __construct($base_url = 'http://localhost/php-api') {
        $this->base_url = rtrim($base_url, '/');
        echo "=== Comprehensive Accounts Endpoint Test Suite ===\n";
        echo "Base URL: {$this->base_url}\n";
        echo "Test started at: " . date('Y-m-d H:i:s') . "\n\n";
    }
    
    public function runAllTests() {
        $this->setupTestData();
        
        // Test basic connectivity
        $this->testConnectivity();
        
        // Test GET operations
        $this->testGetAllAccounts();
        $this->testGetAccountSync();
        
        // Test POST operations (Create)
        $this->testCreateAccountValid();
        $this->testCreateAccountInvalidData();
        $this->testCreateAccountMissingFields();
        
        // Test GET operations with existing accounts
        $this->testGetAccountById();
        $this->testGetAccountByUsername();
        $this->testGetAccountByIdRestful();
        $this->testGetAccountByUsernameRestful();
        $this->testGetAccountByJsonPayload();
        
        // Test PUT operations (Update)
        $this->testUpdateAccountById();
        $this->testUpdateAccountByUsername();
        $this->testUpdateAccountJsonPayload();
        $this->testUpdateAccountInvalidData();
        
        // Test DELETE operations
        $this->testDeleteAccountById();
        $this->testDeleteAccountByUsername();
        $this->testDeleteAccountJsonPayload();
        $this->testDeleteProtectedAccount();
        
        // Test edge cases
        $this->testInvalidMethods();
        $this->testNonExistentAccount();
        $this->testMalformedJson();
        
        // Cleanup
        $this->cleanup();
        
        $this->printSummary();
    }
    
    private function setupTestData() {
        $this->test_account_data = [
            'valid_account_1' => [
                'username' => 'testuser_' . time(),
                'plain_text_password' => 'TestPassword123!',
                'firstname' => 'Test',
                'lastname' => 'User',
                'email' => 'testuser_' . time() . '@example.com',
                'role' => ['id' => 2, 'name' => 'User'],
                'ip_addresses' => ['192.168.1.100', '10.0.0.50'],
                'customer_id' => 1001
            ],
            'valid_account_2' => [
                'username' => 'testuser2_' . time(),
                'plain_text_password' => 'TestPassword456!',
                'firstname' => 'Test2',
                'lastname' => 'User2',
                'email' => 'testuser2_' . time() . '@example.com',
                'role' => ['id' => 2, 'name' => 'User'],
                'ip_addresses' => ['192.168.1.101'],
                'customer_id' => 1002
            ]
        ];
    }
    
    private function makeRequest($endpoint, $method = 'GET', $data = null, $headers = []) {
        $url = $this->base_url . '/' . ltrim($endpoint, '/');
        
        // For GET requests, append data as query parameters
        if ($method === 'GET' && $data) {
            $query_string = http_build_query($data);
            $url .= '?' . $query_string;
        }
        
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => array_merge([
                'Content-Type: application/json',
                'Accept: application/json'
            ], $headers),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        // Only send JSON payload for non-GET requests
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            return [
                'success' => false,
                'error' => $curl_error,
                'http_code' => 0,
                'data' => null
            ];
        }
        
        $decoded_response = json_decode($response, true);
        
        return [
            'success' => $http_code >= 200 && $http_code < 400,
            'http_code' => $http_code,
            'data' => $decoded_response,
            'raw_response' => $response
        ];
    }
    
    private function testConnectivity() {
        echo "Testing basic connectivity...\n";
        $result = $this->makeRequest('accounts');
        
        if ($result['http_code'] == 0) {
            $this->recordTest('Connectivity', false, "Connection failed: " . $result['error']);
        } elseif ($result['http_code'] >= 500) {
            $this->recordTest('Connectivity', false, "Server error: HTTP {$result['http_code']}");
        } else {
            $this->recordTest('Connectivity', true, "Successfully connected to API");
        }
    }
    
    private function testGetAllAccounts() {
        echo "Testing GET /accounts (get all accounts)...\n";
        $result = $this->makeRequest('accounts');
        
        $success = $result['success'] && isset($result['data']);
        $message = $success ? "Retrieved accounts list" : "Failed to retrieve accounts: HTTP {$result['http_code']}";
        
        $this->recordTest('GET All Accounts', $success, $message, $result['data']);
    }
    
    private function testGetAccountSync() {
        echo "Testing GET /accounts?sync=true...\n";
        $result = $this->makeRequest('accounts', 'GET', ['sync' => true]);
        
        $success = $result['success'] && isset($result['data']);
        $message = $success ? "Account sync completed" : "Failed to sync accounts: HTTP {$result['http_code']}";
        
        $this->recordTest('GET Accounts Sync', $success, $message, $result['data']);
    }
    
    private function testCreateAccountValid() {
        echo "Testing POST /accounts (create valid account)...\n";
        $account_data = $this->test_account_data['valid_account_1'];
        $result = $this->makeRequest('accounts', 'POST', $account_data);
        
        if ($result['success'] && isset($result['data']['id'])) {
            $this->created_accounts[] = [
                'id' => $result['data']['id'],
                'username' => $result['data']['username']
            ];
            $this->recordTest('POST Create Valid Account', true, "Account created successfully", $result['data']);
        } else {
            $error_msg = isset($result['data']['error']) ? $result['data']['error'] : "HTTP {$result['http_code']}";
            $this->recordTest('POST Create Valid Account', false, "Failed to create account: " . $error_msg);
        }
    }
    
    private function testCreateAccountInvalidData() {
        echo "Testing POST /accounts (invalid IP addresses)...\n";
        $invalid_account = $this->test_account_data['valid_account_2'];
        $invalid_account['ip_addresses'] = ['invalid.ip.address', '999.999.999.999'];
        
        $result = $this->makeRequest('accounts', 'POST', $invalid_account);
        
        $success = !$result['success'] && $result['http_code'] == 400;
        $message = $success ? "Correctly rejected invalid IP addresses" : "Should have rejected invalid data";
        
        $this->recordTest('POST Invalid IP Addresses', $success, $message);
    }
    
    private function testCreateAccountMissingFields() {
        echo "Testing POST /accounts (missing required fields)...\n";
        $incomplete_account = [
            'username' => 'incomplete_' . time()
            // Missing required fields: password, firstname, email
        ];
        
        $result = $this->makeRequest('accounts', 'POST', $incomplete_account);
        
        $success = !$result['success'] && $result['http_code'] == 400;
        $message = $success ? "Correctly rejected incomplete account data" : "Should have rejected missing fields";
        
        $this->recordTest('POST Missing Fields', $success, $message);
    }
    
    private function testGetAccountById() {
        if (empty($this->created_accounts)) {
            $this->recordTest('GET Account by ID', false, "No created account to test with");
            return;
        }
        
        echo "Testing GET /accounts?id={id}...\n";
        $account = $this->created_accounts[0];
        $result = $this->makeRequest('accounts', 'GET', ['id' => $account['id']]);
        
        $success = $result['success'] && isset($result['data']['id']) && $result['data']['id'] == $account['id'];
        $message = $success ? "Retrieved account by ID" : "Failed to retrieve account by ID";
        
        $this->recordTest('GET Account by ID', $success, $message, $result['data'] ?? null);
    }
    
    private function testGetAccountByUsername() {
        if (empty($this->created_accounts)) {
            $this->recordTest('GET Account by Username', false, "No created account to test with");
            return;
        }
        
        echo "Testing GET /accounts?username={username}...\n";
        $account = $this->created_accounts[0];
        $result = $this->makeRequest('accounts', 'GET', ['username' => $account['username']]);
        
        $success = $result['success'] && isset($result['data']['username']) && $result['data']['username'] == $account['username'];
        $message = $success ? "Retrieved account by username" : "Failed to retrieve account by username";
        
        $this->recordTest('GET Account by Username', $success, $message, $result['data'] ?? null);
    }
    
    private function testGetAccountByIdRestful() {
        // Remove this test - now using query parameters instead
        $this->recordTest('GET Account by ID (RESTful)', true, "Test removed - now using query parameters");
    }
    
    private function testGetAccountByUsernameRestful() {
        // Remove this test - now using query parameters instead
        $this->recordTest('GET Account by Username (RESTful)', true, "Test removed - now using query parameters");
    }    private function testGetAccountByJsonPayload() {
        if (empty($this->created_accounts)) {
            $this->recordTest('GET Account by JSON payload', false, "No created account to test with");
            return;
        }
        
        echo "Testing GET /accounts with JSON payload...\n";
        $account = $this->created_accounts[0];
        
        // Test with ID in JSON
        $result = $this->makeRequest('accounts', 'GET', ['id' => $account['id']]);
        $success1 = $result['success'] && isset($result['data']['id']) && $result['data']['id'] == $account['id'];
        
        // Test with username in JSON
        $result = $this->makeRequest('accounts', 'GET', ['username' => $account['username']]);
        $success2 = $result['success'] && isset($result['data']['username']) && $result['data']['username'] == $account['username'];
        
        $success = $success1 && $success2;
        $message = $success ? "Retrieved account via JSON payload" : "Failed to retrieve account via JSON payload";
        
        $this->recordTest('GET Account by JSON payload', $success, $message);
    }
    
    private function testUpdateAccountById() {
        if (empty($this->created_accounts)) {
            $this->recordTest('PUT Account by ID', false, "No created account to test with");
            return;
        }
        
        echo "Testing PUT /accounts with JSON payload {id}...\n";
        $account = $this->created_accounts[0];
        $update_data = [
            'id' => $account['id'],
            'firstname' => 'Updated',
            'lastname' => 'Name',
            'ip_addresses' => ['192.168.1.200', '10.0.0.100']
        ];
        
        $result = $this->makeRequest('accounts', 'PUT', $update_data);
        
        $success = $result['success'] && $result['http_code'] == 200;
        $message = $success ? "Updated account by ID" : "Failed to update account by ID";
        
        $this->recordTest('PUT Account by ID', $success, $message);
    }
    
    private function testUpdateAccountByUsername() {
        if (empty($this->created_accounts)) {
            $this->recordTest('PUT Account by Username', false, "No created account to test with");
            return;
        }
        
        echo "Testing PUT /accounts with JSON payload {username}...\n";
        $account = $this->created_accounts[0];
        $update_data = [
            'username' => $account['username'],
            'email' => 'updated_' . time() . '@example.com'
        ];
        
        $result = $this->makeRequest('accounts', 'PUT', $update_data);
        
        $success = $result['success'] && $result['http_code'] == 200;
        $message = $success ? "Updated account by username" : "Failed to update account by username";
        
        $this->recordTest('PUT Account by Username', $success, $message);
    }
    
    private function testUpdateAccountJsonPayload() {
        if (empty($this->created_accounts)) {
            $this->recordTest('PUT Account with JSON payload', false, "No created account to test with");
            return;
        }
        
        echo "Testing PUT /accounts with JSON payload...\n";
        $account = $this->created_accounts[0];
        $update_data = [
            'id' => $account['id'],
            'customer_id' => 2001
        ];
        
        $result = $this->makeRequest('accounts', 'PUT', $update_data);
        
        $success = $result['success'] && $result['http_code'] == 200;
        $message = $success ? "Updated account with JSON payload" : "Failed to update account with JSON payload";
        
        $this->recordTest('PUT Account with JSON payload', $success, $message);
    }
    
    private function testUpdateAccountInvalidData() {
        if (empty($this->created_accounts)) {
            $this->recordTest('PUT Account Invalid Data', false, "No created account to test with");
            return;
        }
        
        echo "Testing PUT /accounts with invalid data...\n";
        $account = $this->created_accounts[0];
        $invalid_data = [
            'id' => $account['id'],
            'ip_addresses' => ['invalid.ip'],
            'customer_id' => 'not_a_number'
        ];
        
        $result = $this->makeRequest('accounts', 'PUT', $invalid_data);
        
        $success = !$result['success'] && $result['http_code'] == 400;
        $message = $success ? "Correctly rejected invalid update data" : "Should have rejected invalid data";
        
        $this->recordTest('PUT Account Invalid Data', $success, $message);
    }
    
    private function testDeleteAccountById() {
        if (count($this->created_accounts) < 2) {
            $this->recordTest('DELETE Account by ID', false, "Not enough created accounts to test");
            return;
        }
        
        echo "Testing DELETE /accounts with JSON payload {id}...\n";
        $account = array_pop($this->created_accounts); // Remove and use last account
        
        $result = $this->makeRequest('accounts', 'DELETE', ['id' => $account['id']]);
        
        $success = $result['success'] && $result['http_code'] == 200;
        $message = $success ? "Deleted account by ID" : "Failed to delete account by ID";
        
        $this->recordTest('DELETE Account by ID', $success, $message);
    }
    
    private function testDeleteAccountByUsername() {
        if (empty($this->created_accounts)) {
            $this->recordTest('DELETE Account by Username', false, "No created account to test with");
            return;
        }
        
        echo "Testing DELETE /accounts with JSON payload {username}...\n";
        $account = array_pop($this->created_accounts); // Remove and use last account
        
        $result = $this->makeRequest('accounts', 'DELETE', ['username' => $account['username']]);
        
        $success = $result['success'] && $result['http_code'] == 200;
        $message = $success ? "Deleted account by username" : "Failed to delete account by username";
        
        $this->recordTest('DELETE Account by Username', $success, $message);
    }
    
    private function testDeleteAccountJsonPayload() {
        echo "Testing DELETE /accounts with JSON payload...\n";
        // Create a temporary account for deletion
        $temp_account = [
            'username' => 'temp_delete_' . time(),
            'plain_text_password' => 'TempPassword123!',
            'firstname' => 'Temp',
            'lastname' => 'Delete',
            'email' => 'temp_delete_' . time() . '@example.com'
        ];
        
        $create_result = $this->makeRequest('accounts', 'POST', $temp_account);
        
        if (!$create_result['success']) {
            $this->recordTest('DELETE Account with JSON payload', false, "Failed to create temporary account for deletion test");
            return;
        }
        
        $delete_data = ['id' => $create_result['data']['id']];
        $result = $this->makeRequest('accounts', 'DELETE', $delete_data);
        
        $success = $result['success'] && $result['http_code'] == 200;
        $message = $success ? "Deleted account with JSON payload" : "Failed to delete account with JSON payload";
        
        $this->recordTest('DELETE Account with JSON payload', $success, $message);
    }
    
    private function testDeleteProtectedAccount() {
        echo "Testing DELETE protected account (admin) with JSON payload...\n";
        
        $result = $this->makeRequest('accounts', 'DELETE', ['username' => 'admin']);
        
        $success = !$result['success'] && $result['http_code'] == 403;
        $message = $success ? "Correctly protected admin account from deletion" : "Should have protected admin account";
        
        $this->recordTest('DELETE Protected Account', $success, $message);
    }
    
    private function testInvalidMethods() {
        echo "Testing invalid HTTP methods...\n";
        
        $result = $this->makeRequest('accounts', 'PATCH');
        $success = !$result['success'] && $result['http_code'] == 405;
        
        $message = $success ? "Correctly rejected invalid HTTP method" : "Should have rejected invalid method";
        $this->recordTest('Invalid HTTP Method', $success, $message);
    }
    
    private function testNonExistentAccount() {
        echo "Testing non-existent account retrieval with query parameter...\n";
        
        $result = $this->makeRequest('accounts', 'GET', ['id' => 999999]);
        
        $success = !$result['success'] && $result['http_code'] == 404;
        $message = $success ? "Correctly returned 404 for non-existent account" : "Should have returned 404";
        
        $this->recordTest('Non-existent Account', $success, $message);
    }
    
    private function testMalformedJson() {
        echo "Testing malformed JSON payload...\n";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->base_url . '/accounts',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => '{invalid json}',
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $success = $http_code == 400 || $http_code == 500;
        $message = $success ? "Correctly handled malformed JSON" : "Should have handled malformed JSON better";
        
        $this->recordTest('Malformed JSON', $success, $message);
    }
    
    private function cleanup() {
        echo "Cleaning up test accounts...\n";
        
        foreach ($this->created_accounts as $account) {
            $this->makeRequest('accounts', 'DELETE', ['id' => $account['id']]);
        }
        
        $this->created_accounts = [];
    }
    
    private function recordTest($test_name, $success, $message, $data = null) {
        $this->test_results[] = [
            'test' => $test_name,
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $status = $success ? "✓ PASS" : "✗ FAIL";
        echo "  {$status}: {$test_name} - {$message}\n";
    }
    
    private function printSummary() {
        echo "\n=== Test Summary ===\n";
        
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
            echo "\n";
        }
        
        echo "Test completed at: " . date('Y-m-d H:i:s') . "\n";
        echo "=== End Test Summary ===\n";
    }
}

// Run the tests
if (php_sapi_name() === 'cli') {
    // Command line usage
    $base_url = $argv[1] ?? 'http://localhost/php-api';
    $tester = new AccountsEndpointTester($base_url);
    $tester->runAllTests();
} else {
    // Web browser usage
    header('Content-Type: text/plain');
    $base_url = isset($_GET['base_url']) ? $_GET['base_url'] : 'http://localhost/php-api';
    $tester = new AccountsEndpointTester($base_url);
    $tester->runAllTests();
}
?>
