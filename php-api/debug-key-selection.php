<?php
require_once 'config/config.php';
require_once 'classes/PDNSAdminClient.php';

echo "=== API Key Debug ===\n\n";

$pdns_client = new PDNSAdminClient($pdns_config);

// Test endpoint detection
$reflection = new ReflectionClass($pdns_client);
$method = $reflection->getMethod('isServerEndpoint');
$method->setAccessible(true);

$test_endpoints = [
    '/servers/1/zones',
    '/pdnsadmin/users',
    '/pdnsadmin/accounts',
    '/servers/localhost/zones'
];

foreach ($test_endpoints as $endpoint) {
    $is_server = $method->invoke($pdns_client, $endpoint);
    $key_type = $is_server ? 'PowerDNS Server Key' : 'PowerDNS Admin Key';
    $key_value = $is_server ? $pdns_config['pdns_server_key'] : $pdns_config['api_key'];
    
    echo "Endpoint: $endpoint\n";
    echo "Is Server Endpoint: " . ($is_server ? 'YES' : 'NO') . "\n";
    echo "Key Type: $key_type\n";
    echo "Key Value: $key_value\n";
    echo "---\n";
}
?>
