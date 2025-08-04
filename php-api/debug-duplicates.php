<?php
require_once 'config/config.php';
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Check for duplicate domain names
$query = "
    SELECT name, COUNT(*) as count 
    FROM domains 
    GROUP BY name 
    HAVING COUNT(*) > 1 
    ORDER BY count DESC 
    LIMIT 10
";

$stmt = $db->prepare($query);
$stmt->execute();
$duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Duplicate domains:\n";
foreach ($duplicates as $dup) {
    echo "Name: {$dup['name']}, Count: {$dup['count']}\n";
}

// Check total count
$total_query = "SELECT COUNT(*) as total FROM domains";
$total_stmt = $db->prepare($total_query);
$total_stmt->execute();
$total = $total_stmt->fetch(PDO::FETCH_ASSOC);

echo "\nTotal domains in database: {$total['total']}\n";

// Check domains with pdns_zone_id = 0
$zero_query = "SELECT COUNT(*) as zero_count FROM domains WHERE pdns_zone_id = 0 OR pdns_zone_id IS NULL";
$zero_stmt = $db->prepare($zero_query);
$zero_stmt->execute();
$zero_count = $zero_stmt->fetch(PDO::FETCH_ASSOC);

echo "Domains with pdns_zone_id = 0 or NULL: {$zero_count['zero_count']}\n";

// Check domains with proper pdns_zone_id
$proper_query = "SELECT COUNT(*) as proper_count FROM domains WHERE pdns_zone_id > 0";
$proper_stmt = $db->prepare($proper_query);
$proper_stmt->execute();
$proper_count = $proper_stmt->fetch(PDO::FETCH_ASSOC);

echo "Domains with proper pdns_zone_id: {$proper_count['proper_count']}\n";
?>
