<?php
// Determine the correct base path
$base_path = realpath(__DIR__ . '/..');

require_once $base_path . '/config/config.php';
require_once $base_path . '/config/database.php';
require_once $base_path . '/includes/database-compat.php';
require_once $base_path . '/models/Domain.php';
require_once $base_path . '/models/Account.php';
require_once $base_path . '/classes/PDNSAdminClient.php';

// API key is already validated in index.php, log the request
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
    $stmt = $domain->readByAccountId($account_id);
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
    
    // Get all domains from PowerDNS Admin
    $pdns_response = $pdns_client->getAllDomains();
    
    if($pdns_response['status_code'] == 200) {
        $pdns_domains = $pdns_response['data'];
        $synced_count = 0;
        $updated_count = 0;
        
        // Start a transaction to prevent race conditions
        $db->beginTransaction();
        
        try {
            foreach($pdns_domains as $pdns_domain) {
                $domain_name = $pdns_domain['name'] ?? '';
                $pdns_zone_id = $pdns_domain['id'] ?? null;
                
                if (empty($domain_name)) {
                    continue; // Skip domains without a name
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
                    // Domain exists, update it
                    $domain_obj->id = $existing_domain['id'];
                    $domain_obj->readByName(); // Load full data
                    $domain_obj->pdns_zone_id = $pdns_zone_id;
                    $domain_obj->name = $domain_name;
                    // Note: PowerDNS Admin API /pdnsadmin/zones only provides id and name
                    // Other fields like account, type, kind etc. are not available
                    // so we don't update them here to preserve existing data
                    
                    try {
                        if ($domain_obj->updateBasic()) {
                            $updated_count++;
                            error_log("Updated existing domain: {$domain_name} (zone_id: {$pdns_zone_id})");
                        }
                    } catch (Exception $e) {
                        error_log("Failed to update domain {$domain_name}: " . $e->getMessage());
                    }
                } else {
                    // Domain doesn't exist, create it with minimal data
                    $domain_obj->name = $domain_name;
                    $domain_obj->type = 'Zone'; // Default type
                    $domain_obj->pdns_zone_id = $pdns_zone_id;
                    $domain_obj->kind = 'Master'; // Default kind
                    $domain_obj->masters = '';
                    $domain_obj->dnssec = false;
                    $domain_obj->account = ''; // No account info available from API
                    $domain_obj->account_id = null; // No user linking available
                    
                    try {
                        if ($domain_obj->create()) {
                            $synced_count++;
                            error_log("Created new domain: {$domain_name} (zone_id: {$pdns_zone_id})");
                        }
                    } catch (Exception $e) {
                        error_log("Failed to create domain {$domain_name}: " . $e->getMessage());
                    }
                }
            }
            
            // Commit the transaction
            $db->commit();
            
            $message = "Sync completed: {$synced_count} domains added, {$updated_count} domains updated";
            sendResponse(200, array(
                'synced' => $synced_count,
                'updated' => $updated_count,
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
