<?php
echo "=== TESTING DOMAINS API ENDPOINT ===\n\n";

// Mock the functions that are typically in the main API framework
function sendResponse($code, $data, $message = '') {
    echo "HTTP $code Response:\n";
    if (!empty($message)) {
        echo "Message: $message\n";
    }
    if (is_array($data)) {
        echo "Data: " . count($data) . " items\n";
        if (count($data) > 0 && count($data) <= 3) {
            echo "Sample data:\n";
            foreach ($data as $i => $item) {
                if (isset($item['name'])) {
                    echo "  " . ($i + 1) . ". " . $item['name'] . " (ID: " . $item['id'] . ")\n";
                }
            }
        } elseif (count($data) > 3) {
            echo "Sample data (first 3):\n";
            for ($i = 0; $i < 3; $i++) {
                if (isset($data[$i]['name'])) {
                    echo "  " . ($i + 1) . ". " . $data[$i]['name'] . " (ID: " . $data[$i]['id'] . ")\n";
                }
            }
        }
    } else {
        echo "Data: " . $data . "\n";
    }
    echo "\n";
}

function sendError($code, $message) {
    echo "HTTP $code Error: $message\n\n";
}

function logApiRequest($endpoint, $method, $code) {
    echo "API Log: $method /$endpoint -> HTTP $code\n";
}

// Set up the environment
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = array();

// Test the domains API by including its logic
$base_path = realpath(__DIR__);

require_once $base_path . '/config/config.php';
require_once $base_path . '/config/database.php';
require_once $base_path . '/includes/database-compat.php';
require_once $base_path . '/models/Domain.php';
require_once $base_path . '/models/Account.php';
require_once $base_path . '/classes/PDNSAdminClient.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize domain object
$domain = new Domain($db);
$account = new Account($db);

echo "1. TESTING getAllDomains() FUNCTION\n";

// Test the getAllDomains function directly
try {
    $stmt = $domain->read();
    $num = $stmt->rowCount();
    
    echo "Query returned $num rows\n";
    
    if($num > 0) {
        $domains_arr = array();
        $count = 0;
        
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) && $count < 5) {
            extract($row);
            
            $domain_item = array(
                "id" => $id,
                "name" => $name,
                "type" => $type,
                "account_id" => $account_id,
                "account_name" => $account_name,
                "pdns_zone_id" => $pdns_zone_id,
                "kind" => $kind,
                "masters" => $masters,
                "dnssec" => (bool)$dnssec,
                "account" => $account,
                "created_at" => $created_at,
                "updated_at" => $updated_at
            );
            
            array_push($domains_arr, $domain_item);
            $count++;
        }
        
        sendResponse(200, $domains_arr, "Sample domains retrieved successfully");
    } else {
        sendResponse(200, array(), "No domains found");
    }
    
} catch (Exception $e) {
    sendError(500, "Error in getAllDomains: " . $e->getMessage());
}

echo "2. TESTING getDomain() FUNCTION\n";

// Test getting a specific domain
try {
    $domain->id = 1;  // Test with domain ID 1
    
    if($domain->readOne()) {
        $domain_arr = array(
            "id" => $domain->id,
            "name" => $domain->name,
            "type" => $domain->type,
            "account_id" => $domain->account_id,
            "pdns_zone_id" => $domain->pdns_zone_id,
            "kind" => $domain->kind,
            "masters" => $domain->masters,
            "dnssec" => (bool)$domain->dnssec,
            "account" => $domain->account,
            "created_at" => $domain->created_at,
            "updated_at" => $domain->updated_at
        );
        
        sendResponse(200, $domain_arr, "Single domain retrieved successfully");
    } else {
        sendError(404, "Domain with ID 1 not found");
    }
    
} catch (Exception $e) {
    sendError(500, "Error in getDomain: " . $e->getMessage());
}

echo "3. TESTING getDomainsByAccount() FUNCTION\n";

// Test getting domains by account
try {
    $test_account_id = 1;  // Test with account ID 1
    $stmt = $domain->readByAccountId($test_account_id);
    $num = $stmt->rowCount();
    
    echo "Found $num domains for account ID $test_account_id\n";
    
    if($num > 0) {
        $domains_arr = array();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            extract($row);
            
            $domain_item = array(
                "id" => $id,
                "name" => $name,
                "type" => $type,
                "account_id" => $account_id,
                "account_name" => $account_name,
                "pdns_zone_id" => $pdns_zone_id,
                "kind" => $kind,
                "masters" => $masters,
                "dnssec" => (bool)$dnssec,
                "account" => $account,
                "created_at" => $created_at,
                "updated_at" => $updated_at
            );
            
            array_push($domains_arr, $domain_item);
        }
        
        sendResponse(200, $domains_arr, "Domains for account retrieved successfully");
    } else {
        sendResponse(200, array(), "No domains found for this account");
    }
    
} catch (Exception $e) {
    sendError(500, "Error in getDomainsByAccount: " . $e->getMessage());
}

echo "=== API ENDPOINT TEST COMPLETE ===\n";
?>
