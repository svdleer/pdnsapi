<?php
// Determine the correct base path
$base_path = realpath(__DIR__ . '/..');

require_once $base_path . '/config/config.php';
require_once $base_path . '/config/database.php';
require_once $base_path . '/includes/database-compat.php';
require_once $base_path . '/models/Account.php';

// API key is already validated in index.php, log the request
logApiRequest('accounts', $_SERVER['REQUEST_METHOD'], 200);

// Database class should now be available through compatibility layer
if (!class_exists('Database')) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database compatibility layer failed']);
    exit;
}

// Get database connections
$database = new Database();
$db = $database->getConnection();

// PowerDNS Admin database connection is not needed - we use API-only approach
$pdns_admin_conn = null;

// Initialize account object
$account = new Account($db);

// Get the HTTP method
$request_method = $_SERVER["REQUEST_METHOD"];

// Get parameters from URL
$account_id = isset($_GET['id']) ? $_GET['id'] : null;
$account_name = isset($_GET['name']) ? $_GET['name'] : null;
$sync = isset($_GET['sync']) ? $_GET['sync'] : null;

switch($request_method) {
    case 'GET':
        if ($sync === 'true') {
            syncAccountsFromPDNSAdminDB($account, $pdns_admin_conn);
        } elseif ($account_id) {
            getAccount($account, $account_id);
        } elseif ($account_name) {
            getAccountByName($account, $account_name);
        } else {
            getAllAccounts($account);
        }
        break;
        
    case 'POST':
        createAccount($account);
        break;
        
    case 'PUT':
        if ($account_id) {
            updateAccount($account, $account_id);
        } else {
            sendError(400, "Account ID required for update");
        }
        break;
        
    case 'DELETE':
        if ($account_id) {
            deleteAccount($account, $account_id);
        } else {
            sendError(400, "Account ID required for deletion");
        }
        break;
        
    default:
        sendError(405, "Method not allowed");
        break;
}

function syncAccountsFromPDNSAdminDB($account, $pdns_admin_conn) {
    global $db, $pdns_config;
    
    // Use PowerDNS Admin API instead of direct database connection
    $client = new PDNSAdminClient($pdns_config);
    
    try {
        // Get all users from PowerDNS Admin API
        $response = $client->makeRequest('/users');
        
        if (!$response || !isset($response['success']) || !$response['success']) {
            sendError(500, "Failed to fetch users from PowerDNS Admin API");
            return;
        }
        
        $pdns_users = $response['data'] ?? [];
        
        if (empty($pdns_users)) {
            sendResponse(200, [], "No users found in PowerDNS Admin");
            return;
        }
        
        $synced_count = 0;
        $updated_count = 0;
    
    // Start a transaction
    $db->beginTransaction();
    
    try {
        foreach ($pdns_users as $pdns_user) {
            $username = $pdns_user['username'];
            $firstname = $pdns_user['firstname'];
            $lastname = $pdns_user['lastname'];
            $email = $pdns_user['email'];
            
            // Check if account exists
            $check_query = "SELECT id FROM accounts WHERE name = ? FOR UPDATE";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$username]);
            $existing_account = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_account) {
                // Update existing account
                $update_query = "UPDATE accounts SET description = ?, contact = ?, mail = ?, updated_at = NOW() WHERE id = ?";
                $update_stmt = $db->prepare($update_query);
                $description = $firstname . ' ' . $lastname;
                
                if ($update_stmt->execute([$description, $description, $email, $existing_account['id']])) {
                    $updated_count++;
                }
            } else {
                // Create new account
                $create_query = "INSERT INTO accounts (name, description, contact, mail, created_at) VALUES (?, ?, ?, ?, NOW())";
                $create_stmt = $db->prepare($create_query);
                $description = $firstname . ' ' . $lastname;
                
                if ($create_stmt->execute([$username, $description, $description, $email])) {
                    $synced_count++;
                }
            }
        }
        
        // Commit the transaction
        $db->commit();
        
        $message = "Database sync completed: {$synced_count} accounts added, {$updated_count} accounts updated from PowerDNS Admin database";
        sendResponse(200, array(
            'synced' => $synced_count,
            'updated' => $updated_count,
            'total_processed' => count($pdns_users)
        ), $message);
        
    } catch (Exception $e) {
        // Rollback the transaction
        $db->rollback();
        error_log("Account sync from database failed: " . $e->getMessage());
        sendError(500, "Account sync from database failed: " . $e->getMessage());
    }
}

function getAllAccounts($account) {
    $stmt = $account->read();
    $num = $stmt->rowCount();
    
    if($num > 0) {
        $accounts_arr = array();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            extract($row);
            
            $account_item = array(
                "id" => $id,
                "name" => $name,
                "description" => $description,
                "contact" => $contact,
                "mail" => $mail,
                "ip_addresses" => $ip_addresses ? json_decode($ip_addresses, true) : [],
                "pdns_account_id" => $pdns_account_id,
                "created_at" => $created_at,
                "updated_at" => $updated_at
            );
            
            array_push($accounts_arr, $account_item);
        }
        
        sendResponse(200, $accounts_arr);
    } else {
        sendResponse(200, array(), "No accounts found");
    }
}

function getAccount($account, $account_id) {
    $account->id = $account_id;
    
    if($account->readOne()) {
        $account_arr = array(
            "id" => $account->id,
            "name" => $account->name,
            "description" => $account->description,
            "contact" => $account->contact,
            "mail" => $account->mail,
            "ip_addresses" => $account->ip_addresses ? json_decode($account->ip_addresses, true) : [],
            "pdns_account_id" => $account->pdns_account_id,
            "created_at" => $account->created_at,
            "updated_at" => $account->updated_at
        );
        
        sendResponse(200, $account_arr);
    } else {
        sendError(404, "Account not found");
    }
}

function getAccountByName($account, $account_name) {
    if($account->readByName($account_name)) {
        $account_arr = array(
            "id" => $account->id,
            "name" => $account->name,
            "description" => $account->description,
            "contact" => $account->contact,
            "mail" => $account->mail,
            "ip_addresses" => $account->ip_addresses ? json_decode($account->ip_addresses, true) : [],
            "pdns_account_id" => $account->pdns_account_id,
            "created_at" => $account->created_at,
            "updated_at" => $account->updated_at
        );
        
        sendResponse(200, $account_arr);
    } else {
        sendError(404, "Account not found");
    }
}

function createAccount($account) {
    $data = json_decode(file_get_contents("php://input"));
    
    if(!empty($data->name)) {
        $account->name = $data->name;
        $account->description = $data->description ?? '';
        $account->contact = $data->contact ?? '';
        $account->mail = $data->mail ?? '';
        $account->ip_addresses = isset($data->ip_addresses) ? json_encode($data->ip_addresses) : json_encode([]);
        
        if($account->create()) {
            sendResponse(201, null, "Account created successfully");
        } else {
            sendError(503, "Unable to create account");
        }
    } else {
        sendError(400, "Account name is required");
    }
}

function updateAccount($account, $account_id) {
    $data = json_decode(file_get_contents("php://input"));
    
    $account->id = $account_id;
    
    if($account->readOne()) {
        $account->name = $data->name ?? $account->name;
        $account->description = $data->description ?? $account->description;
        $account->contact = $data->contact ?? $account->contact;
        $account->mail = $data->mail ?? $account->mail;
        $account->ip_addresses = isset($data->ip_addresses) ? json_encode($data->ip_addresses) : $account->ip_addresses;
        
        if($account->update()) {
            sendResponse(200, null, "Account updated successfully");
        } else {
            sendError(503, "Unable to update account");
        }
    } else {
        sendError(404, "Account not found");
    }
}

function deleteAccount($account, $account_id) {
    $account->id = $account_id;
    
    if($account->readOne()) {
        if($account->delete()) {
            sendResponse(200, null, "Account deleted successfully");
        } else {
            sendError(503, "Unable to delete account");
        }
    } else {
        sendError(404, "Account not found");
    }
}
?>
