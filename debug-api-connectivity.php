<?php
// Load the config properly
$config_path = __DIR__ . '/php-api/config/config.php';
if (!file_exists($config_path)) {
    die("Config file not found at: $config_path\n");
}
require_once $config_path;

// Load the autoloader
$autoloader_path = __DIR__ . '/php-api/includes/autoloader.php';
if (!file_exists($autoloader_path)) {
    die("Autoloader not found at: $autoloader_path\n");
}
require_once $autoloader_path;

echo "PowerDNS Admin API Connectivity Debug\n";
echo "====================================\n\n";

// Test basic connectivity
echo "1. Testing basic connectivity to hostname...\n";
$hostname = parse_url($pdns_config['base_url'], PHP_URL_HOST);
echo "Hostname: $hostname\n";

$ping_result = shell_exec("ping -c 1 -W 5 $hostname 2>&1");
echo "Ping result: " . substr($ping_result, 0, 200) . "\n\n";

// Test SSL/HTTPS
echo "2. Testing SSL connection...\n";
$ssl_test = shell_exec("timeout 10 openssl s_client -connect $hostname:443 -servername $hostname < /dev/null 2>&1 | grep -E '(CONNECTED|SSL-Session|Verify return code)'");
echo "SSL test: " . substr($ssl_test, 0, 300) . "\n\n";

// Test basic HTTP response
echo "3. Testing basic HTTP response (without auth)...\n";
$basic_curl = shell_exec("timeout 15 curl -s -I " . $pdns_config['base_url'] . "/pdnsadmin/zones 2>&1 | head -5");
echo "Basic curl headers: $basic_curl\n\n";

// Test with authentication
echo "4. Testing with authentication...\n";
$auth_curl = shell_exec("timeout 15 curl -s -H 'Authorization: Basic " . $pdns_config['api_key'] . "' -I " . $pdns_config['base_url'] . "/pdnsadmin/zones 2>&1 | head -5");
echo "Auth curl headers: $auth_curl\n\n";

// Test using our PDNSAdminClient
echo "5. Testing via PDNSAdminClient...\n";
try {
    $client = new PDNSAdminClient();
    echo "Client created successfully\n";
    
    echo "Attempting API call...\n";
    $result = $client->get('/pdnsadmin/zones');
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n6. Config verification...\n";
echo "Base URL: " . $pdns_config['base_url'] . "\n";
echo "Auth type: " . $pdns_config['auth_type'] . "\n";
echo "API key: " . $pdns_config['api_key'] . "\n";
echo "Decoded: " . base64_decode($pdns_config['api_key']) . "\n";

echo "\nDebug complete.\n";
?>
