<?php
// Determine the correct base path
$base_path = realpath(__DIR__ . '/..');

require_once $base_path . '/config/config.php';
require_once $base_path . '/config/database.php';
require_once $base_path . '/includes/database-compat.php';
require_once $base_path . '/models/Account.php';
require_once $base_path . '/models/Domain.php';

// Database class should now be available through compatibility layer
if (!class_exists('Database')) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database compatibility layer failed']);
    exit;
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get HTTP method and action
$request_method = $_SERVER["REQUEST_METHOD"];
$action = $_GET['action'] ?? '';

if ($request_method !== 'POST') {
    sendError(405, "Only POST method allowed for domain-account operations");
}

switch($action) {
    case 'add':
        addDomainToAccount($db);
        break;
    case 'remove':
        removeDomainFromAccount($db);
        break;
    case 'list':
        listAccountDomains($db);
        break;
    default:
        sendError(400, "Action required. Available actions: add, remove, list");
        break;
}

function addDomainToAccount($db) {
    $data = json_decode(file_get_contents("php://input"));
    
    if (empty($data->domain_name) || empty($data->account_id)) {
        sendError(400, "domain_name and account_id are required");
    }
    
    $domain = new Domain($db);
    $account = new Account($db);
    
    // Verify account exists
    $account->id = $data->account_id;
    if (!$account->readOne()) {
        sendError(404, "Account not found");
    }
    
    // Add domain to account
    if ($domain->addToAccount($data->domain_name, $data->account_id)) {
        sendResponse(200, [
            'domain_name' => $data->domain_name,
            'account_id' => $data->account_id,
            'account_name' => $account->name
        ], "Domain added to account successfully");
    } else {
        sendError(500, "Failed to add domain to account");
    }
}

function removeDomainFromAccount($db) {
    $data = json_decode(file_get_contents("php://input"));
    
    if (empty($data->domain_name)) {
        sendError(400, "domain_name is required");
    }
    
    $domain = new Domain($db);
    
    // First, check if the domain is currently assigned to admin (account_id = 1)
    // Admin domains cannot be unassigned
    $domain->name = $data->domain_name;
    if ($domain->readByName()) {
        // Use strict comparison and explicit check for integer 1
        if ($domain->account_id !== null && (int)$domain->account_id === 1) {
            sendError(403, "Cannot remove domain from admin account (account_id: 1). Admin domains cannot be unassigned.");
        }
    } else {
        sendError(404, "Domain not found");
    }
    
    // Remove domain from account
    if ($domain->removeFromAccount($data->domain_name)) {
        sendResponse(200, [
            'domain_name' => $data->domain_name
        ], "Domain removed from account successfully");
    } else {
        sendError(500, "Failed to remove domain from account");
    }
}

function listAccountDomains($db) {
    $data = json_decode(file_get_contents("php://input"));
    
    if (empty($data->account_id)) {
        sendError(400, "account_id is required");
    }
    
    $domain = new Domain($db);
    $account = new Account($db);
    
    // Verify account exists
    $account->id = $data->account_id;
    if (!$account->readOne()) {
        sendError(404, "Account not found");
    }
    
    // Get domains for account
    // Admin (account_id = 1) can see ALL domains, regardless of ownership
    // But each domain should show its actual account assignment
    if ($data->account_id == 1) {
        $stmt = $domain->read(); // Get all domains for admin
    } else {
        $stmt = $domain->readByAccountId($data->account_id);
    }
    $domains_arr = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $domain_item = array(
            "id" => $row['id'], // Our internal domain ID
            "name" => $row['name'],
            "pdns_zone_id" => $row['pdns_zone_id'], // PowerDNS Admin zone ID
            "account_id" => $row['account_id'], // Include account_id
            "account_name" => $row['account_name'], // Include account_name (from JOIN)
            "created_at" => $row['created_at'],
            "updated_at" => $row['updated_at']
        );
        array_push($domains_arr, $domain_item);
    }
    
    $response_message = $data->account_id == 1 
        ? "All domains retrieved for admin user" 
        : "Account domains retrieved successfully";
        
    sendResponse(200, [
        'account' => [
            'id' => $account->id,
            'name' => $account->username ?? $account->name
        ],
        'domains' => $domains_arr,
        'domain_count' => count($domains_arr)
    ], $response_message);
}
?>
