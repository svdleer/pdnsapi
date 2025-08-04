<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/database-compat.php';

echo "=== Testing Manual Domain Assignment ===\n\n";

$database = new Database();
$db = $database->getConnection();

// Test assignment creation
echo "1. Creating test assignment (Domain ID: 22, Account ID: 1)...\n";

try {
    // Check if assignment already exists
    $check_query = "SELECT 1 FROM user_domain_assignments WHERE domain_id = 22 AND user_id = 1";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute();
    
    if ($check_stmt->fetch()) {
        echo "Assignment already exists, deleting first...\n";
        $delete_query = "DELETE FROM user_domain_assignments WHERE domain_id = 22 AND user_id = 1";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->execute();
    }
    
    // Create assignment
    $insert_query = "INSERT INTO user_domain_assignments (domain_id, user_id) VALUES (22, 1)";
    $insert_stmt = $db->prepare($insert_query);
    
    if ($insert_stmt->execute()) {
        echo "✅ Assignment created successfully\n";
    } else {
        echo "❌ Failed to create assignment\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Test assignment retrieval
echo "\n2. Testing assignment retrieval...\n";

try {
    $query = "
        SELECT 
            uda.domain_id,
            uda.user_id as account_id,
            uda.assigned_at,
            d.name as domain_name,
            d.pdns_zone_id,
            a.name as account_name,
            a.mail as account_email
        FROM user_domain_assignments uda
        JOIN domains d ON uda.domain_id = d.id
        JOIN accounts a ON uda.user_id = a.id
        LIMIT 5
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($assignments) > 0) {
        echo "✅ Found " . count($assignments) . " assignments:\n";
        foreach ($assignments as $assignment) {
            echo "- Domain: {$assignment['domain_name']} → Account: {$assignment['account_name']} (assigned: {$assignment['assigned_at']})\n";
        }
    } else {
        echo "❌ No assignments found\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error retrieving assignments: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
