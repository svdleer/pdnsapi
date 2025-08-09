<?php
// Determine the correct base path
$base_path = realpath(__DIR__ . '/..');

require_once $base_path . '/config/config.php';
require_once $base_path . '/config/database.php';
require_once $base_path . '/includes/database-compat.php';
require_once $base_path . '/models/Domain.php';
require_once $base_path . '/models/Account.php';
require_once $base_path . '/classes/PDNSAdminClient.php';

// CRITICAL: Enforce authentication for direct API file access
enforceHTTPS();
addSecurityHeaders();
requireApiKey(); // This will exit with 401/403 if auth fails

// Log successful authenticated request
logApiRequest('domains', $_SERVER['REQUEST_METHOD'], 200);

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

// Get PowerDNS Admin database connection for READ operations
$pdns_admin_conn = null;
if (class_exists('PDNSAdminDatabase')) {
    $pdns_admin_db = new PDNSAdminDatabase();
    $pdns_admin_conn = $pdns_admin_db->getConnection();
}

// Initialize PDNSAdmin client
$pdns_client = new PDNSAdminClient($pdns_config);

// Initialize domain object
$domain = new Domain($db);
$account = new Account($db);

// Get the HTTP method
$request_method = $_SERVER["REQUEST_METHOD"];

// Get parameters from URL
$domain_id = isset($_GET['id']) ? $_GET['id'] : null;
$account_id = isset($_GET['account_id']) ? $_GET['account_id'] : null;
$sync = isset($_GET['sync']) ? $_GET['sync'] : null;

// For GET, POST, PUT, DELETE - check for JSON payload
$json_data = null;
$input = file_get_contents("php://input");
if (!empty($input)) {
    $json_data = json_decode($input, true);
}

switch($request_method) {
    case 'GET':
        if ($sync === 'true') {
            syncDomainsFromPDNS($domain, $pdns_client);
        } elseif ($json_data && isset($json_data['id'])) {
            getDomain($domain, $json_data['id']);
        } elseif ($json_data && isset($json_data['account_id'])) {
            getDomainsByAccount($domain, $json_data['account_id']);
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
        if ($json_data && isset($json_data['id'])) {
            updateDomain($domain, $json_data['id']);
        } elseif ($domain_id) {
            updateDomain($domain, $domain_id);
        } else {
            sendError(400, "Domain ID required for update (via JSON or query parameter)");
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
        
        sendResponse(200, $domains_arr);
    } else {
        sendResponse(200, array(), "No domains found");
    }
}

function getDomain($domain, $domain_id) {
    $domain->id = $domain_id;
    
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
        
        sendResponse(200, $domain_arr);
    } else {
        sendError(404, "Domain not found");
    }
}

function getDomainsByAccount($domain, $account_id) {
    // Admin (account_id = 1) can see ALL domains, regardless of ownership
    if ($account_id == 1) {
        error_log("Admin user requested domains - returning ALL domains");
        $stmt = $domain->read(); // Get all domains
    } else {
        $stmt = $domain->readByAccountId($account_id);
    }
    
    $num = $stmt->rowCount();
    
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
        
        sendResponse(200, $domains_arr);
    } else {
        sendResponse(200, array(), "No domains found for this account");
    }
}

function syncDomainsFromPDNS($domain, $pdns_client) {
    global $db;
    
    // Get all domains from PowerDNS Admin with full domain objects including account info
    $pdns_response = $pdns_client->getAllDomainsWithAccounts();
    
    if($pdns_response['status_code'] == 200) {
        $pdns_domains = $pdns_response['data'];
        $synced_count = 0;
        $updated_count = 0;
        $account_synced = 0;
        
        // Start a transaction to prevent race conditions
        $db->beginTransaction();
        
        try {
            foreach($pdns_domains as $pdns_domain) {
                $domain_name = $pdns_domain['name'] ?? '';
                $pdns_zone_id = $pdns_domain['id'] ?? null;
                $account_id = $pdns_domain['account_id'] ?? null;
                $account_name = $pdns_domain['account']['name'] ?? null;
                
                if (empty($domain_name)) {
                    continue; // Skip domains without a name
                }
                
                // Sync account if present and doesn't exist locally
                $local_account_id = null;
                if ($account_id && $account_name) {
                    $local_account_id = syncAccountFromPDNSAdmin($account_id, $account_name, $pdns_domain['account'], $db);
                    if ($local_account_id) {
                        $account_synced++;
                    }
                }
                
                // If no account from PowerDNS Admin, assign to admin account (ID: 1)
                if (!$local_account_id) {
                    $local_account_id = 1; // Default to admin account
                    error_log("Domain {$domain_name}: No PowerDNS Admin account found, assigning to admin (ID: 1)");
                }
                
                // Use a more robust check with SELECT FOR UPDATE to prevent race conditions
                $check_query = "SELECT id FROM domains WHERE name = ? FOR UPDATE";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->bindParam(1, $domain_name);
                $check_stmt->execute();
                $existing_domain = $check_stmt->fetch(PDO::FETCH_ASSOC);
                
                // Create a new domain object for each domain to avoid conflicts
                $domain_obj = new Domain($db);
                
                if ($existing_domain) {
                    // Domain exists, update it with full data from PowerDNS Admin
                    $domain_obj->id = $existing_domain['id'];
                    $domain_obj->readByName(); // Load full data
                    $domain_obj->pdns_zone_id = $pdns_zone_id;
                    $domain_obj->name = $domain_name;
                    $domain_obj->account_id = $local_account_id; // Link to local account
                    
                    // Update additional fields if available from PowerDNS Admin
                    if (isset($pdns_domain['type'])) {
                        $domain_obj->type = $pdns_domain['type'];
                    }
                    if (isset($pdns_domain['kind'])) {
                        $domain_obj->kind = $pdns_domain['kind'];
                    }
                    if (isset($pdns_domain['dnssec'])) {
                        $domain_obj->dnssec = $pdns_domain['dnssec'];
                    }
                    
                    try {
                        if ($domain_obj->updateBasic()) {
                            $updated_count++;
                            $account_info = $account_name ?: ($local_account_id == 1 ? "admin (default)" : "ID:{$local_account_id}");
                            error_log("Updated existing domain: {$domain_name} (zone_id: {$pdns_zone_id}, account: {$account_info})");
                        }
                    } catch (Exception $e) {
                        error_log("Failed to update domain {$domain_name}: " . $e->getMessage());
                    }
                } else {
                    // Domain doesn't exist, create it with full data from PowerDNS Admin
                    $domain_obj->name = $domain_name;
                    $domain_obj->type = $pdns_domain['type'] ?? 'Zone';
                    $domain_obj->pdns_zone_id = $pdns_zone_id;
                    $domain_obj->kind = $pdns_domain['kind'] ?? 'Master';
                    $domain_obj->masters = $pdns_domain['masters'] ?? '';
                    $domain_obj->dnssec = $pdns_domain['dnssec'] ?? false;
                    $domain_obj->account = $account_name ?? '';
                    $domain_obj->account_id = $local_account_id; // Link to local account
                    
                    try {
                        if ($domain_obj->create()) {
                            $synced_count++;
                            $account_info = $account_name ?: ($local_account_id == 1 ? "admin (default)" : "ID:{$local_account_id}");
                            error_log("Created new domain: {$domain_name} (zone_id: {$pdns_zone_id}, account: {$account_info})");
                        }
                    } catch (Exception $e) {
                        error_log("Failed to create domain {$domain_name}: " . $e->getMessage());
                    }
                }
            }
            
            // Commit the transaction
            $db->commit();
            
            $message = "Sync completed: {$synced_count} domains added, {$updated_count} domains updated, {$account_synced} accounts synced. Unassigned domains defaulted to admin account.";
            sendResponse(200, array(
                'synced' => $synced_count,
                'updated' => $updated_count,
                'accounts_synced' => $account_synced,
                'total_processed' => count($pdns_domains)
            ), $message);
            
        } catch (Exception $e) {
            // Rollback the transaction in case of error
            $db->rollback();
            error_log("Domain sync failed: " . $e->getMessage());
            sendError(500, "Domain sync failed: " . $e->getMessage());
        }
    } else {
        $error_msg = isset($pdns_response['data']['message']) ? $pdns_response['data']['message'] : 'Unknown error';
        sendError(500, "Failed to fetch domains from PowerDNS Admin: " . $error_msg);
    }
}

/**
 * Sync account from PowerDNS Admin to local database
 * Returns local account ID if successful, null otherwise
 */
function syncAccountFromPDNSAdmin($pdns_account_id, $account_name, $account_data, $db) {
    // Check if account already exists locally
    $check_query = "SELECT id FROM accounts WHERE pdns_account_id = ? OR username = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(1, $pdns_account_id);
    $check_stmt->bindParam(2, $account_name);
    $check_stmt->execute();
    $existing_account = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_account) {
        // Account exists, update it if needed
        $account = new Account($db);
        $account->id = $existing_account['id'];
        $account->readOne();
        
        // Update fields from PowerDNS Admin if different
        $needs_update = false;
        if ($account->pdns_account_id != $pdns_account_id) {
            $account->pdns_account_id = $pdns_account_id;
            $needs_update = true;
        }
        if ($account->username != $account_name) {
            $account->username = $account_name;
            $needs_update = true;
        }
        
        // Update additional fields if available
        if (isset($account_data['description']) && $account->firstname != $account_data['description']) {
            $account->firstname = $account_data['description'];
            $needs_update = true;
        }
        if (isset($account_data['mail']) && $account->email != $account_data['mail']) {
            $account->email = $account_data['mail'];
            $needs_update = true;
        }
        
        if ($needs_update) {
            try {
                $account->update();
                error_log("Updated existing account: {$account_name} (pdns_id: {$pdns_account_id})");
            } catch (Exception $e) {
                error_log("Failed to update account {$account_name}: " . $e->getMessage());
            }
        }
        
        return $existing_account['id'];
    } else {
        // Account doesn't exist, create it
        $account = new Account($db);
        $account->username = $account_name;
        $account->pdns_account_id = $pdns_account_id;
        $account->firstname = $account_data['description'] ?? '';
        $account->email = $account_data['mail'] ?? '';
        $account->role_id = 3; // Default User role
        
        try {
            if ($account->create()) {
                error_log("Created new account: {$account_name} (pdns_id: {$pdns_account_id})");
                return $account->id;
            }
        } catch (Exception $e) {
            error_log("Failed to create account {$account_name}: " . $e->getMessage());
        }
        
        return null;
    }
}
                
function updateDomain($domain, $domain_id) {
    global $pdns_client, $db;
    $data = json_decode(file_get_contents("php://input"));
    
    $domain->id = $domain_id;
    
    if($domain->readOne()) {
        $old_account_id = $domain->account_id;
        
        $domain->account_id = $data->account_id ?? $domain->account_id;
        $domain->kind = $data->kind ?? $domain->kind;
        $domain->masters = isset($data->masters) ? (is_array($data->masters) ? implode(',', $data->masters) : $data->masters) : $domain->masters;
        $domain->dnssec = $data->dnssec ?? $domain->dnssec;
        $domain->account = $data->account ?? $domain->account;
        
        // If account_id changed, update the account name and sync with PDNSAdmin
        if ($old_account_id != $domain->account_id && !empty($domain->account_id)) {
            $account = new Account($db);
            $account->id = $domain->account_id;
            if ($account->readOne()) {
                $domain->account = $account->name;
                
                // Update domain in PDNSAdmin with new account
                $pdns_data = [
                    'account' => $account->name
                ];
                
                // Use the putZone method to update zone metadata
                $pdns_response = $pdns_client->makeRequest("/servers/localhost/zones/{$domain->pdns_zone_id}", 'PUT', $pdns_data);
                
                if ($pdns_response['status_code'] != 204) {
                    sendError($pdns_response['status_code'], "Failed to update domain account in PDNSAdmin");
                    return;
                }
            }
        } elseif ($old_account_id != $domain->account_id && empty($domain->account_id)) {
            // Remove account from domain in PDNSAdmin
            $domain->account = '';
            
            $pdns_data = [
                'account' => ''
            ];
            
            $pdns_response = $pdns_client->makeRequest("/servers/localhost/zones/{$domain->pdns_zone_id}", 'PUT', $pdns_data);
            
            if ($pdns_response['status_code'] != 204) {
                sendError($pdns_response['status_code'], "Failed to remove domain account in PDNSAdmin");
                return;
            }
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
