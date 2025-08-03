<?php
// Determine the correct base path
$base_path = realpath(__DIR__ . '/..');

require_once $base_path . '/config/config.php';
require_once $base_path . '/config/database.php';
require_once $base_path . '/models/Account.php';
require_once $base_path . '/models/Domain.php';

// Verify Database class is available
if (!class_exists('Database')) {
    // Try alternative include paths as fallback
    $alternative_paths = [
        __DIR__ . '/../config/database.php',
        dirname(__FILE__) . '/../config/database.php',
        realpath(__DIR__ . '/..') . '/config/database.php'
    ];
    
    foreach ($alternative_paths as $path) {
        if (file_exists($path) && !class_exists('Database')) {
            require_once $path;
            break;
        }
    }
    
    // If still not available, provide detailed error
    if (!class_exists('Database')) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Database class configuration error',
            'debug' => [
                'base_path' => $base_path,
                'working_dir' => getcwd(),
                '__DIR__' => __DIR__,
                'tried_paths' => $alternative_paths,
                'class_exists' => class_exists('Database', false)
            ]
        ]);
        exit;
    }
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
    $stmt = $domain->readByAccountId($data->account_id);
    $domains_arr = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $domain_item = array(
            "id" => $row['id'],
            "name" => $row['name'],
            "type" => $row['type'],
            "kind" => $row['kind'],
            "dnssec" => (bool)$row['dnssec'],
            "created_at" => $row['created_at'],
            "updated_at" => $row['updated_at']
        );
        array_push($domains_arr, $domain_item);
    }
    
    sendResponse(200, [
        'account' => [
            'id' => $account->id,
            'name' => $account->name
        ],
        'domains' => $domains_arr,
        'domain_count' => count($domains_arr)
    ], "Account domains retrieved successfully");
}
?>
