<?php
require_once '/home/admin/python/pdnsadminuser/php-api/config/config.php';
require_once '/home/admin/python/pdnsadminuser/php-api/config/database.php';

// Test templates functionality
echo "Testing template functionality...\n";

// Get PowerDNS Admin database connection
$pdns_admin_db = new PDNSAdminDatabase();
$pdns_admin_conn = $pdns_admin_db->getConnection();

if (!$pdns_admin_conn) {
    echo "Failed to connect to PowerDNS Admin database\n";
    exit(1);
}

try {
    $stmt = $pdns_admin_conn->prepare("SELECT id, name, description FROM domain_template ORDER BY name");
    $stmt->execute();
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($templates) . " templates:\n";
    foreach ($templates as $template) {
        echo "ID: " . $template['id'] . ", Name: " . $template['name'] . ", Description: " . ($template['description'] ?: 'N/A') . "\n";
    }
    
    // Test template with ID 1 if it exists
    if (count($templates) > 0) {
        $template_id = $templates[0]['id'];
        echo "\nTesting template records for ID $template_id:\n";
        
        $stmt = $pdns_admin_conn->prepare("
            SELECT dtr.name as record_name, dtr.type, dtr.data as content, dtr.ttl, 0 as prio 
            FROM domain_template_record dtr 
            WHERE dtr.template_id = ? 
            ORDER BY dtr.name, dtr.type
        ");
        $stmt->execute([$template_id]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Found " . count($records) . " records for this template:\n";
        foreach ($records as $record) {
            echo "Name: " . $record['record_name'] . ", Type: " . $record['type'] . ", Content: " . $record['content'] . "\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nTemplate test completed successfully.\n";
?>
