<?php
/**
 * PowerDNS Admin Database Configuration
 */
if (!class_exists('PDNSAdminDatabase')) {
class PDNSAdminDatabase {
    // These should be the credentials for your PowerDNS Admin database
    private $host = 'cora.avant.nl';  // Same host as your main DB
    private $db_name = 'pda';  // PowerDNS Admin database name - please update this
    private $username = 'pdns_api_db';   // Database user with read access to PowerDNS Admin DB
    private $password = '8swoajKuchij]';  // Database password
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
            error_log("PowerDNS Admin database connection error: " . $exception->getMessage());
            return null;
        }
        
        return $this->conn;
    }
}
}
?>
