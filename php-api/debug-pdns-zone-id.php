<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Get PowerDNS Admin database connection
$pdns_admin_db = new PDNSAdminDatabase();
$pdns_admin_conn = $pdns_admin_db->getConnection();

if (!$pdns_admin_conn) {
    die("Failed to connect to PowerDNS Admin database\n");
}

// Check the actual structure and first few domains
$query = "SELECT id, name, type FROM domain LIMIT 5";
$stmt = $pdns_admin_conn->prepare($query);
$stmt->execute();
$domains = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "PowerDNS Admin domains (first 5):\n";
foreach ($domains as $domain) {
    echo "ID: {$domain['id']}, Name: {$domain['name']}, Type: {$domain['type']}\n";
}

// Also check our own database to see what's stored
echo "\nOur database domains (first 5):\n";
$database = new Database();
$db = $database->getConnection();

$our_query = "SELECT id, name, pdns_zone_id FROM domains LIMIT 5";
$our_stmt = $db->prepare($our_query);
$our_stmt->execute();
$our_domains = $our_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($our_domains as $domain) {
    echo "ID: {$domain['id']}, Name: {$domain['name']}, PDNS Zone ID: {$domain['pdns_zone_id']}\n";
}
?>
