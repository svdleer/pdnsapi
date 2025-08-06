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
    public function getAllDomains() {
        return $this->makeRequest('/servers/1/zones');
    }

    public function getDomain($zone_id) {
        return $this->makeRequest("/pdnsadmin/zones/{$zone_id}");
    }

    public function createDomain($zone_data) {
        return $this->makeRequest('/pdnsadmin/zones', 'POST', $zone_data);
    }

    public function deleteDomain($zone_id) {
        return $this->makeRequest("/pdnsadmin/zones/{$zone_id}", 'DELETE');
    }

    // Account operations (PowerDNS Admin accounts/domains)
    public function getAllAccounts() {
        return $this->makeRequest('/pdnsadmin/accounts');
    }

    public function getAccount($account_identifier) {
        return $this->makeRequest("/pdnsadmin/accounts/{$account_identifier}");
    }

    public function createAccount($account_data) {
        return $this->makeRequest('/pdnsadmin/accounts', 'POST', $account_data);
    }

    public function updateAccount($account_identifier, $account_data) {
        return $this->makeRequest("/pdnsadmin/accounts/{$account_identifier}", 'PUT', $account_data);
    }

    public function deleteAccount($account_identifier) {
        return $this->makeRequest("/pdnsadmin/accounts/{$account_identifier}", 'DELETE');
    }

    // User operations
    public function getAllUsers() {
        return $this->makeRequest('/pdnsadmin/users');
    }

    public function createUser($user_data) {
        return $this->makeRequest('/pdnsadmin/users', 'POST', $user_data);
    }

    public function updateUser($username, $user_data) {
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
    public function getAllApiKeys() {
        return $this->makeRequest('/pdnsadmin/apikeys');
    }

    public function createApiKey($apikey_data) {
        return $this->makeRequest('/pdnsadmin/apikeys', 'POST', $apikey_data);
    }

    public function deleteApiKey($apikey_id) {
        return $this->makeRequest("/pdnsadmin/apikeys/{$apikey_id}", 'DELETE');
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
