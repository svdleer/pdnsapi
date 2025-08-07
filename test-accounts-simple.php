<?php
require_once 'php-api/config/config.php';
require_once 'php-api/classes/PDNSAdminClient.php';

echo "Testing Accounts API..." . PHP_EOL;

// Debug config loading
echo "Config loaded: " . (isset($pdns_config) ? 'YES' : 'NO') . PHP_EOL;
if (isset($pdns_config)) {
    echo "Base URL: " . $pdns_config['base_url'] . PHP_EOL;
    echo "API Key: " . substr($pdns_config['api_key'], 0, 10) . "..." . PHP_EOL;
}

try {
    $client = new PDNSAdminClient($pdns_config);
    $result = $client->getAllAccounts();
    
    echo "Result type: " . gettype($result) . PHP_EOL;
    echo "Result content: " . json_encode($result) . PHP_EOL;
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . PHP_EOL;
}
?>
