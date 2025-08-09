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
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        // Set headers
        $headers = ['Content-Type: application/json'];
        
        // Determine which API key to use based on endpoint
        $use_server_key = $this->isServerEndpoint($endpoint);
        $key_to_use = $use_server_key ? $this->pdns_server_key : $this->api_key;
        
        if ($this->auth_type === 'apikey' && $key_to_use) {
            // Use X-API-Key header
            $headers[] = 'X-API-Key: ' . $key_to_use;
        } elseif ($this->auth_type === 'basic' && $key_to_use) {
            // Use the already base64 encoded API key for Basic Auth
            $headers[] = 'Authorization: Basic ' . $key_to_use;
        } elseif ($this->auth_type === 'basic' && $this->username && $this->password) {
            // Encode username:password to base64 for basic auth
            $credentials = base64_encode($this->username . ':' . $this->password);
            $headers[] = 'Authorization: Basic ' . $credentials;
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'status_code' => $http_code,
            'data' => json_decode($response, true),
            'raw_response' => $response
        ];
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
        return $this->makeRequest('/pdnsadmin/zones');
    }

    public function getAllDomainsWithAccounts() {
        // Use the PowerDNS Admin API endpoint that returns full domain objects with account relationships
        // This endpoint should return domains with their associated account information
        return $this->makeRequest('/pdnsadmin/zones');
    }

    public function createDomain($zone_data) {
        return $this->makeRequest('/pdnsadmin/zones', 'POST', $zone_data);
    }

    public function deleteDomain($zone_id) {
        return $this->makeRequest("/pdnsadmin/zones/{$zone_id}", 'DELETE');
    }

    /**
     * Get domain by name - requires local database lookup to find PowerDNS ID
     * This function assumes you have a local database with domain name->powerdns_id mapping
     */
    public function getDomainByName($domain_name) {
        // First get the PowerDNS zone ID from local database
        $powerdns_id = $this->getDomainIdByName($domain_name);
        
        if (!$powerdns_id) {
            return [
                'status_code' => 404,
                'data' => null,
                'raw_response' => json_encode(['error' => 'Domain not found in local database'])
            ];
        }

        // PowerDNS Admin API doesn't support individual domain retrieval
        // So we get all domains and filter by the one we want
        $response = $this->getAllDomains();
        
        if ($response['status_code'] === 200 && isset($response['data'])) {
            foreach ($response['data'] as $domain) {
                // Check both name and PowerDNS ID matches
                if (isset($domain['name']) && $domain['name'] === $domain_name) {
                    return [
                        'status_code' => 200,
                        'data' => $domain,
                        'raw_response' => json_encode($domain)
                    ];
                }
                // Also check by PowerDNS ID if available
                if (isset($domain['id']) && $domain['id'] == $powerdns_id) {
                    return [
                        'status_code' => 200,
                        'data' => $domain,
                        'raw_response' => json_encode($domain)
                    ];
                }
            }
            
            // If not found in PowerDNS Admin, return local database info with note
            return [
                'status_code' => 200,
                'data' => [
                    'pdns_zone_id' => $powerdns_id,
                    'name' => $domain_name,
                    'source' => 'local_database_only',
                    'note' => 'Domain found in local database but not in PowerDNS Admin API response'
                ],
                'raw_response' => json_encode([
                    'pdns_zone_id' => $powerdns_id,
                    'name' => $domain_name,
                    'source' => 'local_database_only'
                ])
            ];
        }

        return [
            'status_code' => 500,
            'data' => null,
            'raw_response' => json_encode(['error' => 'Failed to retrieve domains from PowerDNS Admin API'])
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
     * Delete domain by name - requires local database lookup to find PowerDNS ID
     */
    public function deleteDomainByName($domain_name) {
        // First get the PowerDNS zone ID from local database
        $powerdns_id = $this->getDomainIdByName($domain_name);
        
        if (!$powerdns_id) {
            return [
                'status_code' => 404,
                'data' => null,
                'raw_response' => json_encode(['error' => 'Domain not found in local database'])
            ];
        }

        // Use the existing deleteDomain function with the PowerDNS ID
        return $this->deleteDomain($powerdns_id);
    }

    /**
     * Search domains by name pattern - uses local database for efficient searching
     */
    public function searchDomainsByName($name_pattern) {
        try {
            require_once __DIR__ . '/../includes/autoloader.php';
            $db = new Database();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare('SELECT id, name, pdns_zone_id FROM domains WHERE name LIKE :pattern ORDER BY name');
            $pattern = '%' . $name_pattern . '%';
            $stmt->bindParam(':pattern', $pattern);
            $stmt->execute();
            $results = $stmt->fetchAll();
            
            return [
                'status_code' => 200,
                'data' => $results,
                'raw_response' => json_encode($results)
            ];
        } catch (Exception $e) {
            return [
                'status_code' => 500,
                'data' => null,
                'raw_response' => json_encode(['error' => 'Database search failed: ' . $e->getMessage()])
            ];
        }
    }

    /**
     * Helper function to get domain ID by name from local database
     * This function connects to the local database to find the PowerDNS Admin zone ID
     */
    private function getDomainIdByName($domain_name) {
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
}
?>
