<?php
echo "=== SYNCING DOMAINS WITH ACCOUNTS ===\n\n";

// Mock required functions
function sendResponse($code, $data, $message = '') {
    echo "Sync Result: $message\n";
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            echo "  $key: $value\n";
        }
    }
}

function sendError($code, $message) {
    echo "Error: $message\n";
}

function logApiRequest($endpoint, $method, $code) {
    // Silent
}

// Set up environment
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = ['sync' => 'true'];

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/database-compat.php';
require_once 'models/Domain.php';
require_once 'models/Account.php';
require_once 'classes/PDNSAdminClient.php';

$database = new Database();
$db = $database->getConnection();

$pdns_client = new PDNSAdminClient($pdns_config);
$domain = new Domain($db);

echo "Starting domain sync from PowerDNS Admin...\n\n";

// Call the sync function directly
try {
    // Include the sync function from domains.php
    include_once 'api/domains.php';
    
} catch (Exception $e) {
    echo "Sync failed: " . $e->getMessage() . "\n";
}

echo "\n=== SYNC COMPLETE ===\n";
?>
