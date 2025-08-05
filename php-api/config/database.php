<?php
/**
 * Database Configuration
 */
if (!class_exists('Database')) {
class Database {
    private $host = 'cora.avant.nl';
    private $db_name = 'pdns_api_db';
    private $username = 'pdns_api_db';
    private $password = '8swoajKuchij]';
    private $charset = 'utf8mb4';
    private $conn;



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
?>
