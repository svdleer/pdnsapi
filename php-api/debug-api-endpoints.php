<?php
// Debug script to explore PowerDNS Admin API endpoints
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'classes/PDNSAdminClient.php';

// Initialize PDNSAdmin client
$pdns_client = new PDNSAdminClient($pdns_config);

echo "Exploring PowerDNS Admin API endpoints...\n\n";

// Try different potential endpoints
$endpoints_to_test = [
    '/pdnsadmin/zones?details=true',
    '/pdnsadmin/zones?with_accounts=true',
    '/pdnsadmin/zones/1',
    '/pdnsadmin/domains',
    '/pdnsadmin/accounts',
    '/pdnsadmin/users',
    '/pdnsadmin/zone_accounts',
    '/pdnsadmin/domain_accounts'
];

foreach ($endpoints_to_test as $endpoint) {
    echo "Testing endpoint: $endpoint\n";
    $response = $pdns_client->makeRequest($endpoint);
    echo "  Status: " . $response['status_code'] . "\n";
    
    if ($response['status_code'] == 200) {
        echo "  SUCCESS! Data available.\n";
        if (is_array($response['data']) && count($response['data']) > 0) {
            echo "  Sample data structure:\n";
            $sample = is_array($response['data']) ? $response['data'][0] : $response['data'];
            echo "    " . json_encode($sample, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        }
    } else {
        echo "  Failed: " . ($response['raw_response'] ? substr($response['raw_response'], 0, 100) : 'No response') . "...\n";
    }
    echo "\n";
}

// Also test getting accounts to see their structure
echo "Getting accounts to understand account structure:\n";
$accounts_response = $pdns_client->getAllAccounts();
if ($accounts_response['status_code'] == 200) {
    echo "Accounts found: " . count($accounts_response['data']) . "\n";
    if (count($accounts_response['data']) > 0) {
        echo "Sample account:\n";
        echo json_encode($accounts_response['data'][0], JSON_PRETTY_PRINT) . "\n";
    }
} else {
    echo "Failed to get accounts: " . $accounts_response['status_code'] . "\n";
}

echo "\n";

// Test getting users to see their structure  
echo "Getting users to understand user structure:\n";
$users_response = $pdns_client->getAllUsers();
if ($users_response['status_code'] == 200) {
    echo "Users found: " . count($users_response['data']) . "\n";
    if (count($users_response['data']) > 0) {
        echo "Sample user:\n";
        echo json_encode($users_response['data'][0], JSON_PRETTY_PRINT) . "\n";
    }
} else {
    echo "Failed to get users: " . $users_response['status_code'] . "\n";
}
?>
