#!/usr/bin/env php
<?php
/**
 * Database-driven Global IP Allowlist Management Utility
 * 
 * Manages the global IP allowlist stored in MySQL database.
 * Uses existing enhanced IPv4/IPv6 validation functions.
 * 
 * Usage:
 *   php manage-ips-clean.php list
 *   php manage-ips-clean.php add 192.168.1.100 "Office IP"
 *   php manage-ips-clean.php add 203.0.113.0/24 "Office network"
 *   php manage-ips-clean.php add 2001:db8::/32 "IPv6 network"
 *   php manage-ips-clean.php remove 192.168.1.100
 *   php manage-ips-clean.php test 192.168.1.50
 *   php manage-ips-clean.php disable 192.168.1.100
 *   php manage-ips-clean.php enable 192.168.1.100
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

class IPManager {
    private $pdo;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    public function listIPs() {
        echo "\n🔐 Global IP Allowlist (stored in database):\n";
        
        try {
            $stmt = $this->pdo->prepare("SELECT ip_address, description, enabled, created_at FROM ip_allowlist ORDER BY enabled DESC, ip_address");
            $stmt->execute();
            $ips = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($ips)) {
                echo "❌ No IPs configured - API will be inaccessible!\n";
                return;
            }
            
            $enabled_count = 0;
            foreach ($ips as $index => $row) {
                $status = $row['enabled'] ? "✅" : "❌";
                $desc = $row['description'] ? " ({$row['description']})" : "";
                echo sprintf("%2d. %s %s%s - added %s\n", 
                    $index + 1, 
                    $status, 
                    $row['ip_address'], 
                    $desc,
                    date('Y-m-d', strtotime($row['created_at']))
                );
                if ($row['enabled']) $enabled_count++;
            }
            
            echo "\n📊 Total: " . count($ips) . " entries (" . $enabled_count . " enabled)\n";
            
        } catch (Exception $e) {
            echo "❌ Database error: " . $e->getMessage() . "\n";
        }
    }
    
    public function addIP($ip, $description = '') {
        if (!$this->isValidIP($ip)) {
            echo "❌ Invalid IP format: $ip\n";
            echo "   Supported formats: 192.168.1.1, 192.168.1.0/24, 2001:db8::/32\n";
            return false;
        }
        
        try {
            $stmt = $this->pdo->prepare("INSERT INTO ip_allowlist (ip_address, description) VALUES (?, ?)");
            $stmt->execute([$ip, $description]);
            
            echo "✅ Added IP: $ip" . ($description ? " ($description)" : "") . "\n";
            return true;
            
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                echo "ℹ️  IP already exists: $ip\n";
            } else {
                echo "❌ Database error: " . $e->getMessage() . "\n";
            }
            return false;
        }
    }
    
    public function removeIP($ip) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM ip_allowlist WHERE ip_address = ?");
            $stmt->execute([$ip]);
            
            if ($stmt->rowCount() > 0) {
                echo "✅ Removed IP: $ip\n";
                return true;
            } else {
                echo "❌ IP not found: $ip\n";
                return false;
            }
            
        } catch (Exception $e) {
            echo "❌ Database error: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    public function enableIP($ip) {
        return $this->toggleIP($ip, true);
    }
    
    public function disableIP($ip) {
        return $this->toggleIP($ip, false);
    }
    
    private function toggleIP($ip, $enabled) {
        try {
            $stmt = $this->pdo->prepare("UPDATE ip_allowlist SET enabled = ? WHERE ip_address = ?");
            $stmt->execute([$enabled, $ip]);
            
            if ($stmt->rowCount() > 0) {
                $status = $enabled ? "enabled" : "disabled";
                echo "✅ IP $status: $ip\n";
                return true;
            } else {
                echo "❌ IP not found: $ip\n";
                return false;
            }
            
        } catch (Exception $e) {
            echo "❌ Database error: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    public function testIP($ip) {
        echo "🧪 Testing IP: $ip\n";
        
        if (!$this->isValidIP($ip)) {
            echo "❌ Invalid IP format\n";
            return;
        }
        
        // Load current allowlist and test
        $allowed_ips = getIpAllowlist();
        
        foreach ($allowed_ips as $allowed_ip) {
            if (ipInRange($ip, $allowed_ip)) {
                echo "✅ ALLOWED - matches rule: $allowed_ip\n";
                return;
            }
        }
        
        echo "❌ BLOCKED - no matching rules\n";
    }
    
    private function isValidIP($ip) {
        // Single IP address (IPv4 or IPv6)
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;
        }
        
        // CIDR notation
        if (strpos($ip, '/') !== false) {
            list($subnet, $mask) = explode('/', $ip);
            
            if (filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return is_numeric($mask) && $mask >= 0 && $mask <= 32;
            }
            
            if (filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                return is_numeric($mask) && $mask >= 0 && $mask <= 128;
            }
        }
        
        return false;
    }
}

// Main execution
if ($argc < 2) {
    echo "PowerDNS Admin API - Database IP Management\n";
    echo "Usage: php manage-ips-clean.php <command> [arguments]\n\n";
    echo "Commands:\n";
    echo "  list                           Show all allowed IPs from database\n";
    echo "  add <ip> [description]         Add IP or CIDR range to database\n";
    echo "  remove <ip>                    Remove IP or CIDR range from database\n";
    echo "  enable <ip>                    Enable existing IP in database\n";
    echo "  disable <ip>                   Disable existing IP in database\n";
    echo "  test <ip>                      Test if IP is allowed\n\n";
    echo "Examples:\n";
    echo "  php manage-ips-clean.php add 192.168.1.100 \"Office IP\"\n";
    echo "  php manage-ips-clean.php add 203.0.113.0/24 \"Office network\"\n";
    echo "  php manage-ips-clean.php add 2001:db8::/32 \"IPv6 network\"\n";
    echo "  php manage-ips-clean.php disable 192.168.1.100\n";
    exit(1);
}

$manager = new IPManager();
$command = $argv[1];

switch ($command) {
    case 'list':
        $manager->listIPs();
        break;
        
    case 'add':
        if ($argc < 3) {
            echo "❌ Usage: php manage-ips-clean.php add <ip> [description]\n";
            exit(1);
        }
        $description = isset($argv[3]) ? $argv[3] : '';
        $manager->addIP($argv[2], $description);
        break;
        
    case 'remove':
        if ($argc < 3) {
            echo "❌ Usage: php manage-ips-clean.php remove <ip>\n";
            exit(1);
        }
        $manager->removeIP($argv[2]);
        break;
        
    case 'enable':
        if ($argc < 3) {
            echo "❌ Usage: php manage-ips-clean.php enable <ip>\n";
            exit(1);
        }
        $manager->enableIP($argv[2]);
        break;
        
    case 'disable':
        if ($argc < 3) {
            echo "❌ Usage: php manage-ips-clean.php disable <ip>\n";
            exit(1);
        }
        $manager->disableIP($argv[2]);
        break;
        
    case 'test':
        if ($argc < 3) {
            echo "❌ Usage: php manage-ips-clean.php test <ip>\n";
            exit(1);
        }
        $manager->testIP($argv[2]);
        break;
        
    default:
        echo "❌ Unknown command: $command\n";
        echo "   Valid commands: list, add, remove, enable, disable, test\n";
        exit(1);
}

echo "\n";
?>
