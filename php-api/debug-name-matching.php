<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Get both database connections
$database = new Database();
$db = $database->getConnection();

$pdns_admin_db = new PDNSAdminDatabase();
$pdns_admin_conn = $pdns_admin_db->getConnection();

// Get a sample of domains from PowerDNS Admin
echo "PowerDNS Admin domains (sample 10):\n";
$pdns_query = "SELECT id, name FROM domain ORDER BY id LIMIT 10";
$pdns_stmt = $pdns_admin_conn->prepare($pdns_query);
$pdns_stmt->execute();
$pdns_domains = $pdns_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($pdns_domains as $domain) {
    echo "ID: {$domain['id']}, Name: '{$domain['name']}'\n";
}

// Get corresponding domains from our database
echo "\nOur database domains (with pdns_zone_id = 0):\n";
$our_query = "SELECT id, name, pdns_zone_id FROM domains WHERE pdns_zone_id = 0 LIMIT 10";
$our_stmt = $db->prepare($our_query);
$our_stmt->execute();
$our_domains = $our_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($our_domains as $domain) {
    echo "ID: {$domain['id']}, Name: '{$domain['name']}', PDNS Zone ID: {$domain['pdns_zone_id']}\n";
}

// Check if these domain names exist in PowerDNS Admin
echo "\nChecking if our domains exist in PowerDNS Admin:\n";
foreach ($our_domains as $our_domain) {
    $check_query = "SELECT id FROM domain WHERE name = ?";
    $check_stmt = $pdns_admin_conn->prepare($check_query);
    $check_stmt->execute([$our_domain['name']]);
    $found = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($found) {
        echo "Domain '{$our_domain['name']}' found in PDNS Admin with ID: {$found['id']}\n";
    } else {
        echo "Domain '{$our_domain['name']}' NOT found in PDNS Admin\n";
    }
}
?>
