<?php
echo "=== DEBUGGING DOMAINS API ISSUE ===\n\n";

// Test database connections
echo "1. TESTING DATABASE CONNECTIONS:\n";

require_once '../config/database.php';

// Test main database
echo "Main database: ";
$database = new Database();
$db = $database->getConnection();
if ($db) {
    echo "✅ Connected\n";
} else {
    echo "❌ Failed\n";
}

// Test PowerDNS Admin database
echo "PowerDNS Admin database: ";
$pdns_admin_db = new PDNSAdminDatabase();
$pdns_admin_conn = $pdns_admin_db->getConnection();
if ($pdns_admin_conn) {
    echo "✅ Connected\n";
} else {
    echo "❌ Failed\n";
}

echo "\n";

// Test basic domain model
echo "2. TESTING DOMAIN MODEL:\n";
require_once '../models/Domain.php';

try {
    $domain = new Domain($db);
    echo "Domain model: ✅ Created successfully\n";
} catch (Exception $e) {
    echo "Domain model: ❌ Error - " . $e->getMessage() . "\n";
}

echo "\n";

// Test PDNSAdminClient
echo "3. TESTING PDNS ADMIN CLIENT:\n";
require_once '../config/config.php';
require_once '../classes/PDNSAdminClient.php';

try {
    $pdns_client = new PDNSAdminClient($pdns_config);
    echo "PDNSAdminClient: ✅ Created successfully\n";
} catch (Exception $e) {
    echo "PDNSAdminClient: ❌ Error - " . $e->getMessage() . "\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
?>
