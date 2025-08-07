<?php
require_once 'php-api/config/config.php';
require_once 'php-api/classes/PDNSAdminClient.php';

echo "Testing Templates API..." . PHP_EOL;

try {
    $client = new PDNSAdminClient($pdns_config);
    $result = $client->getAllTemplates();
    
    echo "Status Code: " . $result['status_code'] . PHP_EOL;
    echo "Templates count: " . count($result['data']) . PHP_EOL;
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . PHP_EOL;
}
?>
