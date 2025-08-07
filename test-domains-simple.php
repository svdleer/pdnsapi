<?php
require_once 'php-api/config/config.php';
require_once 'php-api/classes/PDNSAdminClient.php';

echo "Testing Domains API..." . PHP_EOL;

try {
    $client = new PDNSAdminClient($pdns_config);
    $result = $client->getAllDomains();
    
    echo "Status Code: " . $result['status_code'] . PHP_EOL;
    echo "Domain count: " . count($result['data']) . PHP_EOL;
    
    if (!empty($result['data'])) {
        echo "First domain: " . json_encode($result['data'][0], JSON_PRETTY_PRINT) . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . PHP_EOL;
}
?>
