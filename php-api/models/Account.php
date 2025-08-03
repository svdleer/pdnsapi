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
    public $name;
    public $description;
    public $contact;
    public $mail;
    public $ip_addresses;
    public $pdns_account_id;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET name=:name, description=:description, contact=:contact, 
                    mail=:mail, ip_addresses=:ip_addresses, pdns_account_id=:pdns_account_id, created_at=NOW()";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":contact", $this->contact);
        $stmt->bindParam(":mail", $this->mail);
        $stmt->bindParam(":ip_addresses", $this->ip_addresses);
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
            $this->name = $row['name'];
            $this->description = $row['description'];
            $this->contact = $row['contact'];
            $this->mail = $row['mail'];
            $this->ip_addresses = $row['ip_addresses'];
            $this->pdns_account_id = $row['pdns_account_id'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }

    public function readByName() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE name = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->name);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->id = $row['id'];
            $this->description = $row['description'];
            $this->contact = $row['contact'];
            $this->mail = $row['mail'];
            $this->ip_addresses = $row['ip_addresses'];
            $this->pdns_account_id = $row['pdns_account_id'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET description=:description, contact=:contact, mail=:mail, 
                    ip_addresses=:ip_addresses, updated_at=NOW()
                WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':contact', $this->contact);
        $stmt->bindParam(':mail', $this->mail);
        $stmt->bindParam(':ip_addresses', $this->ip_addresses);
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
                WHERE name LIKE ? OR description LIKE ? OR contact LIKE ? OR mail LIKE ? OR ip_addresses LIKE ?
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
