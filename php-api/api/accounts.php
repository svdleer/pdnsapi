<?php
// Determine the correct base path
$base_path = realpath(__DIR__ . '/..');

require_once $base_path . '/config/config.php';
require_once $base_path . '/config/database.php';
require_once $base_path . '/includes/database-compat.php';
require_once $base_path . '/models/Account.php';
require_once $base_path . '/classes/PDNSAdm        sendResponse(201, [
            'id' => $db->lastInsertId(),
            'username' => $account->username,
            'firstname' => $account->firstname,
            'lastname' => $account->lastname,
            'email' => $account->email,
            'role_id' => $account->role_id,
            'pdns_account_id' => $account->pdns_account_id,
            'ip_addresses' => $validated_ips,
            'customer_id' => $customer_id
        ], "Account created successfully in PowerDNS Admin and synced to local database");p';

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
    
    // Get all users from PowerDNS Admin database
    $users_query = "SELECT id, username, firstname, lastname, email, role_id FROM user";
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
            // Check if account exists
            $check_query = "SELECT id, ip_addresses, customer_id FROM accounts WHERE username = ? FOR UPDATE";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$pdns_user['username']]);
            $existing_account = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_account) {
                // Update existing account (preserve ip_address and customer_id)
                $update_query = "UPDATE accounts SET firstname = ?, lastname = ?, email = ?, role_id = ?, pdns_account_id = ?, updated_at = NOW() WHERE username = ?";
                $update_stmt = $db->prepare($update_query);
                
                if ($update_stmt->execute([
                    $pdns_user['firstname'],
                    $pdns_user['lastname'], 
                    $pdns_user['email'],
                    $pdns_user['role_id'],
                    $pdns_user['id'],
                    $pdns_user['username']
                ])) {
                    $updated_count++;
                }
            } else {
                // Create new account
                $create_query = "INSERT INTO accounts (username, firstname, lastname, email, role_id, pdns_account_id, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
                $create_stmt = $db->prepare($create_query);
                
                if ($create_stmt->execute([
                    $pdns_user['username'],
                    $pdns_user['firstname'],
                    $pdns_user['lastname'],
                    $pdns_user['email'],
                    $pdns_user['role_id'],
                    $pdns_user['id']
                ])) {
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
                "username" => $username,
                "firstname" => $firstname,
                "lastname" => $lastname,
                "email" => $email,
                "role_id" => $role_id,
                "ip_addresses" => $ip_addresses ? json_decode($ip_addresses, true) : [],
                "customer_id" => $customer_id,
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
            "username" => $account->username,
            "firstname" => $account->firstname,
            "lastname" => $account->lastname,
            "email" => $account->email,
            "role_id" => $account->role_id,
            "ip_addresses" => $account->ip_addresses ? json_decode($account->ip_addresses, true) : [],
            "customer_id" => $account->customer_id,
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
            "username" => $account->username,
            "firstname" => $account->firstname,
            "lastname" => $account->lastname,
            "email" => $account->email,
            "role_id" => $account->role_id,
            "ip_addresses" => $account->ip_addresses ? json_decode($account->ip_addresses, true) : [],
            "customer_id" => $account->customer_id,
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
    global $pdns_config, $db, $pdns_admin_conn;
    
    $data = json_decode(file_get_contents("php://input"));
    
    // Validate required PowerDNS Admin fields
    if (empty($data->username) || empty($data->plain_text_password) || empty($data->firstname) || empty($data->email)) {
        sendError(400, "Username, plain_text_password, firstname, and email are required");
        return;
    }
    
    // Validate IP addresses if provided
    $validated_ips = [];
    if (isset($data->ip_addresses) && is_array($data->ip_addresses)) {
        foreach ($data->ip_addresses as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                sendError(400, "Invalid IP address format: " . $ip);
                return;
            }
            $validated_ips[] = $ip;
        }
    }
    
    // Validate customer_id if provided
    $customer_id = null;
    if (isset($data->customer_id)) {
        if (!is_numeric($data->customer_id) || $data->customer_id <= 0) {
            sendError(400, "customer_id must be a positive integer");
            return;
        }
        $customer_id = (int)$data->customer_id;
    }
    
    // Create user in PowerDNS Admin via API
    $client = new PDNSAdminClient($pdns_config);
    
    $pdns_data = [
        'username' => $data->username,
        'plain_text_password' => $data->plain_text_password,
        'firstname' => $data->firstname,
        'lastname' => $data->lastname ?? '',
        'email' => $data->email,
        'role' => $data->role ?? ['id' => 2, 'name' => 'User']
    ];
    
    $api_response = $client->makeRequest('/pdnsadmin/users', 'POST', $pdns_data);
    
    if (!$api_response || $api_response['status_code'] !== 201) {
        $error_msg = "Unknown error";
        if (isset($api_response['data']['msg'])) {
            $error_msg = $api_response['data']['msg'];
        } elseif (isset($api_response['raw_response'])) {
            $error_msg = substr($api_response['raw_response'], 0, 200);
        }
        sendError(500, "Failed to create user in PowerDNS Admin: " . $error_msg);
        return;
    }
    
    // Sync users from PowerDNS Admin to get the created user
    if (!$pdns_admin_conn) {
        sendError(500, "Failed to connect to PowerDNS Admin database for sync");
        return;
    }
    
    // Get the newly created user from PowerDNS Admin database
    $user_query = "SELECT id, username, firstname, lastname, email, role_id FROM user WHERE username = ?";
    $stmt = $pdns_admin_conn->prepare($user_query);
    $stmt->execute([$data->username]);
    $pdns_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pdns_user) {
        sendError(500, "User created in PowerDNS Admin but not found in database sync");
        return;
    }
    
    // Create in local database with additional fields
    $account->username = $pdns_user['username'];
    $account->password = null; // Don't store password locally
    $account->firstname = $pdns_user['firstname'];
    $account->lastname = $pdns_user['lastname'];
    $account->email = $pdns_user['email'];
    $account->role_id = $pdns_user['role_id'];
    $account->ip_addresses = json_encode($validated_ips);
    $account->customer_id = $customer_id;
    $account->pdns_account_id = $pdns_user['id'];
    
    if($account->create()) {
        sendResponse(201, [
            'id' => $db->lastInsertId(),
            'username' => $account->username,
            'firstname' => $account->firstname,
            'lastname' => $account->lastname,
            'email' => $account->email,
            'role_id' => $account->role_id,
            'pdns_account_id' => $account->pdns_account_id,
            'ip_address' => $account->ip_address,
            'customer_id' => $account->customer_id
        ], "Account created successfully in PowerDNS Admin and synced to local database");
    } else {
        sendError(503, "User created in PowerDNS Admin but failed to sync to local database");
    }
}

function updateAccount($account, $account_id) {
    global $pdns_config;
    
    $data = json_decode(file_get_contents("php://input"));
    
    $account->id = $account_id;
    
    if($account->readOne()) {
        // Validate IP addresses if provided
        $validated_ips = $account->ip_addresses ? json_decode($account->ip_addresses, true) : []; // Keep existing if not provided
        if (isset($data->ip_addresses)) {
            if (!is_array($data->ip_addresses)) {
                sendError(400, "ip_addresses must be an array");
                return;
            }
            $validated_ips = [];
            foreach ($data->ip_addresses as $ip) {
                if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                    sendError(400, "Invalid IP address format: " . $ip);
                    return;
                }
                $validated_ips[] = $ip;
            }
        }
        
        // Validate customer_id if provided
        $customer_id = $account->customer_id; // Keep existing if not provided
        if (isset($data->customer_id)) {
            if ($data->customer_id !== null && (!is_numeric($data->customer_id) || $data->customer_id <= 0)) {
                sendError(400, "customer_id must be a positive integer or null");
                return;
            }
            $customer_id = $data->customer_id;
        }
        
        // Update in PowerDNS Admin via API if we have a PowerDNS Admin ID and relevant fields are being updated
        if ($account->pdns_account_id && (isset($data->firstname) || isset($data->lastname) || isset($data->email))) {
            $client = new PDNSAdminClient($pdns_config);
            
            $pdns_data = [];
            if (isset($data->firstname)) $pdns_data['firstname'] = $data->firstname;
            if (isset($data->lastname)) $pdns_data['lastname'] = $data->lastname;
            if (isset($data->email)) $pdns_data['email'] = $data->email;
            
            if (!empty($pdns_data)) {
                $api_response = $client->makeRequest('/users/' . $account->pdns_account_id, 'PUT', $pdns_data);
                
                if (!$api_response || !isset($api_response['success']) || !$api_response['success']) {
                    error_log("Failed to update account in PowerDNS Admin: " . ($api_response['msg'] ?? 'Unknown error'));
                    // Continue with local update even if PowerDNS Admin update fails
                }
            }
        }
        
        // Update in local database
        $account->firstname = $data->firstname ?? $account->firstname;
        $account->lastname = $data->lastname ?? $account->lastname;
        $account->email = $data->email ?? $account->email;
        $account->role_id = $data->role_id ?? $account->role_id;
        $account->ip_addresses = json_encode($validated_ips);
        $account->customer_id = $customer_id;
        
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
