<?php
// Determine the correct base path
$base_path = realpath(__DIR__ . '/..');

require_once $base_path . '/config/config.php';
require_once $base_path . '/config/database.php';
require_once $base_path . '/includes/database-compat.php';
require_once $base_path . '/models/Account.php';
require_once $base_path . '/classes/PDNSAdminClient.php';

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

// Get PowerDNS Admin database connection for READ operations
$pdns_admin_conn = null;
if (class_exists('PDNSAdminDatabase')) {
    $pdns_admin_db = new PDNSAdminDatabase();
    $pdns_admin_conn = $pdns_admin_db->getConnection();
}

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
    global $db;
    
    if (!$pdns_admin_conn) {
        sendError(500, "Failed to connect to PowerDNS Admin database");
        return;
    }
    
    // Get all users from PowerDNS Admin database (READ operation - use DB)
    $users_query = "SELECT id, username, firstname, lastname, email FROM user";
    $stmt = $pdns_admin_conn->prepare($users_query);
    $stmt->execute();
    $pdns_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($pdns_users)) {
        sendError(500, "No users found in PowerDNS Admin database");
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
    global $pdns_config;
    
    $data = json_decode(file_get_contents("php://input"));
    
    if(!empty($data->name)) {
        // First create in PowerDNS Admin via API (WRITE operation - use API)
        $client = new PDNSAdminClient($pdns_config);
        
        $pdns_data = [
            'username' => $data->name,
            'firstname' => $data->contact ?? $data->name,
            'lastname' => '',
            'email' => $data->mail ?? '',
            'password' => bin2hex(random_bytes(16)), // Generate a random password
            'role' => 'User' // Default role
        ];
        
        $api_response = $client->makeRequest('/users', 'POST', $pdns_data);
        
        if (!$api_response || !isset($api_response['success']) || !$api_response['success']) {
            sendError(500, "Failed to create account in PowerDNS Admin: " . ($api_response['msg'] ?? 'Unknown error'));
            return;
        }
        
        // Then create in local database
        $account->name = $data->name;
        $account->description = $data->description ?? '';
        $account->contact = $data->contact ?? '';
        $account->mail = $data->mail ?? '';
        $account->ip_addresses = isset($data->ip_addresses) ? json_encode($data->ip_addresses) : json_encode([]);
        
        // Store the PowerDNS Admin user ID if available
        if (isset($api_response['data']['id'])) {
            $account->pdns_account_id = $api_response['data']['id'];
        }
        
        if($account->create()) {
            sendResponse(201, null, "Account created successfully in both PowerDNS Admin and local database");
        } else {
            sendError(503, "Account created in PowerDNS Admin but failed to create in local database");
        }
    } else {
        sendError(400, "Account name is required");
    }
}

function updateAccount($account, $account_id) {
    global $pdns_config;
    
    $data = json_decode(file_get_contents("php://input"));
    
    $account->id = $account_id;
    
    if($account->readOne()) {
        // Update in PowerDNS Admin via API if we have a PowerDNS Admin ID (WRITE operation - use API)
        if ($account->pdns_account_id) {
            $client = new PDNSAdminClient($pdns_config);
            
            $pdns_data = [];
            if (isset($data->contact)) $pdns_data['firstname'] = $data->contact;
            if (isset($data->mail)) $pdns_data['email'] = $data->mail;
            
            if (!empty($pdns_data)) {
                $api_response = $client->makeRequest('/users/' . $account->pdns_account_id, 'PUT', $pdns_data);
                
                if (!$api_response || !isset($api_response['success']) || !$api_response['success']) {
                    error_log("Failed to update account in PowerDNS Admin: " . ($api_response['msg'] ?? 'Unknown error'));
                    // Continue with local update even if PowerDNS Admin update fails
                }
            }
        }
        
        // Update in local database
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
