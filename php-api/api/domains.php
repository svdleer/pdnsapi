<?php
// Determine the correct base path
$base_path = realpath(__DIR__ . '/..');

require_once $base_path . '/config/config.php';
require_once $base_path . '/config/database.php';
require_once $base_path . '/includes/database-compat.php';
require_once $base_path . '/models/Domain.php';
require_once $base_path . '/models/Account.php';

// API key is already validated in index.php, log the request
logApiRequest('domains', $_SERVER['REQUEST_METHOD'], 200);

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

// Get PowerDNS Admin database connection
$pdns_admin_conn = null;
if (class_exists('PDNSAdminDatabase')) {
    $pdns_admin_db = new PDNSAdminDatabase();
    $pdns_admin_conn = $pdns_admin_db->getConnection();
}

// Initialize domain object
$domain = new Domain($db);
$account = new Account($db);

// Get the HTTP method
$request_method = $_SERVER["REQUEST_METHOD"];

// Get parameters from URL
$domain_id = isset($_GET['id']) ? $_GET['id'] : null;
$account_id = isset($_GET['account_id']) ? $_GET['account_id'] : null;
$sync = isset($_GET['sync']) ? $_GET['sync'] : null;

switch($request_method) {
    case 'GET':
        if ($sync === 'true') {
            // Always use database sync for better performance and complete data
            syncDomainsFromPDNSAdminDB($domain, $pdns_admin_conn);
        } elseif ($domain_id) {
            getDomain($domain, $domain_id);
        } elseif ($account_id) {
            getDomainsByAccount($domain, $account_id);
        } else {
            getAllDomains($domain);
        }
        break;
        
    case 'POST':
        if (isset($_GET['action']) && $_GET['action'] === 'add_to_account') {
            addDomainToAccount($domain, $_POST);
        } else {
            sendError(405, "Domain creation is not supported. Use sync action to import domains from PowerDNS Admin.");
        }
        break;
        
    case 'PUT':
        if ($domain_id) {
            updateDomain($domain, $domain_id);
        } else {
            sendError(400, "Domain ID required for update");
        }
        break;
        
    default:
        sendError(405, "Method not allowed");
        break;
}

function getAllDomains($domain) {
    $stmt = $domain->read();
    $num = $stmt->rowCount();
    
    if($num > 0) {
        $domains_arr = array();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            extract($row);
            
            $domain_item = array(
                "id" => (int)$id,
                "name" => rtrim($name, '.'), // Remove trailing dot
                "pdns_zone_id" => (int)$pdns_zone_id, // Ensure it's an integer
                "account_id" => $account_id ? (int)$account_id : null
            );
            
            array_push($domains_arr, $domain_item);
        }
        
        sendResponse(200, $domains_arr);
    } else {
        sendResponse(200, array(), "No domains found");
    }
}

function getDomain($domain, $domain_id) {
    $domain->id = $domain_id;
    
    if($domain->readOne()) {
        $domain_arr = array(
            "id" => (int)$domain->id,
            "name" => rtrim($domain->name, '.'), // Remove trailing dot
            "pdns_zone_id" => (int)$domain->pdns_zone_id, // Ensure it's an integer
            "account_id" => $domain->account_id ? (int)$domain->account_id : null
        );
        
        sendResponse(200, $domain_arr);
    } else {
        sendError(404, "Domain not found");
    }
}

function getDomainsByAccount($domain, $account_id) {
    $stmt = $domain->readByAccountId($account_id);
    $num = $stmt->rowCount();
    
    if($num > 0) {
        $domains_arr = array();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            extract($row);
            
            $domain_item = array(
                "id" => (int)$id,
                "name" => rtrim($name, '.'), // Remove trailing dot
                "pdns_zone_id" => (int)$pdns_zone_id, // Ensure it's an integer
                "account_id" => $account_id ? (int)$account_id : null
            );
            
            array_push($domains_arr, $domain_item);
        }
        
        sendResponse(200, $domains_arr);
    } else {
        sendResponse(200, array(), "No domains found for this account");
    }
}

function syncDomainsFromPDNSAdminDB($domain, $pdns_admin_conn) {
    global $db;
    
    if (!$pdns_admin_conn) {
        sendError(500, "Failed to connect to PowerDNS Admin database");
        return;
    }
    
    // Get all domains with their user assignments from PowerDNS Admin database
    $pdns_query = "
        SELECT 
            d.id as pdns_domain_id,
            d.name as domain_name,
            d.type as domain_type,
            d.dnssec,
            u.id as user_id,
            u.username,
            u.firstname,
            u.lastname,
            u.email
        FROM domain d
        LEFT JOIN domain_user du ON d.id = du.domain_id
        LEFT JOIN user u ON du.user_id = u.id
        ORDER BY d.name
    ";
    
    $stmt = $pdns_admin_conn->prepare($pdns_query);
    $stmt->execute();
    $pdns_domains = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($pdns_domains)) {
        sendError(500, "No domains found in PowerDNS Admin database");
        return;
    }
    
    $synced_count = 0;
    $updated_count = 0;
    
    // Start a transaction to prevent race conditions
    $db->beginTransaction();
    
    try {
        // Group domains by name to handle multiple user assignments
        $domains_grouped = [];
        foreach ($pdns_domains as $pdns_domain) {
            $domain_name = $pdns_domain['domain_name'];
            if (!isset($domains_grouped[$domain_name])) {
                $domains_grouped[$domain_name] = [
                    'pdns_domain_id' => $pdns_domain['pdns_domain_id'],
                    'domain_name' => $domain_name,
                    'domain_type' => $pdns_domain['domain_type'],
                    'dnssec' => $pdns_domain['dnssec'],
                    'users' => []
                ];
            }
            
            // Add user if exists
            if ($pdns_domain['user_id']) {
                $domains_grouped[$domain_name]['users'][] = [
                    'user_id' => $pdns_domain['user_id'],
                    'username' => $pdns_domain['username'],
                    'firstname' => $pdns_domain['firstname'],
                    'lastname' => $pdns_domain['lastname'],
                    'email' => $pdns_domain['email']
                ];
            }
        }
        
        foreach ($domains_grouped as $domain_data) {
            $domain_name = $domain_data['domain_name'];
            $pdns_zone_id = (int)$domain_data['pdns_domain_id']; // Use the numeric ID from PowerDNS Admin DB
            
            if (empty($domain_name)) {
                continue; // Skip domains without a name
            }
            
            // Check if domain exists in our database
            $domain_name_no_dot = rtrim($domain_name, '.');
            $check_query = "SELECT id, name FROM domains WHERE name = ? FOR UPDATE";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$domain_name_no_dot]);
            $existing_domain = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Determine account info from users
            $account_name = '';
            $account_id = null;
            
            if (!empty($domain_data['users'])) {
                // Use the first user as the primary account
                $primary_user = $domain_data['users'][0];
                $account_name = $primary_user['username'] . ' (' . $primary_user['firstname'] . ' ' . $primary_user['lastname'] . ')';
                
                // Try to find or create a matching account
                $account_query = "SELECT id FROM accounts WHERE name = ?";
                $account_stmt = $db->prepare($account_query);
                $account_stmt->bindParam(1, $primary_user['username']);
                $account_stmt->execute();
                $account_result = $account_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($account_result) {
                    $account_id = $account_result['id'];
                } else {
                    // Create account for this user
                    $create_account_query = "INSERT INTO accounts (name, description, contact, mail, created_at) VALUES (?, ?, ?, ?, NOW())";
                    $create_account_stmt = $db->prepare($create_account_query);
                    $description = $primary_user['firstname'] . ' ' . $primary_user['lastname'];
                    $create_account_stmt->execute([
                        $primary_user['username'],
                        $description,
                        $description,
                        $primary_user['email']
                    ]);
                    $account_id = $db->lastInsertId();
                }
            }
            
            // Create a new domain object for each domain to avoid conflicts
            $domain_obj = new Domain($db);
            
            if ($existing_domain) {
                // Domain exists, update it
                $domain_obj->id = $existing_domain['id'];
                if ($domain_obj->readOne()) {
                    $domain_obj->pdns_zone_id = $pdns_zone_id;
                    $domain_obj->name = $domain_name_no_dot; // Always store without trailing dot
                    $domain_obj->type = $domain_data['domain_type'] ?: 'Zone';
                    $domain_obj->dnssec = (bool)$domain_data['dnssec'];
                    $domain_obj->account = $account_name;
                    $domain_obj->account_id = $account_id;
                    
                    try {
                        if ($domain_obj->update()) {
                            $updated_count++;
                            error_log("Updated existing domain: {$domain_name} (zone_id: {$pdns_zone_id}, account: {$account_name})");
                        }
                    } catch (Exception $e) {
                        error_log("Failed to update domain {$domain_name}: " . $e->getMessage());
                    }
                }
            } else {
                // Domain doesn't exist, create it
                $domain_obj->name = $domain_name_no_dot; // Always store without trailing dot
                $domain_obj->type = $domain_data['domain_type'] ?: 'Zone';
                $domain_obj->pdns_zone_id = $pdns_zone_id;
                $domain_obj->kind = 'Master'; // Default kind
                $domain_obj->masters = '';
                $domain_obj->dnssec = (bool)$domain_data['dnssec'];
                $domain_obj->account = $account_name;
                $domain_obj->account_id = $account_id;
                
                try {
                    if ($domain_obj->create()) {
                        $synced_count++;
                        error_log("Created new domain: {$domain_name} (zone_id: {$pdns_zone_id}, account: {$account_name})");
                    }
                } catch (Exception $e) {
                    error_log("Failed to create domain {$domain_name}: " . $e->getMessage());
                }
            }
        }
        
        // Commit the transaction
        $db->commit();
        
        $message = "Database sync completed: {$synced_count} domains added, {$updated_count} domains updated from PowerDNS Admin database";
        sendResponse(200, array(
            'synced' => $synced_count,
            'updated' => $updated_count,
            'total_processed' => count($domains_grouped)
        ), $message);
        
    } catch (Exception $e) {
        // Rollback the transaction in case of error
        $db->rollback();
        error_log("Domain sync from database failed: " . $e->getMessage());
        sendError(500, "Domain sync from database failed: " . $e->getMessage());
    }
}
                
function updateDomain($domain, $domain_id) {
    global $db;
    $data = json_decode(file_get_contents("php://input"));
    
    $domain->id = $domain_id;
    
    if($domain->readOne()) {
        $old_account_id = $domain->account_id;
        
        $domain->account_id = $data->account_id ?? $domain->account_id;
        $domain->kind = $data->kind ?? $domain->kind;
        $domain->masters = isset($data->masters) ? (is_array($data->masters) ? implode(',', $data->masters) : $data->masters) : $domain->masters;
        $domain->dnssec = $data->dnssec ?? $domain->dnssec;
        $domain->account = $data->account ?? $domain->account;
        
        // Update account name if account_id changed
        if ($old_account_id != $domain->account_id && !empty($domain->account_id)) {
            $account = new Account($db);
            $account->id = $domain->account_id;
            if ($account->readOne()) {
                $domain->account = $account->name;
            }
        } elseif ($old_account_id != $domain->account_id && empty($domain->account_id)) {
            $domain->account = '';
        }
        
        if($domain->update()) {
            sendResponse(200, null, "Domain updated successfully");
        } else {
            sendError(503, "Unable to update domain");
        }
    } else {
        sendError(404, "Domain not found");
    }
}

function addDomainToAccount($domain, $post_data) {
    if(!empty($post_data['domain_name']) && !empty($post_data['account_id'])) {
        if($domain->addToAccount($post_data['domain_name'], $post_data['account_id'])) {
            sendResponse(200, null, "Domain added to account successfully");
        } else {
            sendError(503, "Unable to add domain to account");
        }
    } else {
        sendError(400, "Domain name and account ID are required");
    }
}
?>
