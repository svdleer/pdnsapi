<?php
require_once 'config/config.php';
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Test the database directly
$query = "SELECT id, name, pdns_zone_id, account_id FROM domains LIMIT 3";
$stmt = $db->prepare($query);
$stmt->execute();
$domains = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "First 3 domains from database:\n";
foreach ($domains as $domain) {
    echo json_encode([
        'id' => (int)$domain['id'],
        'name' => rtrim($domain['name'], '.'),
        'pdns_zone_id' => (int)$domain['pdns_zone_id'],
        'account_id' => $domain['account_id'] ? (int)$domain['account_id'] : null
    ], JSON_PRETTY_PRINT) . "\n";
}

// Test total count
$count_query = "SELECT COUNT(*) as count FROM domains";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute();
$count = $count_stmt->fetch(PDO::FETCH_ASSOC);

echo "\nTotal domains in database: {$count['count']}\n";
?>
