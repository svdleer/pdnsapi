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

// Parse URL path to support RESTful endpoints like /accounts/123
$request_uri = $_SERVER['REQUEST_URI'];
$path_parts = explode('/', trim(parse_url($request_uri, PHP_URL_PATH), '/'));

// Get parameters from URL (query parameters)
$account_id = isset($_GET['id']) ? $_GET['id'] : null;
$account_username = isset($_GET['username']) ? $_GET['username'] : null;
$sync = isset($_GET['sync']) ? $_GET['sync'] : null;

// Check for RESTful path parameter (e.g., /accounts/123)
// Find 'accounts' in the path and get the next segment as ID if it's numeric
$accounts_index = array_search('accounts', $path_parts);
if ($accounts_index !== false && isset($path_parts[$accounts_index + 1])) {
    $path_id = $path_parts[$accounts_index + 1];
    if (is_numeric($path_id)) {
        $account_id = $path_id; // RESTful path parameter takes precedence
    } elseif (!empty($path_id) && !is_numeric($path_id)) {
        // If it's not numeric, treat it as username
        $account_username = $path_id;
    }
}

// For GET, POST, PUT, DELETE - check for JSON payload
$json_data = null;
$input = file_get_contents("php://input");
if (!empty($input)) {
    $json_data = json_decode($input, true);
}

switch($request_method) {
    case 'GET':
        if ($sync === 'true') {
            syncAccountsFromPDNSAdminDB($account, $pdns_admin_conn, false); // Explicit sync should be verbose
        } elseif ($account_id) {
            getAccount($account, $account_id);
        } elseif ($account_username) {
            getAccountByName($account, $account_username);
        } elseif ($json_data && isset($json_data['id'])) {
            getAccount($account, $json_data['id']);
        } elseif ($json_data && isset($json_data['username'])) {
            getAccountByName($account, $json_data['username']);
        } else {
            getAllAccounts($account);
        }
        break;
        
    case 'POST':
        createAccount($account);
        break;
        
    case 'PUT':
        if ($json_data && isset($json_data['id'])) {
            updateAccount($account, $json_data['id']);
        } elseif ($json_data && isset($json_data['username'])) {
            updateAccount($account, $json_data['username']);
        } elseif ($account_id) {
            updateAccount($account, $account_id);
        } elseif ($account_username) {
            updateAccount($account, $account_username);
        } else {
            sendError(400, "Account ID or username required for update (via JSON, path, or query parameter)");
        }
        break;
        
    case 'DELETE':
        if ($json_data && isset($json_data['id'])) {
            deleteAccount($account, $json_data['id']);
        } elseif ($json_data && isset($json_data['username'])) {
            deleteAccount($account, $json_data['username']);
        } elseif ($account_id) {
            deleteAccount($account, $account_id);
        } elseif ($account_username) {
            deleteAccount($account, $account_username);
        } else {
            sendError(400, "Account ID or username required for deletion (via JSON, path, or query parameter)");
        }
        break;
        
    default:
        sendError(405, "Method not allowed");
        break;
}

function syncAccountsFromPDNSAdminDB($account, $pdns_admin_conn, $silent = true) {
    global $db;
    
    if (!$pdns_admin_conn) {
        if ($silent) {
            return ['error' => 'Failed to connect to PowerDNS Admin database'];
        } else {
            sendError(500, "Failed to connect to PowerDNS Admin database");
            return;
        }
    }
    
    // Get all users from PowerDNS Admin database
    $users_query = "SELECT id, username, firstname, lastname, email, role_id FROM user";
    $stmt = $pdns_admin_conn->prepare($users_query);
    $stmt->execute();
    $pdns_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($pdns_users)) {
        if ($silent) {
            return ['error' => 'No users found in PowerDNS Admin database'];
        } else {
            sendError(500, "No users found in PowerDNS Admin database");
            return;
        }
    }
    
    $synced_count = 0;
    $updated_count = 0;
    $deleted_count = 0;
    
    // Create array of PowerDNS Admin usernames for quick lookup
    $pdns_usernames = array_column($pdns_users, 'username');
    
    // Start a transaction
    $db->beginTransaction();
    
    try {
        // Process PowerDNS Admin users (create/update)
        foreach ($pdns_users as $pdns_user) {
            // Check if account exists
            $check_query = "SELECT id, ip_addresses, customer_id FROM accounts WHERE username = ? FOR UPDATE";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$pdns_user['username']]);
            $existing_account = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_account) {
                // Update existing account (preserve ip_addresses and customer_id)
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
        
        // Remove accounts that exist locally but not in PowerDNS Admin
        $placeholders = str_repeat('?,', count($pdns_usernames) - 1) . '?';
        $delete_query = "DELETE FROM accounts WHERE username NOT IN ($placeholders)";
        $delete_stmt = $db->prepare($delete_query);
        if ($delete_stmt->execute($pdns_usernames)) {
            $deleted_count = $delete_stmt->rowCount();
        }
        
        // Commit the transaction
        $db->commit();
        
        $message = "Database sync completed: {$synced_count} accounts added, {$updated_count} accounts updated, {$deleted_count} accounts removed from local database";
        
        if ($silent) {
            return [
                'synced' => $synced_count,
                'updated' => $updated_count,
                'deleted' => $deleted_count,
                'total_processed' => count($pdns_users),
                'message' => $message
            ];
        } else {
            sendResponse(200, array(
                'synced' => $synced_count,
                'updated' => $updated_count,
                'deleted' => $deleted_count,
                'total_processed' => count($pdns_users)
            ), $message);
        }
        
    } catch (Exception $e) {
        // Rollback the transaction
        $db->rollback();
        error_log("Account sync from database failed: " . $e->getMessage());
        
        if ($silent) {
            return ['error' => 'Account sync from database failed: ' . $e->getMessage()];
        } else {
            sendError(500, "Account sync from database failed: " . $e->getMessage());
        }
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
        // Auto-sync after account creation (silent mode is default)
        global $pdns_admin_conn;
        syncAccountsFromPDNSAdminDB($account, $pdns_admin_conn);
        
        sendResponse(201, [
            'id' => $account->id,
            'username' => $account->username,
            'firstname' => $account->firstname,
            'lastname' => $account->lastname,
            'email' => $account->email,
            'role_id' => $account->role_id,
            'pdns_account_id' => $account->pdns_account_id,
            'ip_addresses' => $account->ip_addresses ? json_decode($account->ip_addresses, true) : [],
            'customer_id' => $account->customer_id
        ], "Account created successfully in PowerDNS Admin and synced to local database");
    } else {
        sendError(503, "User created in PowerDNS Admin but failed to sync to local database");
    }
}

function updateAccount($account, $account_identifier) {
    global $pdns_config;
    
    $data = json_decode(file_get_contents("php://input"));
    
    // Determine if the identifier is numeric (ID) or string (username)
    if (is_numeric($account_identifier)) {
        // It's an ID
        $account->id = $account_identifier;
        if (!$account->readOne()) {
            sendError(404, "Account not found");
            return;
        }
    } else {
        // It's a username
        if (!$account->readByName($account_identifier)) {
            sendError(404, "Account not found");
            return;
        }
    }
    
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
        // Auto-sync after account update (silent mode is default)
        global $pdns_admin_conn;
        syncAccountsFromPDNSAdminDB($account, $pdns_admin_conn);
        
        sendResponse(200, null, "Account updated successfully");
    } else {
        sendError(503, "Unable to update account");
    }
}

function deleteAccount($account, $account_identifier) {
    global $pdns_config;
    
    // Determine if the identifier is numeric (ID) or string (username)
    if (is_numeric($account_identifier)) {
        // It's an ID, so we need to look up the account first
        $account->id = $account_identifier;
        if (!$account->readOne()) {
            sendError(404, "Account not found");
            return;
        }
        $username_for_pdns = $account->username;
    } else {
        // It's a username, so we can use it directly for PowerDNS Admin API
        $username_for_pdns = $account_identifier;
        // Also load the account data for the response
        if (!$account->readByName($account_identifier)) {
            sendError(404, "Account not found");
            return;
        }
    }
    
    // Delete from PowerDNS Admin API using username
    $client = new PDNSAdminClient($pdns_config);
    $response = $client->deleteUser($username_for_pdns);
    
    if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
        // Auto-sync after account deletion (silent mode is default)
        global $pdns_admin_conn;
        syncAccountsFromPDNSAdminDB($account, $pdns_admin_conn);
        
        sendResponse(200, null, "Account deleted from PowerDNS Admin and local database synced automatically");
    } else {
        sendError(503, "Unable to delete account from PowerDNS Admin. HTTP Code: " . $response['status_code'] . ". Response: " . $response['raw_response']);
    }
}
?>
