<?php
require_once 'config/config.php';
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "Starting domain cleanup...\n";

// Find all domains that have duplicates (with and without trailing dot)
$duplicate_query = "
    SELECT d1.id as dot_id, d1.name as dot_name, d1.pdns_zone_id as dot_zone_id,
           d2.id as no_dot_id, d2.name as no_dot_name, d2.pdns_zone_id as no_dot_zone_id
    FROM domains d1
    JOIN domains d2 ON CONCAT(d2.name, '.') = d1.name
    WHERE d1.name LIKE '%.'
    AND d2.name NOT LIKE '%.'
    LIMIT 10
";

$stmt = $db->prepare($duplicate_query);
$stmt->execute();
$duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found duplicate pairs (showing first 10):\n";
foreach ($duplicates as $dup) {
    echo "With dot - ID: {$dup['dot_id']}, Name: '{$dup['dot_name']}', Zone ID: {$dup['dot_zone_id']}\n";
    echo "Without dot - ID: {$dup['no_dot_id']}, Name: '{$dup['no_dot_name']}', Zone ID: {$dup['no_dot_zone_id']}\n";
    echo "---\n";
}

// Count total duplicates
$count_query = "
    SELECT COUNT(*) as count
    FROM domains d1
    JOIN domains d2 ON CONCAT(d2.name, '.') = d1.name
    WHERE d1.name LIKE '%.'
    AND d2.name NOT LIKE '%.'
";

$count_stmt = $db->prepare($count_query);
$count_stmt->execute();
$count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);

echo "Total duplicate pairs found: {$count_result['count']}\n";
echo "\nTo clean up, we should delete domains with trailing dots that have corresponding entries without dots.\n";
echo "This would delete {$count_result['count']} duplicate domains.\n";

// Show what would be deleted
echo "\nFirst 5 domains that would be deleted:\n";
$delete_preview_query = "
    SELECT d1.id, d1.name, d1.pdns_zone_id
    FROM domains d1
    JOIN domains d2 ON CONCAT(d2.name, '.') = d1.name
    WHERE d1.name LIKE '%.'
    AND d2.name NOT LIKE '%.'
    LIMIT 5
";

$preview_stmt = $db->prepare($delete_preview_query);
$preview_stmt->execute();
$to_delete = $preview_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($to_delete as $domain) {
    echo "ID: {$domain['id']}, Name: '{$domain['name']}', Zone ID: {$domain['pdns_zone_id']}\n";
}
?>
