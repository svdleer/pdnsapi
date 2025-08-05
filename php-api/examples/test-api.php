<?php
/**
 * Example API Usage Script
 * This script demonstrates how to interact with the PDNSAdmin PHP API
 */

// Configuration
$api_base_url = 'http://localhost/php-api'; // Update with your API URL

echo "PDNSAdmin PHP API - Example Usage\n";
echo "=================================\n\n";

// Function to make API requests
function apiRequest($url, $method = 'GET', $data = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    if ($data && in_array($method, ['POST', 'PUT'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status_code' => $http_code,
        'data' => json_decode($response, true)
    ];
}

// 1. Check API Status
echo "1. Checking API Status...\n";
$response = apiRequest($api_base_url . '/status');
if ($response['status_code'] == 200) {
    echo "   ✓ API is running\n";
    echo "   Database Status: " . $response['data']['data']['database_status'] . "\n";
    echo "   PDNSAdmin Status: " . $response['data']['data']['pdns_admin_status'] . "\n";
} else {
    echo "   ✗ API Error: " . $response['data']['error'] . "\n";
    exit(1);
}

// 2. Test PDNSAdmin Connection
echo "\n2. Testing PDNSAdmin Connection...\n";
$response = apiRequest($api_base_url . '/status?action=test_connection');
if ($response['status_code'] == 200) {
    echo "   ✓ PDNSAdmin connection successful\n";
} else {
    echo "   ✗ PDNSAdmin connection failed\n";
}

// 3. Sync domains from PDNSAdmin
echo "\n3. Syncing domains from PDNSAdmin...\n";
$response = apiRequest($api_base_url . '/domains?sync=true');
if ($response['status_code'] == 200) {
    echo "   ✓ Synced " . $response['data']['data']['synced_count'] . " domains\n";
} else {
    echo "   ✗ Sync failed\n";
}

// 4. Create a test account
echo "\n4. Creating test account...\n";
$account_data = [
    'name' => 'test-account-' . time(),
    'description' => 'Test account created by example script',
    'contact' => 'Test User',
    'mail' => 'test@example.com'
];

$response = apiRequest($api_base_url . '/accounts', 'POST', $account_data);
if ($response['status_code'] == 201) {
    echo "   ✓ Account '{$account_data['name']}' created successfully\n";
    $created_account_name = $account_data['name'];
} else {
    echo "   ✗ Failed to create account: " . ($response['data']['error'] ?? 'Unknown error') . "\n";
    $created_account_name = null;
}

// 5. List all accounts
echo "\n5. Listing all accounts...\n";
$response = apiRequest($api_base_url . '/accounts');
if ($response['status_code'] == 200) {
    $accounts = $response['data']['data'];
    echo "   ✓ Found " . count($accounts) . " accounts:\n";
    foreach ($accounts as $account) {
        echo "     - ID: {$account['id']}, Name: {$account['name']}, Email: {$account['mail']}\n";
        if ($account['name'] === $created_account_name) {
            $created_account_id = $account['id'];
        }
    }
} else {
    echo "   ✗ Failed to list accounts\n";
}

// 6. List all domains
echo "\n6. Listing all domains...\n";
$response = apiRequest($api_base_url . '/domains');
if ($response['status_code'] == 200) {
    $domains = $response['data']['data'];
    echo "   ✓ Found " . count($domains) . " domains:\n";
    foreach (array_slice($domains, 0, 5) as $domain) { // Show first 5 only
        echo "     - ID: {$domain['id']}, Name: {$domain['name']}, Account: " . ($domain['account_name'] ?? 'None') . "\n";
    }
    if (count($domains) > 5) {
        echo "     ... and " . (count($domains) - 5) . " more\n";
    }
} else {
    echo "   ✗ Failed to list domains\n";
}

// 7. Add a domain to the created account (if we have both)
if (isset($created_account_id) && !empty($domains)) {
    echo "\n7. Adding domain to account...\n";
    $first_domain = $domains[0]['name'];
    
    $domain_account_data = [
        'domain_name' => $first_domain,
        'account_id' => $created_account_id
    ];
    
    $response = apiRequest($api_base_url . '/domain-account?action=add', 'POST', $domain_account_data);
    if ($response['status_code'] == 200) {
        echo "   ✓ Domain '{$first_domain}' added to account '{$created_account_name}'\n";
    } else {
        echo "   ✗ Failed to add domain to account: " . ($response['data']['error'] ?? 'Unknown error') . "\n";
    }
    
    // 8. List domains for the account
    echo "\n8. Listing domains for account...\n";
    $list_data = ['account_id' => $created_account_id];
    $response = apiRequest($api_base_url . '/domain-account?action=list', 'POST', $list_data);
    if ($response['status_code'] == 200) {
        $account_domains = $response['data']['data']['domains'];
        echo "   ✓ Account '{$created_account_name}' has " . count($account_domains) . " domains:\n";
        foreach ($account_domains as $domain) {
            echo "     - {$domain['name']}\n";
        }
    } else {
        echo "   ✗ Failed to list account domains\n";
    }
}

// 9. Clean up - delete the test account
if (isset($created_account_id)) {
    echo "\n9. Cleaning up - deleting test account...\n";
    $response = apiRequest($api_base_url . '/accounts?id=' . $created_account_id, 'DELETE');
    if ($response['status_code'] == 200) {
        echo "   ✓ Test account deleted successfully\n";
    } else {
        echo "   ✗ Failed to delete test account: " . ($response['data']['error'] ?? 'Unknown error') . "\n";
    }
}

echo "\nExample script completed!\n";
echo "\nAPI Endpoints tested:\n";
echo "- GET /status\n";
echo "- GET /status?action=test_connection\n";
echo "- GET /domains?sync=true\n";
echo "- POST /accounts\n";
echo "- GET /accounts\n";
echo "- GET /domains\n";
echo "- POST /domain-account?action=add\n";
echo "- POST /domain-account?action=list\n";
echo "- DELETE /accounts\n";
?>
