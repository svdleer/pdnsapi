<?php
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
    echo json_encode([
        'error' => 'Database compatibility layer failed',
        'debug' => [
            'base_path' => $base_path,
            'working_dir' => getcwd(),
            'included_files' => get_included_files(),
            'declared_classes' => array_filter(get_declared_classes(), function($class) {
                return stripos($class, 'database') !== false;
            })
        ]
    ], JSON_PRETTY_PRINT);
    exit;
}

// Verify PDNSAdmin config
if (!isset($pdns_config) || !is_array($pdns_config) || !isset($pdns_config['base_url'])) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'PDNSAdmin configuration missing or invalid',
        'debug' => [
            'pdns_config_isset' => isset($pdns_config),
            'pdns_config_is_array' => isset($pdns_config) ? is_array($pdns_config) : false,
            'pdns_config_keys' => isset($pdns_config) && is_array($pdns_config) ? array_keys($pdns_config) : [],
            'pdns_config_preview' => isset($pdns_config) ? (is_array($pdns_config) ? array_slice($pdns_config, 0, 3, true) : gettype($pdns_config)) : 'NOT_SET'
        ]
    ], JSON_PRETTY_PRINT);
    exit;
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize PDNSAdmin client
$pdns_client = new PDNSAdminClient($pdns_config);

// Initialize account object
$account = new Account($db);

// Get the HTTP method
$request_method = $_SERVER["REQUEST_METHOD"];

// Get the account ID from URL if present
$account_id = isset($_GET['id']) ? $_GET['id'] : null;
$account_name = isset($_GET['name']) ? $_GET['name'] : null;

switch($request_method) {
    case 'GET':
        if ($account_id) {
            getAccount($account, $account_id);
        } elseif ($account_name) {
            getAccountByName($account, $account_name);
        } else {
            getAllUsers($account);
        }
        break;
        
    case 'POST':
        createAccount($account, $pdns_client);
        break;
        
    case 'PUT':
        if ($account_id) {
            updateAccount($account, $pdns_client, $account_id);
        } else {
            sendError(400, "Account ID required for update");
        }
        break;
        
    case 'DELETE':
        if ($account_id) {
            deleteAccount($account, $pdns_client, $account_id);
        } else {
            sendError(400, "Account ID required for deletion");
        }
        break;
        
    default:
        sendError(405, "Method not allowed");
        break;
}

function getAllUsers($account) {
    $stmt = $account->read();
    $num = $stmt->rowCount();
    
    if($num > 0) {
        $users_arr = array();
        
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
            
            array_push($users_arr, $account_item);
        }
        
        sendResponse(200, $users_arr);
    } else {
        sendResponse(200, array(), "No users found");
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
    $account->name = $account_name;
    
    if($account->readByName()) {
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

function createAccount($account, $pdns_client) {
    $data = json_decode(file_get_contents("php://input"));
    
    if(!empty($data->name) && !empty($data->mail)) {
        $account->name = $data->name;
        $account->description = $data->description ?? '';
        $account->contact = $data->contact ?? '';
        $account->mail = $data->mail;
        
        // Handle IP addresses (don't send to PDNSAdmin)
        $ip_addresses = [];
        if (isset($data->ip_addresses) && is_array($data->ip_addresses)) {
            // Validate IP addresses
            foreach ($data->ip_addresses as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    $ip_addresses[] = $ip;
                }
            }
        }
        $account->ip_addresses = json_encode($ip_addresses);
        
        // Create account in PDNSAdmin first (without IP addresses)
        $pdns_data = [
            'name' => $account->name,
            'description' => $account->description,
            'contact' => $account->contact,
            'mail' => $account->mail
        ];
        
        $pdns_response = $pdns_client->createAccount($pdns_data);
        
        if($pdns_response['status_code'] == 201) {
            // If successful in PDNSAdmin, save to local database
            $account->pdns_account_id = $pdns_response['data']['id'] ?? null;
            
            if($account->create()) {
                sendResponse(201, null, "Account created successfully");
            } else {
                sendError(503, "Unable to create account in local database");
            }
        } else {
            $error_msg = $pdns_response['data']['error'] ?? 'Failed to create account in PDNSAdmin';
            sendError($pdns_response['status_code'], $error_msg);
        }
    } else {
        sendError(400, "Unable to create account. Data is incomplete (name and mail required)");
    }
}

function updateAccount($account, $pdns_client, $account_id) {
    $data = json_decode(file_get_contents("php://input"));
    
    $account->id = $account_id;
    
    if($account->readOne()) {
        $account->description = $data->description ?? $account->description;
        $account->contact = $data->contact ?? $account->contact;
        $account->mail = $data->mail ?? $account->mail;
        
        // Handle IP addresses (don't send to PDNSAdmin)
        if (isset($data->ip_addresses)) {
            $ip_addresses = [];
            if (is_array($data->ip_addresses)) {
                // Validate IP addresses
                foreach ($data->ip_addresses as $ip) {
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        $ip_addresses[] = $ip;
                    }
                }
            }
            $account->ip_addresses = json_encode($ip_addresses);
        }
        
        // Update in PDNSAdmin first (without IP addresses)
        $pdns_data = [
            'name' => $account->name, // Name is immutable
            'description' => $account->description,
            'contact' => $account->contact,
            'mail' => $account->mail
        ];
        
        $pdns_response = $pdns_client->updateAccount($account->name, $pdns_data);
        
        if($pdns_response['status_code'] == 204) {
            // If successful in PDNSAdmin, update local database
            if($account->update()) {
                sendResponse(200, null, "Account updated successfully");
            } else {
                sendError(503, "Unable to update account in local database");
            }
        } else {
            $error_msg = $pdns_response['data']['error'] ?? 'Failed to update account in PDNSAdmin';
            sendError($pdns_response['status_code'], $error_msg);
        }
    } else {
        sendError(404, "Account not found");
    }
}

function deleteAccount($account, $pdns_client, $account_id) {
    $account->id = $account_id;
    
    if($account->readOne()) {
        // Delete from PDNSAdmin first
        $pdns_response = $pdns_client->deleteAccount($account->name);
        
        if($pdns_response['status_code'] == 204) {
            // If successful in PDNSAdmin, delete from local database
            if($account->delete()) {
                sendResponse(200, null, "Account deleted successfully");
            } else {
                sendError(503, "Unable to delete account from local database");
            }
        } else {
            $error_msg = $pdns_response['data']['error'] ?? 'Failed to delete account from PDNSAdmin';
            sendError($pdns_response['status_code'], $error_msg);
        }
    } else {
        sendError(404, "Account not found");
    }
}
?>
