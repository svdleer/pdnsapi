<?php
/**
 * Database Configuration
 */

// Load environment variables
if (!defined('ENV_LOADED')) {
    require_once __DIR__ . '/../includes/env-loader.php';
    define('ENV_LOADED', true);
}

if (!class_exists('Database')) {
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $charset;
    private $conn;

    public function __construct() {
        $this->host = $_ENV['API_DB_HOST'] ?? 'localhost';
        $this->db_name = $_ENV['API_DB_NAME'] ?? 'database_name_required';
        $this->username = $_ENV['API_DB_USER'] ?? 'username_required';
        $this->password = $_ENV['API_DB_PASS'] ?? 'password_required';
        $this->charset = $_ENV['API_DB_CHARSET'] ?? 'utf8mb4';
    }

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            // Log the error but don't output it directly
            error_log("Database connection error: " . $exception->getMessage());
            return null;
        }
        
        return $this->conn;
    }
}
}

// PowerDNS Admin Database Connection
if (!class_exists('PDNSAdminDatabase')) {
class PDNSAdminDatabase {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $charset;
    private $conn;

    public function __construct() {
        $this->host = $_ENV['PDNS_ADMIN_DB_HOST'] ?? 'localhost';
        $this->db_name = $_ENV['PDNS_ADMIN_DB_NAME'] ?? 'database_name_required';
        $this->username = $_ENV['PDNS_ADMIN_DB_USER'] ?? 'username_required';
        $this->password = $_ENV['PDNS_ADMIN_DB_PASS'] ?? 'password_required';
        $this->charset = $_ENV['API_DB_CHARSET'] ?? 'utf8mb4';
    }

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            // Log the error but don't output it directly
            error_log("PowerDNS Admin database connection error: " . $exception->getMessage());
            return null;
        }
        
        return $this->conn;
    }
}
}

// Initialize global PDO connection
$database = new Database();
$pdo = $database->getConnection();

// Initialize PowerDNS Admin database connection
$pdns_admin_db = new PDNSAdminDatabase();
$pdns_admin_pdo = $pdns_admin_db->getConnection();
?>
