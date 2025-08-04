<?php
require_once 'config/config.php';
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "Starting database cleanup...\n";

// Get current count
$count_query = "SELECT COUNT(*) as count FROM domains";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute();
$count = $count_stmt->fetch(PDO::FETCH_ASSOC);

echo "Current domains in database: {$count['count']}\n";

// Also check user_domain_assignments
$assignments_query = "SELECT COUNT(*) as count FROM user_domain_assignments";
$assignments_stmt = $db->prepare($assignments_query);
$assignments_stmt->execute();
$assignments_count = $assignments_stmt->fetch(PDO::FETCH_ASSOC);
echo "Current user_domain_assignments: {$assignments_count['count']}\n";

try {
    // Disable foreign key checks temporarily
    echo "Disabling foreign key checks...\n";
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");

    // Empty both tables
    echo "Emptying user_domain_assignments table...\n";
    $db->exec("TRUNCATE TABLE user_domain_assignments");

    echo "Emptying domains table...\n";
    $db->exec("TRUNCATE TABLE domains");

    // Re-enable foreign key checks
    echo "Re-enabling foreign key checks...\n";
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "✅ Both tables emptied successfully!\n";
    
    // Verify tables are empty
    $verify_domains = $db->prepare("SELECT COUNT(*) as count FROM domains");
    $verify_domains->execute();
    $domains_left = $verify_domains->fetch(PDO::FETCH_ASSOC);
    
    $verify_assignments = $db->prepare("SELECT COUNT(*) as count FROM user_domain_assignments");
    $verify_assignments->execute();
    $assignments_left = $verify_assignments->fetch(PDO::FETCH_ASSOC);
    
    echo "Domains remaining: {$domains_left['count']}\n";
    echo "Assignments remaining: {$assignments_left['count']}\n";
    
    if ($domains_left['count'] == 0 && $assignments_left['count'] == 0) {
        echo "✅ Database is now clean and ready for fresh sync!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error during cleanup: " . $e->getMessage() . "\n";
    // Make sure to re-enable foreign key checks even if there's an error
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
}
?>
