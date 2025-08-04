<?php
require_once 'config/config.php';
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "Checking all entries for 'papa-mama.nl' in our database:\n";
$query = "SELECT id, name, pdns_zone_id, account_id FROM domains WHERE name LIKE '%papa-mama.nl%'";
$stmt = $db->prepare($query);
$stmt->execute();
$domains = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($domains as $domain) {
    echo "ID: {$domain['id']}, Name: '{$domain['name']}', PDNS Zone ID: {$domain['pdns_zone_id']}, Account ID: {$domain['account_id']}\n";
}

// Test the matching logic used in sync
$domain_name = 'papa-mama.nl';
$domain_name_no_dot = rtrim($domain_name, '.');

echo "\nTesting sync matching logic for 'papa-mama.nl':\n";
echo "Searching for: '{$domain_name}' OR '{$domain_name_no_dot}.'\n";

$check_query = "SELECT id, name FROM domains WHERE name = ? OR name = ?";
$check_stmt = $db->prepare($check_query);
$check_stmt->execute([$domain_name, $domain_name_no_dot . '.']);
$matches = $check_stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found matches:\n";
foreach ($matches as $match) {
    echo "ID: {$match['id']}, Name: '{$match['name']}'\n";
}
?>
