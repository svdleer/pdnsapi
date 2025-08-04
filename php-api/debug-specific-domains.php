<?php
require_once 'config/config.php';
require_once 'config/database.php';

$pdns_admin_db = new PDNSAdminDatabase();
$pdns_admin_conn = $pdns_admin_db->getConnection();

$test_domains = ['papa-mama.nl', 'lasource-uzes.com', 'overnightstudios.com'];

echo "Checking if specific domains exist in PowerDNS Admin:\n";
foreach ($test_domains as $domain_name) {
    $query = "SELECT id, name FROM domain WHERE name = ?";
    $stmt = $pdns_admin_conn->prepare($query);
    $stmt->execute([$domain_name]);
    $found = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($found) {
        echo "Domain '{$domain_name}' found with ID: {$found['id']}\n";
    } else {
        echo "Domain '{$domain_name}' NOT found in PowerDNS Admin\n";
    }
}

// Check how many domains exist in PowerDNS Admin vs our database
$pdns_count_query = "SELECT COUNT(*) as count FROM domain";
$pdns_count_stmt = $pdns_admin_conn->prepare($pdns_count_query);
$pdns_count_stmt->execute();
$pdns_count = $pdns_count_stmt->fetch(PDO::FETCH_ASSOC);

$database = new Database();
$db = $database->getConnection();

$our_count_query = "SELECT COUNT(*) as count FROM domains";
$our_count_stmt = $db->prepare($our_count_query);
$our_count_stmt->execute();
$our_count = $our_count_stmt->fetch(PDO::FETCH_ASSOC);

echo "\nDomain counts:\n";
echo "PowerDNS Admin: {$pdns_count['count']}\n";
echo "Our database: {$our_count['count']}\n";
?>
