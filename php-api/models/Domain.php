<?php
// Determine the correct base path
$base_path = realpath(__DIR__ . '/..');
require_once $base_path . '/config/database.php';
require_once $base_path . '/includes/database-compat.php';

/**
 * Domain Model
 */
class Domain {
    private $conn;
    private $table_name = "domains";

    public $id;
    public $name;
    public $type;
    public $account_id;
    public $pdns_zone_id;
    public $kind;
    public $masters;
    public $dnssec;
    public $account;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET name=:name, type=:type, account_id=:account_id, 
                    pdns_zone_id=:pdns_zone_id, kind=:kind, masters=:masters,
                    dnssec=:dnssec, account=:account, created_at=NOW()";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":account_id", $this->account_id);
        $stmt->bindParam(":pdns_zone_id", $this->pdns_zone_id);
        $stmt->bindParam(":kind", $this->kind);
        $stmt->bindParam(":masters", $this->masters);
        $stmt->bindParam(":dnssec", $this->dnssec);
        $stmt->bindParam(":account", $this->account);

        return $stmt->execute();
    }

    public function read() {
        $query = "SELECT d.*, a.name as account_name 
                FROM " . $this->table_name . " d
                LEFT JOIN accounts a ON d.account_id = a.id
                ORDER BY d.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne() {
        $query = "SELECT d.*, a.name as account_name 
                FROM " . $this->table_name . " d
                LEFT JOIN accounts a ON d.account_id = a.id
                WHERE d.id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->name = $row['name'];
            $this->type = $row['type'];
            $this->account_id = $row['account_id'];
            $this->pdns_zone_id = $row['pdns_zone_id'];
            $this->kind = $row['kind'];
            $this->masters = $row['masters'];
            $this->dnssec = $row['dnssec'];
            $this->account = $row['account'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }

    public function readByAccountId($account_id) {
        $query = "SELECT d.*, a.name as account_name 
                FROM " . $this->table_name . " d
                LEFT JOIN accounts a ON d.account_id = a.id
                WHERE d.account_id = ?
                ORDER BY d.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $account_id);
        $stmt->execute();
        return $stmt;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET account_id=:account_id, kind=:kind, masters=:masters,
                    dnssec=:dnssec, account=:account, updated_at=NOW()
                WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':account_id', $this->account_id);
        $stmt->bindParam(':kind', $this->kind);
        $stmt->bindParam(':masters', $this->masters);
        $stmt->bindParam(':dnssec', $this->dnssec);
        $stmt->bindParam(':account', $this->account);
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
        $query = "SELECT d.*, a.name as account_name 
                FROM " . $this->table_name . " d
                LEFT JOIN accounts a ON d.account_id = a.id
                WHERE d.name LIKE ? OR d.type LIKE ? OR a.name LIKE ?
                ORDER BY d.created_at DESC";

        $stmt = $this->conn->prepare($query);

        $keywords = "%{$keywords}%";
        $stmt->bindParam(1, $keywords);
        $stmt->bindParam(2, $keywords);
        $stmt->bindParam(3, $keywords);

        $stmt->execute();
        return $stmt;
    }

    public function addToAccount($domain_name, $account_id) {
        $query = "UPDATE " . $this->table_name . " 
                SET account_id = :account_id, updated_at = NOW() 
                WHERE name = :domain_name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':account_id', $account_id);
        $stmt->bindParam(':domain_name', $domain_name);
        
        return $stmt->execute();
    }

    public function removeFromAccount($domain_name) {
        $query = "UPDATE " . $this->table_name . " 
                SET account_id = NULL, updated_at = NOW() 
                WHERE name = :domain_name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':domain_name', $domain_name);
        
        return $stmt->execute();
    }
}
?>
