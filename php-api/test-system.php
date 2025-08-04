<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/database-compat.php';
require_once 'models/Account.php';
require_once 'classes/PDNSAdminClient.php';

echo "=== Testing User-Domain Connection System ===\n\n";

// Test database connection
echo "1. Testing database connection...\n";
try {
    $database = new Database();
    $db = $database->getConnection();
    if ($db) {
        echo "✅ Database connection successful\n\n";
    } else {
        echo "❌ Database connection failed\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test PowerDNS Admin client
echo "2. Testing PowerDNS Admin client...\n";
try {
    $client = new PDNSAdminClient($pdns_config);
    echo "✅ PDNSAdmin client initialized\n\n";
} catch (Exception $e) {
    echo "❌ PDNSAdmin client error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test user retrieval
echo "3. Testing user retrieval from PowerDNS Admin...\n";
$users_response = $client->getAllUsers();
echo "Status Code: " . $users_response['status_code'] . "\n";
echo "Response Length: " . strlen($users_response['raw_response']) . " bytes\n";

if ($users_response['status_code'] === 200) {
    echo "✅ Users retrieved successfully\n";
    $users = $users_response['data'];
    if (is_array($users)) {
        echo "Found " . count($users) . " users\n";
        if (count($users) > 0) {
            echo "First user: " . json_encode($users[0], JSON_PRETTY_PRINT) . "\n";
        }
    }
} else {
    echo "❌ Failed to retrieve users\n";
    echo "Raw response: " . substr($users_response['raw_response'], 0, 500) . "\n";
}

echo "\n4. Testing zones retrieval from PowerDNS Admin...\n";
$zones_response = $client->getAllZones();
echo "Status Code: " . $zones_response['status_code'] . "\n";
echo "Response Length: " . strlen($zones_response['raw_response']) . " bytes\n";

if ($zones_response['status_code'] === 200) {
    echo "✅ Zones retrieved successfully\n";
    $zones = $zones_response['data'];
    if (is_array($zones)) {
        echo "Found " . count($zones) . " zones\n";
        if (count($zones) > 0) {
            echo "First zone: " . json_encode($zones[0], JSON_PRETTY_PRINT) . "\n";
            
            // Check for account info in zones
            $zonesWithAccount = 0;
            foreach ($zones as $zone) {
                if (!empty($zone['account'])) {
                    $zonesWithAccount++;
                }
            }
            echo "Zones with account info: $zonesWithAccount/" . count($zones) . "\n";
        }
    }
} else {
    echo "❌ Failed to retrieve zones\n";
    echo "Raw response: " . substr($zones_response['raw_response'], 0, 500) . "\n";
}

echo "\n5. Testing local database tables...\n";

// Check if tables exist
$tables = ['accounts', 'domains', 'user_domain_assignments'];
foreach ($tables as $table) {
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ Table '$table' exists with " . $result['count'] . " records\n";
    } catch (Exception $e) {
        echo "❌ Table '$table' error: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Test Complete ===\n";
?>
