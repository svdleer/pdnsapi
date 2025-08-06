<?php
/**
 * Explore PowerDNS Admin Template Tables
 */
require_once __DIR__ . '/php-api/config/database.php';

// Get PowerDNS Admin database connection
$pdns_admin_db = new PDNSAdminDatabase();
$pdns_conn = $pdns_admin_db->getConnection();

if (!$pdns_conn) {
    die("Could not connect to PowerDNS Admin database\n");
}

echo "=== PowerDNS Admin Template Tables Structure ===\n\n";

// Check what template-related tables exist
try {
    // Get all tables that contain 'template' in the name
    $stmt = $pdns_conn->prepare("SHOW TABLES LIKE '%template%'");
    $stmt->execute();
    $template_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Template Tables Found:\n";
    foreach ($template_tables as $table) {
        echo "- $table\n";
    }
    echo "\n";
    
    // If domain_template table exists, show its structure
    if (in_array('domain_template', $template_tables)) {
        echo "=== domain_template table structure ===\n";
        $stmt = $pdns_conn->prepare("DESCRIBE domain_template");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $column) {
            echo "{$column['Field']} - {$column['Type']} - {$column['Null']} - {$column['Key']}\n";
        }
        echo "\n";
        
        // Show sample data
        echo "=== Sample domain_template data ===\n";
        $stmt = $pdns_conn->prepare("SELECT * FROM domain_template LIMIT 5");
        $stmt->execute();
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($templates as $template) {
            print_r($template);
        }
        echo "\n";
    }
    
    // If domain_template_record table exists, show its structure
    if (in_array('domain_template_record', $template_tables)) {
        echo "=== domain_template_record table structure ===\n";
        $stmt = $pdns_conn->prepare("DESCRIBE domain_template_record");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $column) {
            echo "{$column['Field']} - {$column['Type']} - {$column['Null']} - {$column['Key']}\n";
        }
        echo "\n";
        
        // Show sample data
        echo "=== Sample domain_template_record data ===\n";
        $stmt = $pdns_conn->prepare("SELECT * FROM domain_template_record LIMIT 10");
        $stmt->execute();
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($records as $record) {
            print_r($record);
        }
        echo "\n";
    }
    
    // Get complete template with records for a specific template
    if (in_array('domain_template', $template_tables) && in_array('domain_template_record', $template_tables)) {
        echo "=== Complete template example (template + records) ===\n";
        
        // Get first template
        $stmt = $pdns_conn->prepare("SELECT * FROM domain_template LIMIT 1");
        $stmt->execute();
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($template) {
            echo "Template: " . $template['name'] . " (ID: " . $template['id'] . ")\n";
            echo "Description: " . $template['description'] . "\n\n";
            
            // Get records for this template
            $stmt = $pdns_conn->prepare("SELECT * FROM domain_template_record WHERE template_id = ?");
            $stmt->execute([$template['id']]);
            $template_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "Template Records:\n";
            foreach ($template_records as $record) {
                echo "  {$record['name']} {$record['type']} {$record['data']} (TTL: {$record['ttl']}, Status: " . ($record['status'] ? 'Active' : 'Disabled') . ")\n";
                if (!empty($record['comment'])) {
                    echo "    Comment: {$record['comment']}\n";
                }
            }
            echo "\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Error exploring templates: " . $e->getMessage() . "\n";
}

echo "=== End of Template Exploration ===\n";
?>
