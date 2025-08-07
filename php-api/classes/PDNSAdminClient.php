<?php
/**
 * PDNSAdmin API Client
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
    // NOTE: PowerDNS Admin API has limited CRUD capabilities:
    // ✅ GET /pdnsadmin/zones (list all zones) - WORKS
    // ✅ POST /pdnsadmin/zones (create zone) - WORKS 
    // ✅ DELETE /pdnsadmin/zones/{id} (delete zone) - WORKS
    // ❌ GET /pdnsadmin/zones/{id} (get single zone) - HTTP 405 Method Not Allowed
    // ❌ PUT /pdnsadmin/zones/{id} (update zone) - HTTP 405 Method Not Allowed
    // ❌ PATCH /pdnsadmin/zones/{id} (patch zone) - HTTP 405 Method Not Allowed
    
    public function getAllDomains() {
        return $this->makeRequest('/pdnsadmin/zones');
    }

    public function getDomain($zone_id) {
        // WARNING: This endpoint returns HTTP 405 - Method Not Allowed
        // PowerDNS Admin API doesn't support individual zone retrieval
        return $this->makeRequest("/pdnsadmin/zones/{$zone_id}");
    }

    public function createDomain($zone_data) {
        return $this->makeRequest('/pdnsadmin/zones', 'POST', $zone_data);
    }

    public function updateDomain($zone_id, $zone_data) {
        // WARNING: This endpoint returns HTTP 405 - Method Not Allowed
        // PowerDNS Admin API doesn't support individual zone updates
        return $this->makeRequest("/pdnsadmin/zones/{$zone_id}", 'PUT', $zone_data);
    }

    public function deleteDomain($zone_id) {
        return $this->makeRequest("/pdnsadmin/zones/{$zone_id}", 'DELETE');
    }

    // Account operations (PowerDNS Admin accounts/domains)
    public function getAllAccounts() {
        return $this->makeRequest('/pdnsadmin/users');
    }

    public function getAccount($account_identifier) {
        return $this->makeRequest("/pdnsadmin/users/{$account_identifier}");
    }

    public function createAccount($account_data) {
        return $this->makeRequest('/pdnsadmin/users', 'POST', $account_data);
    }

    public function updateAccount($account_identifier, $account_data) {
        return $this->makeRequest("/pdnsadmin/users/{$account_identifier}", 'PUT', $account_data);
    }

    public function deleteAccount($account_identifier) {
        return $this->makeRequest("/pdnsadmin/users/{$account_identifier}", 'DELETE');
    }

    // User operations
    // NOTE: PowerDNS Admin API capabilities for users:
    // ✅ GET /pdnsadmin/users (list all users) - WORKS
    // ✅ GET /pdnsadmin/users/{username} (get single user) - WORKS
    // ❌ POST /pdnsadmin/users (create user) - HTTP 500 Server Error
    // ❌ PUT /pdnsadmin/users/{username} (update user) - HTTP 405 Method Not Allowed
    // ❌ DELETE /pdnsadmin/users/{username} (delete user) - Not tested
    
    public function getAllUsers() {
        return $this->makeRequest('/pdnsadmin/users');
    }

    public function getUser($username) {
        return $this->makeRequest("/pdnsadmin/users/{$username}");
    }

    public function createUser($user_data) {
        // WARNING: This endpoint returns HTTP 500 - Server Error
        // User creation via API may not be properly supported
        return $this->makeRequest('/pdnsadmin/users', 'POST', $user_data);
    }

    public function updateUser($username, $user_data) {
        // WARNING: This endpoint returns HTTP 405 - Method Not Allowed
        // PowerDNS Admin API doesn't support individual user updates
        return $this->makeRequest("/pdnsadmin/users/{$username}", 'PUT', $user_data);
    }

    public function deleteUser($user_id) {
        return $this->makeRequest("/pdnsadmin/users/{$user_id}", 'DELETE');
    }

    // Flexible user/account operations that can work with both identifiers
    public function updateUserByIdentifier($identifier, $user_data) {
        // For user operations, we always use the user endpoint with username
        return $this->updateUser($identifier, $user_data);
    }

    public function deleteUserByIdentifier($identifier) {
        // For user operations, we always use the user endpoint with username
        return $this->deleteUser($identifier);
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

    // Template operations - NOT SUPPORTED by PowerDNS Admin API
    // These methods are kept for compatibility but will always return error responses
    // Templates should be implemented as local database extensions
    
    public function getAllTemplates() {
        return [
            'status_code' => 404,
            'data' => null,
            'raw_response' => 'Template endpoints not supported by PowerDNS Admin API'
        ];
    }

    public function getTemplate($template_id) {
        return [
            'status_code' => 404,
            'data' => null,
            'raw_response' => 'Template endpoints not supported by PowerDNS Admin API'
        ];
    }

    public function createTemplate($template_data) {
        return [
            'status_code' => 404,
            'data' => null,
            'raw_response' => 'Template endpoints not supported by PowerDNS Admin API'
        ];
    }

    public function updateTemplate($template_id, $template_data) {
        return [
            'status_code' => 404,
            'data' => null,
            'raw_response' => 'Template endpoints not supported by PowerDNS Admin API'
        ];
    }

    public function deleteTemplate($template_id) {
        return [
            'status_code' => 404,
            'data' => null,
            'raw_response' => 'Template endpoints not supported by PowerDNS Admin API'
        ];
    }

    public function createDomainFromTemplate($template_id, $domain_data) {
        return [
            'status_code' => 404,
            'data' => null,
            'raw_response' => 'Template endpoints not supported by PowerDNS Admin API'
        ];
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
