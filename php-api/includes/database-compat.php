<?php
/**
 * Database Configuration Compatibility Layer
 * This file handles both old and new database configuration formats
 */

// Check if we have the old config format
if (isset($config) && is_array($config)) {
    // Convert old format to Database class
    if (!class_exists('Database')) {
        class Database {
            private $host;
            private $db_name;
            private $username;
            private $password;
            private $conn;

            public function __construct() {
                global $config;
                $this->host = $config['host'] ?? 'localhost';
                $this->db_name = $config['dbname'] ?? 'pdns_api_db';
                $this->username = $config['username'] ?? 'root';
                $this->password = $config['password'] ?? '';
            }

            public function getConnection() {
                $this->conn = null;
                
                try {
                    $this->conn = new PDO(
                        "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                        $this->username,
                        $this->password
                    );
                    $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                } catch(PDOException $exception) {
                    echo "Connection error: " . $exception->getMessage();
                }
                
                return $this->conn;
            }
        }
    }
} else {
    // New format - Database class should already be defined
    if (!class_exists('Database')) {
        class Database {
            private $host = 'localhost';
            private $db_name = 'pdns_api_db';
            private $username = 'root';
            private $password = '';
            private $conn;

            public function getConnection() {
                $this->conn = null;
                
                try {
                    $this->conn = new PDO(
                        "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                        $this->username,
                        $this->password
                    );
                    $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                } catch(PDOException $exception) {
                    echo "Connection error: " . $exception->getMessage();
                }
                
                return $this->conn;
            }
        }
    }
}

// PDNSAdmin Configuration Compatibility
if (!isset($pdns_config) || !is_array($pdns_config)) {
    // Create default PDNSAdmin config if missing
    $pdns_config = [
        'base_url' => 'http://localhost:80/api/v1',
        'auth_type' => 'basic',
        'username' => 'admin',
        'password' => 'password',
        'api_key' => null
    ];
} else {
    // Ensure required keys exist
    $pdns_config = array_merge([
        'base_url' => 'http://localhost:80/api/v1',
        'auth_type' => 'basic',
        'username' => 'admin',
        'password' => 'password',
        'api_key' => null
    ], $pdns_config);
}
?>
