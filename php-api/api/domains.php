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

switch($request_method) {
    case 'GET':
        if ($sync === 'true') {
            syncDomainsFromPDNS($domain, $pdns_client);
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
            createDomain($domain, $pdns_client);
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
    
    // Get all domains from PDNSAdmin
    $pdns_response = $pdns_client->getAllDomains();
    
    if($pdns_response['status_code'] == 200) {
        $pdns_domains = $pdns_response['data'];
        $synced_count = 0;
        $updated_count = 0;
        
        foreach($pdns_domains as $pdns_domain) {
            $domain_name = $pdns_domain['name'] ?? '';
            
            if (empty($domain_name)) {
                continue; // Skip domains without a name
            }
            
            // Create a new domain object for each domain to avoid conflicts
            $domain_obj = new Domain($db);
            $domain_obj->name = $domain_name;
            
            // Check if domain exists using a specific method
            if ($domain_obj->readByName()) {
                // Domain exists, update it
                $domain_obj->type = $pdns_domain['type'] ?? 'Zone';
                $domain_obj->pdns_zone_id = $pdns_domain['id'];
                $domain_obj->kind = $pdns_domain['kind'] ?? 'Master';
                $domain_obj->masters = isset($pdns_domain['masters']) ? implode(',', $pdns_domain['masters']) : '';
                $domain_obj->dnssec = $pdns_domain['dnssec'] ?? false;
                $domain_obj->account = $pdns_domain['account'] ?? '';
                
                try {
                    if ($domain_obj->update()) {
                        $updated_count++;
                    }
                } catch (Exception $e) {
                    error_log("Failed to update domain {$domain_name}: " . $e->getMessage());
                }
            } else {
                // Domain doesn't exist, create it
                $domain_obj->name = $domain_name;
                $domain_obj->type = $pdns_domain['type'] ?? 'Zone';
                $domain_obj->pdns_zone_id = $pdns_domain['id'];
                $domain_obj->kind = $pdns_domain['kind'] ?? 'Master';
                $domain_obj->masters = isset($pdns_domain['masters']) ? implode(',', $pdns_domain['masters']) : '';
                $domain_obj->dnssec = $pdns_domain['dnssec'] ?? false;
                $domain_obj->account = $pdns_domain['account'] ?? '';
                $domain_obj->account_id = null; // Will be set later when we implement account linking
                
                try {
                    if ($domain_obj->create()) {
                        $synced_count++;
                    }
                } catch (Exception $e) {
                    error_log("Failed to create domain {$domain_name}: " . $e->getMessage());
                    
                    // If it's a duplicate key error, try to update instead
                    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        if ($domain_obj->readByName()) {
                            $domain_obj->type = $pdns_domain['type'] ?? 'Zone';
                            $domain_obj->pdns_zone_id = $pdns_domain['id'];
                            $domain_obj->kind = $pdns_domain['kind'] ?? 'Master';
                            $domain_obj->masters = isset($pdns_domain['masters']) ? implode(',', $pdns_domain['masters']) : '';
                            $domain_obj->dnssec = $pdns_domain['dnssec'] ?? false;
                            $domain_obj->account = $pdns_domain['account'] ?? '';
                            
                            try {
                                if ($domain_obj->update()) {
                                    $updated_count++;
                                }
                            } catch (Exception $update_e) {
                                error_log("Failed to update domain {$domain_name} after duplicate error: " . $update_e->getMessage());
                            }
                        }
                    }
                }
            }
        }
        
        sendResponse(200, [
            'synced_count' => $synced_count, 
            'updated_count' => $updated_count,
            'total_pdns_domains' => count($pdns_domains)
        ], "Domains synchronized successfully");
    } else {
        sendError($pdns_response['status_code'], "Failed to fetch domains from PDNSAdmin");
    }
}

function createDomain($domain, $pdns_client) {
    global $db;
    $data = json_decode(file_get_contents("php://input"));
    
    if(!empty($data->name)) {
        // Get account information if account_id is provided
        $account_name = null;
        if (!empty($data->account_id)) {
            $account = new Account($db);
            $account->id = $data->account_id;
            if ($account->readOne()) {
                $account_name = $account->name;
            }
        }
        
        // Prepare data for PDNSAdmin
        $pdns_data = [
            'name' => $data->name,
            'kind' => $data->kind ?? 'Master',
            'masters' => $data->masters ?? [],
            'nameservers' => $data->nameservers ?? []
        ];
        
        // Add account to PDNSAdmin data if available
        if ($account_name) {
            $pdns_data['account'] = $account_name;
        }
        
        // Create domain in PDNSAdmin first
        $pdns_response = $pdns_client->createDomain($pdns_data);
        
        if($pdns_response['status_code'] == 201) {
            // If successful in PDNSAdmin, save to local database
            $domain->name = $data->name;
            $domain->type = 'Zone';
            $domain->account_id = $data->account_id ?? null;
            $domain->pdns_zone_id = $pdns_response['data']['id'] ?? null;
            $domain->kind = $data->kind ?? 'Master';
            $domain->masters = is_array($data->masters ?? []) ? implode(',', $data->masters) : '';
            $domain->dnssec = $data->dnssec ?? false;
            $domain->account = $account_name ?? '';
            
            if($domain->create()) {
                sendResponse(201, null, "Domain created successfully and assigned to account");
            } else {
                sendError(503, "Unable to create domain in local database");
            }
        } else {
            $error_msg = $pdns_response['data']['error'] ?? 'Failed to create domain in PDNSAdmin';
            sendError($pdns_response['status_code'], $error_msg);
        }
    } else {
        sendError(400, "Unable to create domain. Domain name is required");
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
