<?php
// Determine the correct base path
$base_path = realpath(__DIR__ . '/..');
require_once $base_path . '/config/database.php';
require_once $base_path . '/includes/database-compat.php';

/**
 * Account Model
 */
class Account {
    private $conn;
    private $table_name = "accounts";

    public $id;
    public $username;
    public $password;
    public $firstname;
    public $lastname;
    public $email;
    public $role_id;
    public $ip_address;
    public $customer_id;
    public $pdns_account_id;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET username=:username, password=:password, firstname=:firstname, 
                    lastname=:lastname, email=:email, role_id=:role_id, 
                    ip_address=:ip_address, customer_id=:customer_id, 
                    pdns_account_id=:pdns_account_id, created_at=NOW()";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":firstname", $this->firstname);
        $stmt->bindParam(":lastname", $this->lastname);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":role_id", $this->role_id);
        $stmt->bindParam(":ip_address", $this->ip_address);
        $stmt->bindParam(":customer_id", $this->customer_id);
        $stmt->bindParam(":pdns_account_id", $this->pdns_account_id);

        return $stmt->execute();
    }

    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->username = $row['username'];
            $this->password = $row['password'];
            $this->firstname = $row['firstname'];
            $this->lastname = $row['lastname'];
            $this->email = $row['email'];
            $this->role_id = $row['role_id'];
            $this->ip_address = $row['ip_address'];
            $this->customer_id = $row['customer_id'];
            $this->pdns_account_id = $row['pdns_account_id'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }

    public function readByName($name = null) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE username = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $search_name = $name ?? $this->username;
        $stmt->bindParam(1, $search_name);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->password = $row['password'];
            $this->firstname = $row['firstname'];
            $this->lastname = $row['lastname'];
            $this->email = $row['email'];
            $this->role_id = $row['role_id'];
            $this->ip_address = $row['ip_address'];
            $this->customer_id = $row['customer_id'];
            $this->pdns_account_id = $row['pdns_account_id'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET password=:password, firstname=:firstname, lastname=:lastname, 
                    email=:email, role_id=:role_id, ip_address=:ip_address, 
                    customer_id=:customer_id, pdns_account_id=:pdns_account_id, 
                    updated_at=NOW()
                WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':firstname', $this->firstname);
        $stmt->bindParam(':lastname', $this->lastname);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':role_id', $this->role_id);
        $stmt->bindParam(':ip_address', $this->ip_address);
        $stmt->bindParam(':customer_id', $this->customer_id);
        $stmt->bindParam(':pdns_account_id', $this->pdns_account_id);
        $stmt->bindParam(':id', $this->id);

        return $stmt->execute();
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        return $stmt->execute();
    }

    public function search($keywords) {
        $query = "SELECT * FROM " . $this->table_name . " 
                WHERE username LIKE ? OR firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR ip_address LIKE ?
                ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);

        $keywords = "%{$keywords}%";
        $stmt->bindParam(1, $keywords);
        $stmt->bindParam(2, $keywords);
        $stmt->bindParam(3, $keywords);
        $stmt->bindParam(4, $keywords);
        $stmt->bindParam(5, $keywords);

        $stmt->execute();
        return $stmt;
    }
}
?>
