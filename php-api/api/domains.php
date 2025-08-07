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
        if (isset($_GET['templates']) && $_GET['templates'] === 'true') {
            // Get available templates
            $templates = getAvailableTemplates();
            if ($templates !== false) {
                sendResponse(200, $templates, "Available templates retrieved");
            } else {
                sendError(500, "Failed to retrieve templates");
            }
        } elseif ($sync === 'true') {
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
        } elseif (isset($_GET['sync']) && $_GET['sync'] === 'true') {
            syncDomainsFromPDNS($domain, $pdns_client);
        } elseif ($json_data) {
            // Create domain via PowerDNS Admin API
            createDomainViaPDNS($domain, $pdns_client, $json_data);
        } else {
            sendError(400, "Invalid request. Provide JSON payload for domain creation or use ?action=add_to_account or ?sync=true");
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
    
    // NOTE: PowerDNS Admin /pdnsadmin/zones endpoint returns massive data (480KB+) 
    // which causes server crashes. We cannot reliably sync from PowerDNS Admin.
    
    // Attempt to get domains from PowerDNS Admin API with timeout protection
    try {
        // Set a very short timeout to prevent server crashes
        $pdns_response = $pdns_client->makeRequest('/pdnsadmin/zones', 'GET', null, 5); // 5 second timeout
        
        if($pdns_response['status_code'] == 200) {
            $pdns_domains = $pdns_response['data'];
            
            // Handle case where no domains exist in PowerDNS Admin
            if (empty($pdns_domains) || !is_array($pdns_domains)) {
                sendResponse(200, array(
                    'synced' => 0,
                    'updated' => 0,
                    'total_processed' => 0,
                    'warning' => 'PowerDNS Admin returned empty zones list'
                ), "Sync completed: No domains found in PowerDNS Admin");
                return;
            }
            
            // If we get here, we have data - proceed with normal sync
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
    
    } catch (Exception $e) {
        // Handle timeout or server crash scenarios
        error_log("PowerDNS Admin sync failed due to server issues: " . $e->getMessage());
        
        sendResponse(200, array(
            'synced' => 0,
            'updated' => 0,
            'total_processed' => 0,
            'error' => 'PowerDNS Admin API timeout/crash',
            'message' => 'The PowerDNS Admin server cannot handle the zones query due to large data size (480KB+). Manual sync may be required.'
        ), "Sync failed: PowerDNS Admin API timeout due to large dataset");
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

function createDomainViaPDNS($domain, $pdns_client, $json_data) {
    global $db;
    
    // Validate required fields
    if (empty($json_data['name'])) {
        sendError(400, "Domain name is required");
        return;
    }
    
    // Ensure domain name is canonical (ends with a dot)
    $domain_name = $json_data['name'];
    if (!str_ends_with($domain_name, '.')) {
        $domain_name .= '.';
    }

    // Prepare domain data for PowerDNS Admin API
    $pdns_data = [
        'name' => $domain_name,
        'kind' => 'Master',  // Always Master
        'type' => 'Native'   // Always Native
    ];
    
    // Add optional fields if provided
    if (isset($json_data['masters']) && !empty($json_data['masters'])) {
        $pdns_data['masters'] = is_array($json_data['masters']) ? $json_data['masters'] : [$json_data['masters']];
    }
    
    if (isset($json_data['account']) && !empty($json_data['account'])) {
        $pdns_data['account'] = $json_data['account'];
    }
    
    // Handle template - convert template to rrsets
    if (isset($json_data['template_id']) && !empty($json_data['template_id'])) {
        $template_rrsets = getTemplateAsRrsets($json_data['template_id'], $domain_name);
        if ($template_rrsets !== false) {
            $pdns_data['rrsets'] = $template_rrsets;
        } else {
            sendError(400, "Template not found or could not be converted");
            return;
        }
    } elseif (isset($json_data['template_name']) && !empty($json_data['template_name'])) {
        $template_rrsets = getTemplateAsRrsetsByName($json_data['template_name'], $domain_name);
        if ($template_rrsets !== false) {
            $pdns_data['rrsets'] = $template_rrsets;
        } else {
            sendError(400, "Template '{$json_data['template_name']}' not found or could not be converted");
            return;
        }
    }
    
    // Add rrsets if provided (for initial records or override template)
    if (isset($json_data['rrsets']) && is_array($json_data['rrsets'])) {
        $pdns_data['rrsets'] = $json_data['rrsets'];
    }
    
    // Create domain in PowerDNS Admin via API
    $pdns_response = $pdns_client->makeRequest('/pdnsadmin/zones', 'POST', $pdns_data);
    
    if ($pdns_response['status_code'] == 201 || $pdns_response['status_code'] == 200) {
        // Domain created successfully, now sync it to local database
        
        // Try to get the created domain info from PowerDNS Admin - use servers endpoint for specific zone
        $get_response = $pdns_client->makeRequest('/servers/localhost/zones/' . urlencode($domain_name), 'GET');
        
        if ($get_response['status_code'] == 200) {
            $created_domain = $get_response['data']; // Direct domain data, not an array
            
            if ($created_domain) {
                // Create domain in local database
                $domain_obj = new Domain($db);
                $domain_obj->name = $created_domain['name'];
                $domain_obj->type = 'Native';  // Always Native
                $domain_obj->pdns_zone_id = $created_domain['id'] ?? null;
                $domain_obj->kind = 'Master';  // Always Master
                $domain_obj->masters = isset($pdns_data['masters']) ? implode(',', $pdns_data['masters']) : '';
                $domain_obj->dnssec = false; // Default, can be updated later
                $domain_obj->account = $pdns_data['account'] ?? '';
                $domain_obj->account_id = null; // Will be set based on account name if provided
                
                // If account name is provided, try to find account_id
                if (!empty($pdns_data['account'])) {
                    $account = new Account($db);
                    $account_info = $account->getByName($pdns_data['account']);
                    if ($account_info) {
                        $domain_obj->account_id = $account_info['id'];
                        $domain_obj->account = $pdns_data['account'] . " ({$account_info['description']})";
                    }
                }
                
                if ($domain_obj->create()) {
                    sendResponse(201, [
                        'id' => $domain_obj->id,
                        'name' => $domain_obj->name,
                        'type' => $domain_obj->type,
                        'pdns_zone_id' => $domain_obj->pdns_zone_id,
                        'kind' => $domain_obj->kind,
                        'masters' => $domain_obj->masters,
                        'dnssec' => $domain_obj->dnssec,
                        'account' => $domain_obj->account,
                        'account_id' => $domain_obj->account_id,
                        'template_applied' => isset($json_data['template_id']) || isset($json_data['template_name']) ? true : false
                    ], "Domain created successfully");
                } else {
                    sendError(500, "Domain created in PowerDNS Admin but failed to save in local database");
                }
            } else {
                // Domain was created but we can't retrieve details from PowerDNS Admin
                // This is likely due to PowerDNS Admin not persisting the domain properly
                // Return a successful response with the information we have
                sendResponse(201, [
                    'name' => $domain_name,
                    'message' => 'Domain creation request sent to PowerDNS Admin successfully, but domain details could not be retrieved. Domain may need manual verification.'
                ], "Domain creation initiated");
            }
        } else {
            sendError(500, "Domain created but could not sync with local database");
        }
    } elseif ($pdns_response['status_code'] == 409) {
        sendError(409, "Domain already exists");
    } else {
        $error_msg = isset($pdns_response['data']['message']) ? $pdns_response['data']['message'] : 'Unknown error';
        sendError($pdns_response['status_code'], "Failed to create domain in PowerDNS Admin: " . $error_msg);
    }
}

function getTemplateAsRrsets($template_id, $domain_name) {
    global $pdns_admin_conn;
    
    if (!$pdns_admin_conn) {
        $pdns_admin_db = new PDNSAdminDatabase();
        $pdns_admin_conn = $pdns_admin_db->getConnection();
    }
    
    if (!$pdns_admin_conn) {
        error_log("Could not connect to PowerDNS Admin database for template conversion");
        return false;
    }
    
    try {
        // Get template records
        $stmt = $pdns_admin_conn->prepare("SELECT * FROM domain_template_record WHERE template_id = ? AND status = 1 ORDER BY name, type");
        $stmt->execute([$template_id]);
        $template_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($template_records)) {
            return false;
        }
        
        return convertTemplateRecordsToRrsets($template_records, $domain_name);
        
    } catch (PDOException $e) {
        error_log("Error getting template as rrsets: " . $e->getMessage());
        return false;
    }
}

function getTemplateAsRrsetsByName($template_name, $domain_name) {
    global $pdns_admin_conn;
    
    if (!$pdns_admin_conn) {
        $pdns_admin_db = new PDNSAdminDatabase();
        $pdns_admin_conn = $pdns_admin_db->getConnection();
    }
    
    if (!$pdns_admin_conn) {
        error_log("Could not connect to PowerDNS Admin database for template conversion");
        return false;
    }
    
    try {
        // Get template ID first
        $stmt = $pdns_admin_conn->prepare("SELECT id FROM domain_template WHERE name = ?");
        $stmt->execute([$template_name]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$template) {
            return false;
        }
        
        return getTemplateAsRrsets($template['id'], $domain_name);
        
    } catch (PDOException $e) {
        error_log("Error getting template by name: " . $e->getMessage());
        return false;
    }
}

function convertTemplateRecordsToRrsets($template_records, $domain_name) {
    $rrsets = [];
    $grouped_records = [];
    
    // Group records by name and type
    foreach ($template_records as $record) {
        $name = $record['name'];
        $type = $record['type'];
        
        // Replace @ with actual domain name
        if ($name === '@') {
            $name = $domain_name;
        } else {
            // If name doesn't end with domain name, append it
            if ($name !== $domain_name && !str_ends_with($name, '.' . $domain_name)) {
                $name = $name . '.' . $domain_name;
            }
        }
        
        // Ensure name ends with dot for PowerDNS
        if (!str_ends_with($name, '.')) {
            $name .= '.';
        }
        
        $key = $name . '|' . $type;
        
        if (!isset($grouped_records[$key])) {
            $grouped_records[$key] = [
                'name' => $name,
                'type' => $type,
                'ttl' => $record['ttl'],
                'records' => [],
                'comments' => []
            ];
        }
        
        // Add record data
        $grouped_records[$key]['records'][] = [
            'content' => $record['data'],
            'disabled' => false
        ];
        
        // Add comment if exists
        if (!empty($record['comment'])) {
            $grouped_records[$key]['comments'][] = [
                'content' => $record['comment'],
                'account' => '',
                'modified_at' => time()
            ];
        }
    }
    
    // Convert grouped records to PowerDNS rrsets format
    foreach ($grouped_records as $group) {
        $rrset = [
            'name' => $group['name'],
            'type' => $group['type'],
            'ttl' => $group['ttl'],
            'changetype' => 'REPLACE',
            'records' => $group['records']
        ];
        
        if (!empty($group['comments'])) {
            $rrset['comments'] = $group['comments'];
        }
        
        $rrsets[] = $rrset;
    }
    
    return $rrsets;
}

function getAvailableTemplates() {
    global $pdns_admin_conn;
    
    if (!$pdns_admin_conn) {
        $pdns_admin_db = new PDNSAdminDatabase();
        $pdns_admin_conn = $pdns_admin_db->getConnection();
    }
    
    if (!$pdns_admin_conn) {
        return false;
    }
    
    try {
        $stmt = $pdns_admin_conn->prepare("SELECT id, name, description FROM domain_template ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting available templates: " . $e->getMessage());
        return false;
    }
}
?>
