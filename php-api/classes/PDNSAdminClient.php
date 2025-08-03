<?php
/**
 * PDNSAdmin API Client
 */
class PDNSAdminClient {
    private $base_url;
    private $api_key;
    private $auth_type;
    private $username;
    private $password;

    public function __construct($config) {
        $this->base_url = rtrim($config['base_url'], '/');
        $this->api_key = $config['api_key'] ?? null;
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
        
        if ($this->auth_type === 'apikey' && $this->api_key) {
            $headers[] = 'X-API-Key: ' . $this->api_key;
        } elseif ($this->auth_type === 'basic' && $this->username && $this->password) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);
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
        return $this->makeRequest('/pdnsadmin/zones');
    }

    public function createDomain($zone_data) {
        return $this->makeRequest('/pdnsadmin/zones', 'POST', $zone_data);
    }

    public function deleteDomain($zone_id) {
        return $this->makeRequest("/pdnsadmin/zones/{$zone_id}", 'DELETE');
    }

    // Account operations
    public function getAllAccounts() {
        return $this->makeRequest('/pdnsadmin/accounts');
    }

    public function getAccount($account_name) {
        return $this->makeRequest("/pdnsadmin/accounts/{$account_name}");
    }

    public function createAccount($account_data) {
        return $this->makeRequest('/pdnsadmin/accounts', 'POST', $account_data);
    }

    public function updateAccount($account_name, $account_data) {
        return $this->makeRequest("/pdnsadmin/accounts/{$account_name}", 'PUT', $account_data);
    }

    public function deleteAccount($account_name) {
        return $this->makeRequest("/pdnsadmin/accounts/{$account_name}", 'DELETE');
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

    public function deleteUser($username) {
        return $this->makeRequest("/pdnsadmin/users/{$username}", 'DELETE');
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
}
?>
