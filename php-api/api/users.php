<?php
/**
 * Users API Endpoint - Alias for Accounts
 * This endpoint provides user-centric access to the accounts system
 */

// Determine the correct base path
$base_path = realpath(__DIR__ . '/..');

require_once $base_path . '/config/config.php';
require_once $base_path . '/config/database.php';
require_once $base_path . '/includes/database-compat.php';
require_once $base_path . '/models/Account.php';
require_once $base_path . '/classes/PDNSAdminClient.php';

// API key is already validated in index.php, log the request
logApiRequest('users', $_SERVER['REQUEST_METHOD'], 200);

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

// Initialize account object (users are stored as accounts)
$account = new Account($db);

// Get the HTTP method
$request_method = $_SERVER["REQUEST_METHOD"];

// Parse URL path to support RESTful endpoints like /users/123
$request_uri = $_SERVER['REQUEST_URI'];
$path_parts = explode('/', trim(parse_url($request_uri, PHP_URL_PATH), '/'));

// Get parameters from URL (query parameters)
$user_id = isset($_GET['id']) ? $_GET['id'] : null;
$user_username = isset($_GET['username']) ? $_GET['username'] : null;
$sync = isset($_GET['sync']) ? $_GET['sync'] : null;

// Check for RESTful path parameter (e.g., /users/123)
$users_index = array_search('users', $path_parts);
if ($users_index !== false && isset($path_parts[$users_index + 1])) {
    $path_id = $path_parts[$users_index + 1];
    if (is_numeric($path_id)) {
        $user_id = $path_id; // RESTful path parameter takes precedence
    } elseif (!empty($path_id) && !is_numeric($path_id)) {
        // If it's not numeric, treat it as username
        $user_username = $path_id;
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
            syncUsersFromPDNSAdminDB($account, $pdns_admin_conn, false); // Explicit sync should be verbose
        } elseif ($user_id) {
            getUser($account, $user_id);
        } elseif ($user_username) {
            getUserByUsername($account, $user_username);
        } elseif ($json_data && isset($json_data['id'])) {
            getUser($account, $json_data['id']);
        } elseif ($json_data && isset($json_data['username'])) {
            getUserByUsername($account, $json_data['username']);
        } else {
            getAllUsers($account);
        }
        break;
        
    case 'POST':
        createUser($account);
        break;
        
    case 'PUT':
        if ($json_data && isset($json_data['id'])) {
            updateUser($account, $json_data['id']);
        } elseif ($json_data && isset($json_data['username'])) {
            updateUser($account, $json_data['username']);
        } elseif ($user_id) {
            updateUser($account, $user_id);
        } elseif ($user_username) {
            updateUser($account, $user_username);
        } else {
            sendError(400, "User ID or username required for update (via JSON, path, or query parameter)");
        }
        break;
        
    case 'DELETE':
        if ($json_data && isset($json_data['id'])) {
            deleteUser($account, $json_data['id']);
        } elseif ($json_data && isset($json_data['username'])) {
            deleteUser($account, $json_data['username']);
        } elseif ($user_id) {
            deleteUser($account, $user_id);
        } elseif ($user_username) {
            deleteUser($account, $user_username);
        } else {
            sendError(400, "User ID or username required for deletion (via JSON, path, or query parameter)");
        }
        break;
        
    default:
        sendError(405, "Method not allowed");
        break;
}

function syncUsersFromPDNSAdminDB($account, $pdns_admin_conn, $silent = true) {
    // This is the same as syncAccountsFromPDNSAdminDB in accounts.php
    // Users are just accounts with a different API presentation
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
            // Check if user exists
            $check_query = "SELECT id, ip_addresses, customer_id FROM accounts WHERE username = ? FOR UPDATE";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$pdns_user['username']]);
            $existing_user = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_user) {
                // Update existing user (preserve ip_addresses and customer_id)
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
                // Create new user
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
        
        // Remove users that exist locally but not in PowerDNS Admin
        $placeholders = str_repeat('?,', count($pdns_usernames) - 1) . '?';
        $delete_query = "DELETE FROM accounts WHERE username NOT IN ($placeholders)";
        $delete_stmt = $db->prepare($delete_query);
        if ($delete_stmt->execute($pdns_usernames)) {
            $deleted_count = $delete_stmt->rowCount();
        }
        
        // Commit the transaction
        $db->commit();
        
        $message = "User sync completed: {$synced_count} users added, {$updated_count} users updated, {$deleted_count} users removed from local database";
        
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
        error_log("User sync from database failed: " . $e->getMessage());
        
        if ($silent) {
            return ['error' => 'User sync from database failed: ' . $e->getMessage()];
        } else {
            sendError(500, "User sync from database failed: " . $e->getMessage());
        }
    }
}

function getAllUsers($account) {
    $stmt = $account->read();
    $num = $stmt->rowCount();
    
    if($num > 0) {
        $users_arr = array();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            extract($row);
            
            // Map account fields to user-centric field names
            $user_item = array(
                "id" => $id,
                "username" => $username,
                "name" => $username, // Alias for compatibility
                "firstname" => $firstname,
                "lastname" => $lastname,
                "fullname" => trim(($firstname ?? '') . ' ' . ($lastname ?? '')),
                "email" => $email,
                "mail" => $email, // Alias for compatibility
                "role_id" => $role_id,
                "ip_addresses" => $ip_addresses ? json_decode($ip_addresses, true) : [],
                "customer_id" => $customer_id,
                "pdns_user_id" => $pdns_account_id, // Mapped for user context
                "created_at" => $created_at,
                "updated_at" => $updated_at
            );
            
            array_push($users_arr, $user_item);
        }
        
        sendResponse(200, $users_arr, "Users retrieved successfully");
    } else {
        sendResponse(200, array(), "No users found");
    }
}

function getUser($account, $user_id) {
    $account->id = $user_id;
    
    if($account->readOne()) {
        $user_arr = array(
            "id" => $account->id,
            "username" => $account->username,
            "name" => $account->username, // Alias for compatibility
            "firstname" => $account->firstname,
            "lastname" => $account->lastname,
            "fullname" => trim(($account->firstname ?? '') . ' ' . ($account->lastname ?? '')),
            "email" => $account->email,
            "mail" => $account->email, // Alias for compatibility
            "role_id" => $account->role_id,
            "ip_addresses" => $account->ip_addresses ? json_decode($account->ip_addresses, true) : [],
            "customer_id" => $account->customer_id,
            "pdns_user_id" => $account->pdns_account_id, // Mapped for user context
            "created_at" => $account->created_at,
            "updated_at" => $account->updated_at
        );
        
        sendResponse(200, $user_arr, "User retrieved successfully");
    } else {
        sendError(404, "User not found");
    }
}

function getUserByUsername($account, $username) {
    if($account->readByName($username)) {
        $user_arr = array(
            "id" => $account->id,
            "username" => $account->username,
            "name" => $account->username, // Alias for compatibility
            "firstname" => $account->firstname,
            "lastname" => $account->lastname,
            "fullname" => trim(($account->firstname ?? '') . ' ' . ($account->lastname ?? '')),
            "email" => $account->email,
            "mail" => $account->email, // Alias for compatibility
            "role_id" => $account->role_id,
            "ip_addresses" => $account->ip_addresses ? json_decode($account->ip_addresses, true) : [],
            "customer_id" => $account->customer_id,
            "pdns_user_id" => $account->pdns_account_id, // Mapped for user context
            "created_at" => $account->created_at,
            "updated_at" => $account->updated_at
        );
        
        sendResponse(200, $user_arr, "User retrieved successfully");
    } else {
        sendError(404, "User not found");
    }
}

function createUser($account) {
    global $pdns_config, $db, $pdns_admin_conn;
    
    $data = json_decode(file_get_contents("php://input"));
    
    // Validate required PowerDNS Admin fields (accept both user and account field names)
    $username = $data->username ?? $data->name ?? null;
    $email = $data->email ?? $data->mail ?? null;
    $firstname = $data->firstname ?? null;
    $lastname = $data->lastname ?? null;
    $password = $data->plain_text_password ?? $data->password ?? null;
    
    if (empty($username) || empty($password) || empty($firstname) || empty($email)) {
        sendError(400, "Username, password, firstname, and email are required");
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
        'username' => $username,
        'plain_text_password' => $password,
        'firstname' => $firstname,
        'lastname' => $lastname ?? '',
        'email' => $email,
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
    $stmt->execute([$username]);
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
        // Auto-sync after user creation (silent mode is default)
        syncUsersFromPDNSAdminDB($account, $pdns_admin_conn);
        
        sendResponse(201, [
            'id' => $account->id,
            'username' => $account->username,
            'name' => $account->username,
            'firstname' => $account->firstname,
            'lastname' => $account->lastname,
            'fullname' => trim(($account->firstname ?? '') . ' ' . ($account->lastname ?? '')),
            'email' => $account->email,
            'mail' => $account->email,
            'role_id' => $account->role_id,
            'pdns_user_id' => $account->pdns_account_id,
            'ip_addresses' => $account->ip_addresses ? json_decode($account->ip_addresses, true) : [],
            'customer_id' => $account->customer_id
        ], "User created successfully in PowerDNS Admin and synced to local database");
    } else {
        sendError(503, "User created in PowerDNS Admin but failed to sync to local database");
    }
}

function updateUser($account, $user_identifier) {
    global $pdns_config;
    
    $data = json_decode(file_get_contents("php://input"));
    
    // Determine if the identifier is numeric (ID) or string (username)
    if (is_numeric($user_identifier)) {
        // It's an ID
        $account->id = $user_identifier;
        if (!$account->readOne()) {
            sendError(404, "User not found");
            return;
        }
    } else {
        // It's a username
        if (!$account->readByName($user_identifier)) {
            sendError(404, "User not found");
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
    if ($account->pdns_account_id && (isset($data->firstname) || isset($data->lastname) || isset($data->email) || isset($data->mail))) {
        $client = new PDNSAdminClient($pdns_config);
        
        $pdns_data = [];
        if (isset($data->firstname)) $pdns_data['firstname'] = $data->firstname;
        if (isset($data->lastname)) $pdns_data['lastname'] = $data->lastname;
        if (isset($data->email)) $pdns_data['email'] = $data->email;
        if (isset($data->mail)) $pdns_data['email'] = $data->mail; // Accept both field names
        
        if (!empty($pdns_data)) {
            $api_response = $client->makeRequest('/users/' . $account->pdns_account_id, 'PUT', $pdns_data);
            
            if (!$api_response || !isset($api_response['success']) || !$api_response['success']) {
                error_log("Failed to update user in PowerDNS Admin: " . ($api_response['msg'] ?? 'Unknown error'));
                // Continue with local update even if PowerDNS Admin update fails
            }
        }
    }
    
    // Update in local database
    $account->firstname = $data->firstname ?? $account->firstname;
    $account->lastname = $data->lastname ?? $account->lastname;
    $account->email = $data->email ?? $data->mail ?? $account->email; // Accept both field names
    $account->role_id = $data->role_id ?? $account->role_id;
    $account->ip_addresses = json_encode($validated_ips);
    $account->customer_id = $customer_id;
    
    if($account->update()) {
        // Auto-sync after user update (silent mode is default)
        global $pdns_admin_conn;
        syncUsersFromPDNSAdminDB($account, $pdns_admin_conn);
        
        sendResponse(200, null, "User updated successfully");
    } else {
        sendError(503, "Unable to update user");
    }
}

function deleteUser($account, $user_identifier) {
    global $pdns_config;
    
    // Determine if the identifier is numeric (ID) or string (username)
    if (is_numeric($user_identifier)) {
        // It's an ID, so we need to look up the user first
        $account->id = $user_identifier;
        if (!$account->readOne()) {
            sendError(404, "User not found");
            return;
        }
        $username_for_pdns = $account->username;
    } else {
        // It's a username, so we can use it directly for PowerDNS Admin API
        $username_for_pdns = $user_identifier;
        // Also load the user data for the response
        if (!$account->readByName($user_identifier)) {
            sendError(404, "User not found");
            return;
        }
    }
    
    // Check if this is a protected user (admin users cannot be deleted)
    $protected_users = ['admin', 'administrator', 'apiadmin'];
    if (in_array(strtolower($username_for_pdns), $protected_users)) {
        sendError(403, "Cannot delete protected administrator user: {$username_for_pdns}");
        return;
    }
    
    // Check if we have PowerDNS Admin user ID
    if (!$account->pdns_account_id) {
        sendError(400, "User does not have a PowerDNS Admin ID - cannot delete from PowerDNS Admin");
        return;
    }
    
    // Delete from PowerDNS Admin API using PowerDNS Admin user ID
    $client = new PDNSAdminClient($pdns_config);
    $response = $client->deleteUser($account->pdns_account_id);
    
    if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
        // Auto-sync after user deletion (silent mode is default)
        global $pdns_admin_conn;
        syncUsersFromPDNSAdminDB($account, $pdns_admin_conn);
        
        sendResponse(200, null, "User deleted from PowerDNS Admin and local database synced automatically");
    } elseif ($response['status_code'] == 405) {
        // Handle Method Not Allowed - likely trying to delete a protected user
        sendError(403, "Cannot delete user '{$username_for_pdns}' - this user is protected by PowerDNS Admin");
    } else {
        $error_msg = "Unable to delete user from PowerDNS Admin. HTTP Code: " . $response['status_code'];
        if (isset($response['raw_response']) && !empty($response['raw_response'])) {
            // Extract meaningful error from HTML response if possible
            if (strpos($response['raw_response'], '405 Method Not Allowed') !== false) {
                $error_msg = "User deletion not allowed - this may be a protected administrator user";
            } else {
                $error_msg .= ". Response: " . substr(strip_tags($response['raw_response']), 0, 200);
            }
        }
        sendError(503, $error_msg);
    }
}
?>
?>
