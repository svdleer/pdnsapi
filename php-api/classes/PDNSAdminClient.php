<?php
/**
 * PDNSAdmin API Client - PowerDNS Admin API is authoritative for core resources
 * 
 * This client provides access to PowerDNS Admin API endpoints.
 * PowerDNS Admin API is the authoritative source for:
 * 1. Users (accounts) - Full CRUD via /pdnsadmin/users
 * 2. Domains (zones) - Full operations via /pdnsadmin/zones + PowerDNS Server API
 * 3. API Keys - Full CRUD via /pdnsadmin/apikeys
 * 
 * WORKING ENDPOINTS:
 * - Users: GET (list/single), POST (create), PUT (update), DELETE
 * - Domains: GET (list all), POST (create), DELETE
 * - Domain Details/Records: GET, PATCH (via /servers/localhost/zones/{name})
 * - API Keys: Full CRUD (GET, POST, PUT, DELETE)
 * 
 * ENHANCED DOMAIN FUNCTIONS (via local DB + PowerDNS Server API):
 * - getDomainByName($name) - Get domain info by name
 * - updateDomainByName($name, $data) - Update domain via PowerDNS Server API
 * - deleteDomainByName($name) - Delete domain by name
 * - searchDomainsByName($pattern) - Search domains by name pattern
 * - getDomainDetailsByName($name) - Get full domain details & records
 * - updateDomainRecords($name, $rrsets) - Update DNS records
 * 
 * For templates and extended metadata, use local database models as supplements.
 */
class PDNSAdminClient {
    private $base_url;
    private $api_key;
    private $pdns_server_key;
    private $auth_type;
    private $username;
    private $password;

    public function __construct($config) {
        $this->base_url = rtrim($config['base_url'], '/');
        $this->api_key = $config['api_key'] ?? null;
        $this->pdns_server_key = $config['pdns_server_key'] ?? null;
        $this->auth_type = $config['auth_type'] ?? 'apikey'; // 'apikey' or 'basic'
        $this->username = $config['username'] ?? null;
        $this->password = $config['password'] ?? null;
    }

    public function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = $this->base_url . $endpoint;
        
        error_log("PDNSAdminClient: Making {$method} request to {$url}");
        if ($data) {
            error_log("PDNSAdminClient: Request data: " . json_encode($data, JSON_PRETTY_PRINT));
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 second timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // 10 second connection timeout
        
        // Set headers
        $headers = ['Content-Type: application/json'];
        
        // Determine which API key to use based on endpoint
        $use_server_key = $this->isServerEndpoint($endpoint);
        $key_to_use = $use_server_key ? $this->pdns_server_key : $this->api_key;
        
        error_log("PDNSAdminClient: Using " . ($use_server_key ? "server" : "admin") . " key for endpoint {$endpoint}");
        
        if ($use_server_key && $this->pdns_server_key) {
            // Server endpoints use X-API-Key header with the raw API key
            $headers[] = 'X-API-Key: ' . $this->pdns_server_key;
        } elseif (!$use_server_key && $this->auth_type === 'basic' && $this->api_key) {
            // Admin endpoints use Authorization: Basic with base64 encoded credentials
            $headers[] = 'Authorization: Basic ' . $this->api_key;
        } elseif ($this->auth_type === 'basic' && $this->username && $this->password) {
            // Fallback: Encode username:password to base64 for basic auth
            $credentials = base64_encode($this->username . ':' . $this->password);
            $headers[] = 'Authorization: Basic ' . $credentials;
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        error_log("PDNSAdminClient: Request headers: " . json_encode($headers));
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            error_log("PDNSAdminClient: CURL Error: {$curl_error}");
        }
        
        error_log("PDNSAdminClient: Response HTTP {$http_code}, length: " . strlen($response));
        error_log("PDNSAdminClient: Response preview: " . substr($response, 0, 500));
        
        $result = [
            'status_code' => $http_code,
            'data' => json_decode($response, true),
            'raw_response' => $response,
            'curl_error' => $curl_error
        ];
        
        // Log JSON decode errors
        if (json_last_error() !== JSON_ERROR_NONE && !empty($response)) {
            error_log("PDNSAdminClient: JSON decode error: " . json_last_error_msg());
            $result['json_error'] = json_last_error_msg();
        }
        
        return $result;
    }

    // Domain/Zone operations
    // PowerDNS Admin provides two layers:
    // 1. /pdnsadmin/zones - PowerDNS Admin zone management (list, create, delete)
    // 2. /servers/localhost/zones - PowerDNS Server API (full CRUD, records, details)
    // 
    // ✅ GET /pdnsadmin/zones (list all zones) - WORKS
    // ✅ POST /pdnsadmin/zones (create zone) - WORKS 
    // ✅ DELETE /pdnsadmin/zones/{id} (delete zone) - WORKS
    // ✅ GET /servers/localhost/zones/{name} (get zone details) - WORKS
    // ✅ PATCH /servers/localhost/zones/{name} (update zone/records) - WORKS
    // ✅ PUT /servers/localhost/zones/{name} (replace zone) - WORKS
    
    public function getAllDomains() {
        // Use PowerDNS Server API to get complete list of zones (bypasses user filtering)
        $response = $this->makeRequest('/servers/localhost/zones', 'GET');
        
        if ($response['status_code'] === 200) {
            return $response;
        }
        
        // Fallback to PowerDNS Admin API if server API fails
        return $this->makeRequest('/pdnsadmin/zones');
    }

    public function getAllDomainsWithAccounts() {
        // Get domains from PowerDNS Server API and enhance with local database info
        $domains_response = $this->makeRequest('/servers/localhost/zones', 'GET');
        
        if ($domains_response['status_code'] !== 200) {
            // Fallback to PowerDNS Admin API
            $domains_response = $this->makeRequest('/pdnsadmin/zones', 'GET');
        }
        
        if ($domains_response['status_code'] !== 200 || !isset($domains_response['data'])) {
            return [
                'status_code' => 500,
                'data' => null,
                'error' => 'Failed to retrieve domains from API'
            ];
        }
        
        // Enhance with local database information (accounts, metadata, business logic)
        try {
            require_once __DIR__ . '/../includes/autoloader.php';
            $db = new Database();
            $conn = $db->getConnection();
            
            // Get all local domain records with account associations
            $stmt = $conn->query("
                SELECT 
                    d.name,
                    d.account_id,
                    d.pdns_account_id,
                    d.created_at,
                    d.updated_at,
                    a.username as account_name,
                    a.email as account_email,
                    CONCAT(a.firstname, ' ', a.lastname) as account_description
                FROM domains d
                LEFT JOIN accounts a ON d.account_id = a.id
            ");
            $local_domains = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Index by domain name for quick lookup (handle both with/without trailing dots)
            $local_index = [];
            foreach ($local_domains as $local_domain) {
                $domain_name = rtrim($local_domain['name'], '.');
                $local_index[$domain_name] = $local_domain;
                // Also index with trailing dot for exact matches
                $local_index[$domain_name . '.'] = $local_domain;
            }
            
            error_log("Built local index with " . count($local_index) . " entries from " . count($local_domains) . " local domains");
            
        } catch (Exception $e) {
            error_log("Failed to load local domain data: " . $e->getMessage());
            $local_index = [];
        }
        
        // Enhance domain data with local database information
        $enhanced_domains = [];
        $matched_count = 0;
        foreach ($domains_response['data'] as $domain) {
            $domain_name = rtrim($domain['name'], '.');
            $domain_name_with_dot = $domain_name . '.';
            
            // Try both with and without trailing dot, and original name
            $local_data = null;
            if (isset($local_index[$domain_name])) {
                $local_data = $local_index[$domain_name];
            } elseif (isset($local_index[$domain_name_with_dot])) {
                $local_data = $local_index[$domain_name_with_dot];
            } elseif (isset($local_index[$domain['name']])) {
                $local_data = $local_index[$domain['name']];
            }
            
            if ($local_data !== null) {
                $matched_count++;
                error_log("Matched domain: " . $domain['name'] . " -> " . $local_data['name'] . " (account: " . ($local_data['account_id'] ?? 'none') . ")");
            }
            
            $enhanced_domain = [
                'id' => $domain['id'] ?? $domain['name'],
                'name' => $domain['name'],
                'type' => $domain['kind'] ?? $domain['type'] ?? 'Native',
                
                // PowerDNS data
                'powerdns_account' => $domain['account'] ?? null,
                'dnssec' => $domain['dnssec'] ?? false,
                'serial' => $domain['serial'] ?? null,
                'records_count' => isset($domain['rrsets']) ? count($domain['rrsets']) : null,
                
                // Local database enrichment
                'account_id' => $local_data['account_id'] ?? null,
                'account_name' => $local_data['account_name'] ?? null,
                'account_email' => $local_data['account_email'] ?? null,
                'account_description' => $local_data['account_description'] ?? null,
                'pdns_account_id' => $local_data['pdns_account_id'] ?? null,
                'created_at' => $local_data['created_at'] ?? null,
                'updated_at' => $local_data['updated_at'] ?? null,
                
                // Metadata flags
                'has_local_data' => $local_data !== null,
                'sync_status' => $local_data !== null ? 'synced' : 'api_only'
            ];
            
            $enhanced_domains[] = $enhanced_domain;
        }
        
        error_log("Enhanced " . count($enhanced_domains) . " domains ({$matched_count} matched with local data) from API + database");
        
        return [
            'status_code' => 200,
            'data' => $enhanced_domains,
            'metadata' => [
                'total_domains' => count($enhanced_domains),
                'synced_domains' => $matched_count,
                'api_only_domains' => count($enhanced_domains) - $matched_count,
                'source' => 'api_plus_database_enrichment',
                'domains_source' => 'powerdns_api',
                'enrichment_source' => 'local_database',
                'note' => 'PowerDNS API data enriched with local database business information'
            ]
        ];
    }

    public function createDomain($zone_data) {
        return $this->makeRequest('/pdnsadmin/zones', 'POST', $zone_data);
    }

    public function deleteDomain($zone_id) {
        return $this->makeRequest("/pdnsadmin/zones/{$zone_id}", 'DELETE');
    }

    /**
     * Get domain by name using PowerDNS Server API (most reliable)
     */
    public function getDomainByName($domain_name) {
        // Ensure domain name has trailing dot for canonical form
        $canonical_name = rtrim($domain_name, '.') . '.';
        
        // Try PowerDNS Server API first (most reliable and complete)
        $response = $this->makeRequest("/servers/localhost/zones/{$canonical_name}", 'GET');
        
        if ($response['status_code'] === 200) {
            return [
                'status_code' => 200,
                'data' => $response['data'],
                'raw_response' => $response['raw_response'],
                'source' => 'powerdns_server_api'
            ];
        }
        
        // Fallback: Search in all domains list
        error_log("Direct zone lookup failed, searching in all domains list");
        $all_domains = $this->getAllDomains();
        
        if ($all_domains['status_code'] === 200 && isset($all_domains['data'])) {
            foreach ($all_domains['data'] as $domain) {
                $domain_name_check = isset($domain['name']) ? rtrim($domain['name'], '.') : '';
                $search_name = rtrim($domain_name, '.');
                
                if ($domain_name_check === $search_name) {
                    return [
                        'status_code' => 200,
                        'data' => $domain,
                        'raw_response' => json_encode($domain),
                        'source' => 'domains_list_search'
                    ];
                }
            }
        }
        
        // Not found
        return [
            'status_code' => 404,
            'data' => null,
            'raw_response' => json_encode(['error' => 'Domain not found']),
            'source' => 'api_search_complete'
        ];
    }

    /**
     * Update domain by name - uses PowerDNS Server API via PowerDNS Admin
     */
    public function updateDomainByName($domain_name, $update_data) {
        // Use PowerDNS Server API endpoint via PowerDNS Admin (this should work!)
        // Based on api.py: /servers/<server_id>/zones/<zone_id> supports PUT/PATCH
        return $this->makeRequest("/servers/localhost/zones/{$domain_name}", 'PATCH', $update_data);
    }

    /**
     * Get domain details by name - uses PowerDNS Server API via PowerDNS Admin
     */
    public function getDomainDetailsByName($domain_name) {
        // Use PowerDNS Server API to get full zone details
        return $this->makeRequest("/servers/localhost/zones/{$domain_name}", 'GET');
    }

    /**
     * Update domain records (RRSets) by name
     */
    public function updateDomainRecords($domain_name, $rrsets_data) {
        // This allows updating DNS records within a zone
        $update_data = ['rrsets' => $rrsets_data];
        return $this->makeRequest("/servers/localhost/zones/{$domain_name}", 'PATCH', $update_data);
    }

    /**
     * Get domain records by name
     */
    public function getDomainRecords($domain_name) {
        // Get all records for a domain
        return $this->makeRequest("/servers/localhost/zones/{$domain_name}", 'GET');
    }

    /**
     * Delete domain by name using PowerDNS Admin API
     */
    public function deleteDomainByName($domain_name) {
        // Ensure domain name has trailing dot for canonical form
        $canonical_name = rtrim($domain_name, '.') . '.';
        
        // Use PowerDNS Admin API to delete the zone
        // Note: PowerDNS Admin API expects the canonical zone name
        return $this->makeRequest("/pdnsadmin/zones/{$canonical_name}", 'DELETE');
    }

    /**
     * Search domains by name pattern using API
     */
    public function searchDomainsByName($name_pattern) {
        // Get all domains from API
        $all_domains = $this->getAllDomains();
        
        if ($all_domains['status_code'] !== 200 || !isset($all_domains['data'])) {
            return [
                'status_code' => 500,
                'data' => null,
                'raw_response' => json_encode(['error' => 'Failed to retrieve domains for search'])
            ];
        }
        
        // Filter domains by name pattern
        $pattern_lower = strtolower($name_pattern);
        $matching_domains = [];
        
        foreach ($all_domains['data'] as $domain) {
            if (isset($domain['name'])) {
                $domain_name_lower = strtolower($domain['name']);
                if (strpos($domain_name_lower, $pattern_lower) !== false) {
                    $matching_domains[] = [
                        'id' => $domain['id'] ?? $domain['name'],
                        'name' => $domain['name'],
                        'type' => $domain['kind'] ?? $domain['type'] ?? 'Native',
                        'records_count' => isset($domain['rrsets']) ? count($domain['rrsets']) : null
                    ];
                }
            }
        }
        
        return [
            'status_code' => 200,
            'data' => $matching_domains,
            'raw_response' => json_encode($matching_domains),
            'metadata' => [
                'search_pattern' => $name_pattern,
                'total_matches' => count($matching_domains),
                'searched_from' => $all_domains['source'] ?? 'api'
            ]
        ];
    }

    /**
     * @deprecated This function is deprecated - use API-based domain lookups instead
     * Helper function to get domain ID by name from local database
     * This function connects to the local database to find the PowerDNS Admin zone ID
     */
    private function getDomainIdByName($domain_name) {
        error_log("DEPRECATED: getDomainIdByName() called - use API-based domain lookups instead");
        
        try {
            require_once __DIR__ . '/../includes/autoloader.php';
            $db = new Database();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare('SELECT pdns_zone_id FROM domains WHERE name = :name');
            $stmt->bindParam(':name', $domain_name);
            $stmt->execute();
            $result = $stmt->fetch();
            
            return $result ? $result['pdns_zone_id'] : null;
        } catch (Exception $e) {
            // Log error and return null if database connection fails
            error_log("Database error in getDomainIdByName: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Ensure a domain has proper user permissions so it appears in PowerDNS Admin API
     * This fixes the issue where domains without explicit permissions are filtered out
     */
    public function ensureDomainPermissions($domain_name, $username = 'admin') {
        try {
            require_once __DIR__ . '/../config/database.php';
            global $pdns_admin_pdo;
            
            // Get user ID
            $stmt = $pdns_admin_pdo->prepare('SELECT id FROM user WHERE username = :username');
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => "User '{$username}' not found"
                ];
            }
            
            $user_id = $user['id'];
            
            // Get domain ID
            $stmt = $pdns_admin_pdo->prepare('SELECT id FROM domain WHERE name = :domain_name');
            $stmt->bindParam(':domain_name', $domain_name);
            $stmt->execute();
            $domain = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$domain) {
                return [
                    'success' => false,
                    'message' => "Domain '{$domain_name}' not found"
                ];
            }
            
            $domain_id = $domain['id'];
            
            // Check if association already exists
            $stmt = $pdns_admin_pdo->prepare('SELECT id FROM domain_user WHERE domain_id = :domain_id AND user_id = :user_id');
            $stmt->bindParam(':domain_id', $domain_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            if ($stmt->fetch()) {
                return [
                    'success' => true,
                    'message' => "Domain '{$domain_name}' already has permissions for user '{$username}'"
                ];
            }
            
            // Create domain-user association
            $stmt = $pdns_admin_pdo->prepare('INSERT INTO domain_user (domain_id, user_id) VALUES (:domain_id, :user_id)');
            $stmt->bindParam(':domain_id', $domain_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            return [
                'success' => true,
                'message' => "Added domain permissions for '{$domain_name}' to user '{$username}'"
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    // User operations
    // NOTE: PowerDNS Admin API is the authoritative source for user management
    // ✅ GET /pdnsadmin/users (list all users) - WORKS
    // ✅ GET /pdnsadmin/users/{username} (get single user) - WORKS
    // ✅ POST /pdnsadmin/users (create user) - PowerDNS Admin API is leading
    // ✅ PUT /pdnsadmin/users/{username} (update user) - PowerDNS Admin API is leading
    
    public function getAllUsers() {
        return $this->makeRequest('/pdnsadmin/users');
    }

    public function getUser($username) {
        return $this->makeRequest("/pdnsadmin/users/{$username}");
    }

    public function createUser($user_data) {
        return $this->makeRequest('/pdnsadmin/users', 'POST', $user_data);
    }

    public function updateUser($username, $user_data) {
        return $this->makeRequest("/pdnsadmin/users/{$username}", 'PUT', $user_data);
    }

    public function deleteUser($username) {
        return $this->makeRequest("/pdnsadmin/users/{$username}", 'DELETE');
    }

    // API Key operations
    // NOTE: PowerDNS Admin API capabilities for API keys:
    // ✅ GET /pdnsadmin/apikeys (list all API keys) - WORKS
    // ✅ GET /pdnsadmin/apikeys/{id} (get single API key) - WORKS
    // ✅ POST /pdnsadmin/apikeys (create API key) - WORKS
    // ✅ PUT /pdnsadmin/apikeys/{id} (update API key) - WORKS
    // ✅ DELETE /pdnsadmin/apikeys/{id} (delete API key) - WORKS
    // ❌ PATCH /pdnsadmin/apikeys/{id} (patch API key) - HTTP 405 Method Not Allowed
    
    public function getAllApiKeys() {
        return $this->makeRequest('/pdnsadmin/apikeys');
    }

    public function getApiKey($apikey_id) {
        return $this->makeRequest("/pdnsadmin/apikeys/{$apikey_id}");
    }

    public function createApiKey($apikey_data) {
        return $this->makeRequest('/pdnsadmin/apikeys', 'POST', $apikey_data);
    }

    public function updateApiKey($apikey_id, $apikey_data) {
        return $this->makeRequest("/pdnsadmin/apikeys/{$apikey_id}", 'PUT', $apikey_data);
    }

    public function deleteApiKey($apikey_id) {
        return $this->makeRequest("/pdnsadmin/apikeys/{$apikey_id}", 'DELETE');
    }

    // Account operations
    public function getAllAccounts() {
        return $this->makeRequest('/pdnsadmin/accounts');
    }

    public function getAccountByName($account_name) {
        return $this->makeRequest("/pdnsadmin/accounts/{$account_name}");
    }

    public function createAccount($account_data) {
        return $this->makeRequest('/pdnsadmin/accounts', 'POST', $account_data);
    }

    public function updateAccount($account_id, $account_data) {
        return $this->makeRequest("/pdnsadmin/accounts/{$account_id}", 'PUT', $account_data);
    }

    public function deleteAccount($account_id) {
        return $this->makeRequest("/pdnsadmin/accounts/{$account_id}", 'DELETE');
    }

    /**
     * Determine if endpoint requires PowerDNS server API key (for proxied requests)
     */
    private function isServerEndpoint($endpoint) {
        // Endpoints that are proxied to PowerDNS server
        $server_endpoints = [
            '/servers/1/zones',
            '/servers/localhost/zones',
            '/servers/1/config',
            '/servers/localhost/config',
            '/servers/1/statistics',
            '/servers/localhost/statistics'
        ];
        
        // Check if endpoint starts with any server endpoint pattern
        foreach ($server_endpoints as $server_endpoint) {
            if (strpos($endpoint, $server_endpoint) === 0) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Sync PowerDNS domains with local database
     * This maintains local database records for business logic while PowerDNS handles DNS
     */
    public function syncDomainsToLocalDatabase() {
        try {
            // Get all domains from PowerDNS API
            $domains_response = $this->makeRequest('/servers/localhost/zones', 'GET');
            
            if ($domains_response['status_code'] !== 200) {
                return [
                    'success' => false,
                    'message' => 'Failed to fetch domains from PowerDNS API',
                    'status_code' => $domains_response['status_code']
                ];
            }
            
            require_once __DIR__ . '/../includes/autoloader.php';
            $db = new Database();
            $conn = $db->getConnection();
            
            $synced = 0;
            $updated = 0;
            $errors = 0;
            
            foreach ($domains_response['data'] as $domain) {
                $domain_name = rtrim($domain['name'], '.');
                
                try {
                    // Check if domain already exists in local database
                    $stmt = $conn->prepare('SELECT id FROM domains WHERE name = ?');
                    $stmt->execute([$domain_name]);
                    $exists = $stmt->fetch();
                    
                    if (!$exists) {
                        // Insert new domain record
                        $stmt = $conn->prepare('
                            INSERT INTO domains (name, type, pdns_zone_id, kind, dnssec, account, created_at, updated_at)
                            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
                        ');
                        
                        $stmt->execute([
                            $domain_name,
                            'Zone',
                            $domain['id'] ?? $domain_name,
                            $domain['kind'] ?? 'Native',
                            $domain['dnssec'] ?? 0,
                            $domain['account'] ?? null
                        ]);
                        
                        $synced++;
                        error_log("Synced new domain: {$domain_name}");
                    } else {
                        // Update existing domain
                        $stmt = $conn->prepare('
                            UPDATE domains 
                            SET pdns_zone_id = ?, kind = ?, dnssec = ?, account = ?, updated_at = NOW()
                            WHERE name = ?
                        ');
                        
                        $stmt->execute([
                            $domain['id'] ?? $domain_name,
                            $domain['kind'] ?? 'Native',
                            $domain['dnssec'] ?? 0,
                            $domain['account'] ?? null,
                            $domain_name
                        ]);
                        
                        $updated++;
                    }
                    
                } catch (Exception $e) {
                    error_log("Error syncing domain {$domain_name}: " . $e->getMessage());
                    $errors++;
                }
            }
            
            return [
                'success' => true,
                'message' => 'Domain sync completed',
                'stats' => [
                    'total_processed' => count($domains_response['data']),
                    'new_synced' => $synced,
                    'updated' => $updated,
                    'errors' => $errors
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Domain sync failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Domain sync failed: ' . $e->getMessage()
            ];
        }
    }
}
?>
