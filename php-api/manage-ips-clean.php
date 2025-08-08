#!/usr/bin/env php
<?php
/**
 * Simple Global IP Allowlist Management Utility
 * 
 * Manages the global IP allowlist for the PowerDNS Admin API.
 * Uses existing enhanced IPv4/IPv6 validation functions.
 * 
 * Usage:
 *   php manage-ips-clean.php list
 *   php manage-ips-clean.php add 192.168.1.100
 *   php manage-ips-clean.php add 203.0.113.0/24
 *   php manage-ips-clean.php add 2001:db8::/32
 *   php manage-ips-clean.php remove 192.168.1.100
 *   php manage-ips-clean.php test 192.168.1.50
 */

require_once __DIR__ . '/config/config.php';

class IPManager {
    private $config_file;
    
    public function __construct() {
        $this->config_file = __DIR__ . '/config/config.php';
    }
    
    public function listIPs() {
        global $config;
        
        echo "\n🔐 Global IP Allowlist (applies to ALL API endpoints):\n";
        echo "Status: " . ($config['security']['ip_validation_enabled'] ? "✅ ENABLED" : "❌ DISABLED") . "\n\n";
        
        if (empty($config['security']['allowed_ips'])) {
            echo "❌ No IPs configured - API will be inaccessible!\n";
            return;
        }
        
        foreach ($config['security']['allowed_ips'] as $index => $ip) {
            echo sprintf("%2d. %s\n", $index + 1, $ip);
        }
        
        echo "\n📊 Total: " . count($config['security']['allowed_ips']) . " IP entries\n";
    }
    
    public function addIP($ip) {
        if (!$this->isValidIP($ip)) {
            echo "❌ Invalid IP format: $ip\n";
            echo "   Supported formats: 192.168.1.1, 192.168.1.0/24, 2001:db8::/32\n";
            return false;
        }
        
        global $config;
        
        if (in_array($ip, $config['security']['allowed_ips'])) {
            echo "ℹ️  IP already exists: $ip\n";
            return true;
        }
        
        echo "✅ To add IP: $ip\n";
        echo "📝 Manually add this line to config/config.php in the 'allowed_ips' array:\n";
        echo "        '$ip',           // Added " . date('Y-m-d H:i:s') . "\n";
        
        return true;
    }
    
    public function removeIP($ip) {
        global $config;
        
        $key = array_search($ip, $config['security']['allowed_ips']);
        if ($key === false) {
            echo "❌ IP not found: $ip\n";
            return false;
        }
        
        echo "✅ To remove IP: $ip\n";
        echo "📝 Manually remove this line from config/config.php\n";
        
        return true;
    }
    
    public function testIP($ip) {
        echo "🧪 Testing IP: $ip\n";
        
        if (!$this->isValidIP($ip)) {
            echo "❌ Invalid IP format\n";
            return;
        }
        
        global $config;
        
        // Use the existing enhanced IP validation functions
        foreach ($config['security']['allowed_ips'] as $allowed_ip) {
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
    echo "PowerDNS Admin API - Global IP Management\n";
    echo "Usage: php manage-ips-clean.php <command> [arguments]\n\n";
    echo "Commands:\n";
    echo "  list                    Show all allowed IPs\n";
    echo "  add <ip>               Add IP or CIDR range (shows instructions)\n";
    echo "  remove <ip>            Remove IP or CIDR range (shows instructions)\n";
    echo "  test <ip>              Test if IP is allowed\n\n";
    echo "Examples:\n";
    echo "  php manage-ips-clean.php add 192.168.1.100\n";
    echo "  php manage-ips-clean.php add 203.0.113.0/24\n";
    echo "  php manage-ips-clean.php add 2001:db8::/32\n";
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
            echo "❌ Usage: php manage-ips-clean.php add <ip>\n";
            exit(1);
        }
        $manager->addIP($argv[2]);
        break;
        
    case 'remove':
        if ($argc < 3) {
            echo "❌ Usage: php manage-ips-clean.php remove <ip>\n";
            exit(1);
        }
        $manager->removeIP($argv[2]);
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
        echo "   Valid commands: list, add, remove, test\n";
        exit(1);
}

echo "\n";
?>
