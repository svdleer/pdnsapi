<?php
echo "=== CHECKING USER-DOMAIN RELATIONSHIPS ===\n\n";

require_once 'config/database.php';

// Connect to PowerDNS Admin database
$pdns_admin_db = new PDNSAdminDatabase();
$conn = $pdns_admin_db->getConnection();

if (!$conn) {
    echo "❌ Failed to connect to PowerDNS Admin database\n";
    exit(1);
}

echo "✅ Connected to PowerDNS Admin database\n\n";

// 1. Check users
echo "1. USERS IN POWERDNS ADMIN:\n";
$users_query = "SELECT id, username, firstname, lastname, email FROM user LIMIT 10";
$stmt = $conn->prepare($users_query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($users) > 0) {
    foreach ($users as $user) {
        echo "   ID: {$user['id']}, Username: {$user['username']}, Name: {$user['firstname']} {$user['lastname']}, Email: {$user['email']}\n";
    }
} else {
    echo "   No users found\n";
}
echo "\n";

// 2. Check domain-user relationships
echo "2. DOMAIN-USER RELATIONSHIPS:\n";
$domain_user_query = "SELECT COUNT(*) as total FROM domain_user";
$stmt = $conn->prepare($domain_user_query);
$stmt->execute();
$total_relations = $stmt->fetch(PDO::FETCH_ASSOC);

echo "   Total domain-user relationships: {$total_relations['total']}\n";

if ($total_relations['total'] > 0) {
    echo "\n3. SAMPLE DOMAIN-USER RELATIONSHIPS:\n";
    $sample_query = "
        SELECT du.domain_id, d.name as domain_name, du.user_id, u.username, u.firstname, u.lastname 
        FROM domain_user du
        JOIN domain d ON du.domain_id = d.id 
        JOIN user u ON du.user_id = u.id 
        LIMIT 10
    ";
    $stmt = $conn->prepare($sample_query);
    $stmt->execute();
    $relationships = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($relationships as $rel) {
        echo "   Domain: {$rel['domain_name']} → User: {$rel['username']} ({$rel['firstname']} {$rel['lastname']})\n";
    }
    
    echo "\n4. USERS WITH MOST DOMAINS:\n";
    $user_stats_query = "
        SELECT u.username, u.firstname, u.lastname, u.email, COUNT(du.domain_id) as domain_count
        FROM user u
        JOIN domain_user du ON u.id = du.user_id
        GROUP BY u.id
        ORDER BY domain_count DESC
        LIMIT 10
    ";
    $stmt = $conn->prepare($user_stats_query);
    $stmt->execute();
    $user_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($user_stats as $stat) {
        echo "   {$stat['username']} ({$stat['firstname']} {$stat['lastname']}): {$stat['domain_count']} domains\n";
    }
} else {
    echo "   No domain-user relationships found\n";
}

echo "\n=== ANALYSIS COMPLETE ===\n";
?>
