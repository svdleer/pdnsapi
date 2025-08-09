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
    public $pdns_user_id; // PowerDNS Admin user.id (domain owner)
    public $pdns_zone_id;
    public $kind;
    public $masters;
    public $dnssec;
    public $account; // PowerDNS account field
    public $account_id; // Local account ID
    public $account_name; // Account name from accounts table
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET name=:name, type=:type, pdns_user_id=:pdns_user_id, 
                    pdns_zone_id=:pdns_zone_id, kind=:kind, masters=:masters, 
                    dnssec=:dnssec, account=:account, created_at=NOW()";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":pdns_user_id", $this->pdns_user_id);
        $stmt->bindParam(":pdns_zone_id", $this->pdns_zone_id);  
        $stmt->bindParam(":kind", $this->kind);
        $stmt->bindParam(":masters", $this->masters);
        $stmt->bindParam(":dnssec", $this->dnssec);
        $stmt->bindParam(":account", $this->account);

        try {
            $result = $stmt->execute();
            if (!$result) {
                error_log("SQL Error: " . implode(", ", $stmt->errorInfo()));
            }
            return $result;
        } catch (PDOException $e) {
            error_log("PDO Exception in create(): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create domain with data array (for template integration)
     */
    public function createDomain($domain_data) {
        try {
            // Set properties from data array
            $this->name = $domain_data['name'];
            $this->type = $domain_data['type'] ?? 'Zone';
            $this->pdns_user_id = $domain_data['pdns_user_id'] ?? $domain_data['account_id'] ?? null; // Map account_id to pdns_user_id
            $this->pdns_zone_id = $domain_data['pdns_zone_id'] ?? null;
            $this->kind = $domain_data['kind'] ?? 'Master';
            $this->masters = $domain_data['masters'] ?? '';
            $this->dnssec = $domain_data['dnssec'] ?? 0;
            $this->account = $domain_data['account'] ?? '';

            error_log("Creating domain: " . json_encode([
                'name' => $this->name,
                'type' => $this->type,
                'pdns_user_id' => $this->pdns_user_id,
                'kind' => $this->kind
            ]));

            if ($this->create()) {
                $this->id = $this->conn->lastInsertId();
                error_log("Domain created successfully with ID: " . $this->id);
                return $this->getDomainArray();
            }
            
            error_log("Domain create() method returned false");
            return false;
        } catch (Exception $e) {
            error_log("Failed to create domain: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Return domain as array
     */
    public function getDomainArray() {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'pdns_user_id' => $this->pdns_user_id,
            'pdns_zone_id' => $this->pdns_zone_id,
            'kind' => $this->kind,
            'masters' => $this->masters,
            'dnssec' => $this->dnssec,
            'account' => $this->account,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }

    public function read() {
        $query = "SELECT d.*, a.username as account_name 
                FROM " . $this->table_name . " d
                LEFT JOIN accounts a ON d.account_id = a.id
                ORDER BY d.created_at DESC";
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
            $this->type = $row['type'];
            $this->pdns_user_id = $row['pdns_user_id'];
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

    public function readByName() {
        $query = "SELECT d.*, a.username as account_name 
                FROM " . $this->table_name . " d
                LEFT JOIN accounts a ON d.account_id = a.id
                WHERE d.name = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->name);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->type = $row['type'];
            $this->pdns_user_id = $row['pdns_user_id'];
            $this->pdns_zone_id = $row['pdns_zone_id'];
            $this->kind = $row['kind'];
            $this->masters = $row['masters'];
            $this->dnssec = $row['dnssec'];
            $this->account = $row['account'];
            $this->account_id = $row['account_id']; // Add account_id property
            $this->account_name = $row['account_name']; // Add account_name property
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }

    public function readByUserId($pdns_user_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                WHERE pdns_user_id = ?
                ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $pdns_user_id);
        $stmt->execute();
        return $stmt;
    }

    public function readByAccountId($account_id) {
        $query = "SELECT d.*, a.username as account_name 
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
                SET type=:type, pdns_user_id=:pdns_user_id, pdns_zone_id=:pdns_zone_id,
                    kind=:kind, masters=:masters, dnssec=:dnssec, 
                    account=:account, updated_at=NOW()
                WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':type', $this->type);
        $stmt->bindParam(':pdns_user_id', $this->pdns_user_id);
        $stmt->bindParam(':pdns_zone_id', $this->pdns_zone_id);
        $stmt->bindParam(':kind', $this->kind);
        $stmt->bindParam(':masters', $this->masters);
        $stmt->bindParam(':dnssec', $this->dnssec);
        $stmt->bindParam(':account', $this->account);
        $stmt->bindParam(':id', $this->id);

        return $stmt->execute();
    }

    public function updateBasic() {
        // Update only basic fields available from PowerDNS Admin API
        $query = "UPDATE " . $this->table_name . "
                SET pdns_zone_id=:pdns_zone_id, updated_at=NOW()
                WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':pdns_zone_id', $this->pdns_zone_id);
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
        $query = "SELECT d.*, a.username as account_name 
                FROM " . $this->table_name . " d
                LEFT JOIN accounts a ON d.account_id = a.id
                WHERE d.name LIKE ? OR d.type LIKE ? OR a.username LIKE ?
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
