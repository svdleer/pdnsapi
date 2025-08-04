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
        
        // Determine authentication method based on endpoint
        if ($this->isPDNSAdminEndpoint($endpoint)) {
            // PowerDNS Admin endpoints (/pdnsadmin/*) use Basic Auth with username/password
            if ($this->username && $this->password) {
                $credentials = base64_encode($this->username . ':' . $this->password);
                $headers[] = 'Authorization: Basic ' . $credentials;
            }
        } else {
            // PowerDNS server endpoints use X-API-Key header
            $use_server_key = $this->isServerEndpoint($endpoint);
            $key_to_use = $use_server_key ? $this->pdns_server_key : $this->api_key;
            
            if ($key_to_use) {
                $headers[] = 'X-API-Key: ' . $key_to_use;
            }
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Add timeouts to prevent hanging
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        
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
        return $this->makeRequest('/servers/localhost/zones');
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

    // PowerDNS Admin Zones (with account relationships)
    public function getAllZones() {
        return $this->makeRequest('/pdnsadmin/zones');
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

    public function getUser($user_id) {
        return $this->makeRequest("/pdnsadmin/users/{$user_id}");
    }

    /**
     * Determine if endpoint is a PowerDNS Admin specific endpoint requiring Basic Auth
     */
    private function isPDNSAdminEndpoint($endpoint) {
        return strpos($endpoint, '/pdnsadmin/') === 0;
    }

    /**
     * Determine if endpoint requires PowerDNS server API key (for direct PowerDNS requests)
     * Since we're going through PowerDNS Admin, we should use Admin API key for all requests
     */
    private function isServerEndpoint($endpoint) {
        // For now, always use PowerDNS Admin API key since all requests go through PowerDNS Admin
        // Only use PowerDNS server key if we were making direct requests to PowerDNS server
        return false;
        
        /* Original logic for direct PowerDNS server requests:
        $server_endpoints = [
            '/servers/localhost/zones',
            '/servers/1/zones',
            '/servers/localhost/config',
            '/servers/1/config',
            '/servers/localhost/statistics',
            '/servers/1/statistics'
        ];
        
        foreach ($server_endpoints as $server_endpoint) {
            if (strpos($endpoint, $server_endpoint) === 0) {
                return true;
            }
        }
        
        return false;
        */
    }
}
?>
