#!/usr/bin/env php
<?php
/**
 * IP Allowlist Management Utility
 * 
 * This script helps manage the IP allowlist for the PowerDNS Admin API.
 * Uses existing enhanced IPv4/IPv6 validation functions.
 * 
 * Usage:
 *   php manage-ips.php list                    # List current allowed IPs
 *   php manage-ips.php add 192.168.1.50       # Add single IP
 *   php manage-ips.php add 10.0.0.0/24        # Add CIDR range
 *   php manage-ips.php remove 192.168.1.50    # Remove IP
 *   php manage-ips.php test 192.168.1.100     # Test if IP would be allowed
 *   php manage-ips.php current                # Show your current IP
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
        
        $config['security']['allowed_ips'][] = $ip;
        
        if ($this->saveConfig()) {
            echo "✅ Added IP: $ip\n";
            return true;
        }
        
        echo "❌ Failed to save configuration\n";
        return false;
    }
    
    public function removeIP($ip) {
        global $config;
        
        $key = array_search($ip, $config['security']['allowed_ips']);
        if ($key === false) {
            echo "❌ IP not found: $ip\n";
            return false;
        }
        
        unset($config['security']['allowed_ips'][$key]);
        $config['security']['allowed_ips'] = array_values($config['security']['allowed_ips']); // Reindex
        
        if ($this->saveConfig()) {
            echo "✅ Removed IP: $ip\n";
            return true;
        }
        
        echo "❌ Failed to save configuration\n";
        return false;
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
    
    private function saveConfig() {
        global $config;
        
        $content = file_get_contents($this->config_file);
        if (!$content) return false;
        
        // Create the new allowed_ips array content
        $ips_code = "    'allowed_ips' => [\n";
        foreach ($config['security']['allowed_ips'] as $ip) {
            $ips_code .= "        '$ip',\n";
        }
        $ips_code .= "    ],";
        
        // Replace the allowed_ips section
        $pattern = "/(\s+'allowed_ips'\s*=>\s*\[)[^]]*(\],)/s";
        $content = preg_replace($pattern, "$1\n" . substr($ips_code, 4) . "\n    $2", $content);
        
        return file_put_contents($this->config_file, $content) !== false;
    }
}

// Main execution
if ($argc < 2) {
    echo "PowerDNS Admin API - Global IP Management\n";
    echo "Usage: php manage-ips.php <command> [arguments]\n\n";
    echo "Commands:\n";
    echo "  list                    Show all allowed IPs\n";
    echo "  add <ip>               Add IP or CIDR range\n";
    echo "  remove <ip>            Remove IP or CIDR range\n";
    echo "  test <ip>              Test if IP is allowed\n\n";
    echo "Examples:\n";
    echo "  php manage-ips.php add 192.168.1.100\n";
    echo "  php manage-ips.php add 203.0.113.0/24\n";
    echo "  php manage-ips.php add 2001:db8::/32\n";
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
            echo "❌ Usage: php manage-ips.php add <ip>\n";
            exit(1);
        }
        $manager->addIP($argv[2]);
        break;
        
    case 'remove':
        if ($argc < 3) {
            echo "❌ Usage: php manage-ips.php remove <ip>\n";
            exit(1);
        }
        $manager->removeIP($argv[2]);
        break;
        
    case 'test':
        if ($argc < 3) {
            echo "❌ Usage: php manage-ips.php test <ip>\n";
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

// ANSI color codes for terminal output
define('COLOR_GREEN', "\033[32m");
define('COLOR_RED', "\033[31m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_BLUE', "\033[34m");
define('COLOR_RESET', "\033[0m");

function showHelp() {
    echo COLOR_BLUE . "IP Allowlist Management Utility" . COLOR_RESET . "\n\n";
    echo "Usage:\n";
    echo "  php manage-ips.php list                    # List current allowed IPs\n";
    echo "  php manage-ips.php add <IP/CIDR>          # Add IP or CIDR range\n";
    echo "  php manage-ips.php remove <IP/CIDR>       # Remove IP or CIDR range\n";
    echo "  php manage-ips.php test <IP>              # Test if IP would be allowed\n";
    echo "  php manage-ips.php current                # Show your current IP\n";
    echo "  php manage-ips.php help                   # Show this help\n\n";
    echo "Examples:\n";
    echo "  php manage-ips.php add 192.168.1.50       # Add single IPv4\n";
    echo "  php manage-ips.php add 10.0.0.0/24        # Add IPv4 CIDR range\n";
    echo "  php manage-ips.php add 2001:db8::1        # Add single IPv6\n";
    echo "  php manage-ips.php add 2001:db8::/32      # Add IPv6 CIDR range\n\n";
}

function listAllowedIps() {
    global $api_security;
    
    echo COLOR_BLUE . "Current IP Allowlist:" . COLOR_RESET . "\n";
    echo str_repeat("-", 50) . "\n";
    
    if (empty($api_security['allowed_ips'])) {
        echo COLOR_YELLOW . "No IPs configured in allowlist" . COLOR_RESET . "\n";
        return;
    }
    
    foreach ($api_security['allowed_ips'] as $index => $ip) {
        $type = strpos($ip, ':') !== false ? 'IPv6' : 'IPv4';
        $range = strpos($ip, '/') !== false ? 'Range' : 'Single';
        echo sprintf("%2d. %-18s [%s %s]\n", $index + 1, $ip, $type, $range);
    }
    
    echo "\nTotal: " . count($api_security['allowed_ips']) . " entries\n";
    echo "IP Allowlist Status: " . ($api_security['require_ip_allowlist'] ? 
        COLOR_GREEN . "ENABLED" : COLOR_RED . "DISABLED") . COLOR_RESET . "\n";
}

function addIpToAllowlist($new_ip) {
    global $api_security;
    
    // Validate IP format
    if (!isValidIpOrCidr($new_ip)) {
        echo COLOR_RED . "Error: Invalid IP address or CIDR range format" . COLOR_RESET . "\n";
        return false;
    }
    
    // Check if already exists
    if (in_array($new_ip, $api_security['allowed_ips'])) {
        echo COLOR_YELLOW . "IP/CIDR already exists in allowlist: $new_ip" . COLOR_RESET . "\n";
        return false;
    }
    
    echo COLOR_GREEN . "Adding to allowlist: $new_ip" . COLOR_RESET . "\n";
    echo COLOR_YELLOW . "Note: You need to manually update config/config.php to make this permanent" . COLOR_RESET . "\n";
    
    // Show the line to add
    echo "\nAdd this line to the 'allowed_ips' array in config/config.php:\n";
    echo COLOR_BLUE . "        '$new_ip',           // Added " . date('Y-m-d H:i:s') . COLOR_RESET . "\n";
    
    return true;
}

function removeIpFromAllowlist($remove_ip) {
    global $api_security;
    
    if (!in_array($remove_ip, $api_security['allowed_ips'])) {
        echo COLOR_YELLOW . "IP/CIDR not found in allowlist: $remove_ip" . COLOR_RESET . "\n";
        return false;
    }
    
    echo COLOR_GREEN . "Would remove from allowlist: $remove_ip" . COLOR_RESET . "\n";
    echo COLOR_YELLOW . "Note: You need to manually remove this line from config/config.php" . COLOR_RESET . "\n";
    
    return true;
}

function testIpAccess($test_ip) {
    global $api_security;
    
    if (!filter_var($test_ip, FILTER_VALIDATE_IP)) {
        echo COLOR_RED . "Error: Invalid IP address format" . COLOR_RESET . "\n";
        return;
    }
    
    echo COLOR_BLUE . "Testing IP access: $test_ip" . COLOR_RESET . "\n";
    echo str_repeat("-", 40) . "\n";
    
    $ip_type = filter_var($test_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 'IPv6' : 'IPv4';
    echo "IP Type: $ip_type\n";
    echo "Allowlist Status: " . ($api_security['require_ip_allowlist'] ? 
        COLOR_GREEN . "ENABLED" : COLOR_RED . "DISABLED") . COLOR_RESET . "\n";
    
    if (!$api_security['require_ip_allowlist']) {
        echo COLOR_GREEN . "✓ Access ALLOWED (IP allowlist disabled)" . COLOR_RESET . "\n";
        return;
    }
    
    // Test against each allowed IP/range
    $access_granted = false;
    echo "\nTesting against allowlist entries:\n";
    
    foreach ($api_security['allowed_ips'] as $allowed_ip) {
        $match = ipInRange($test_ip, $allowed_ip);
        $status = $match ? COLOR_GREEN . "✓ MATCH" : COLOR_RED . "✗ no match";
        echo "  $allowed_ip: $status" . COLOR_RESET . "\n";
        
        if ($match) {
            $access_granted = true;
        }
    }
    
    echo "\nFinal Result: ";
    if ($access_granted) {
        echo COLOR_GREEN . "✓ ACCESS GRANTED" . COLOR_RESET . "\n";
    } else {
        echo COLOR_RED . "✗ ACCESS DENIED" . COLOR_RESET . "\n";
    }
}

function getCurrentIp() {
    echo COLOR_BLUE . "Current IP Detection:" . COLOR_RESET . "\n";
    echo str_repeat("-", 40) . "\n";
    
    // Try different methods to detect current IP
    $methods = [
        'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? 'Not available',
        'HTTP_X_FORWARDED_FOR' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'Not available',
        'HTTP_X_REAL_IP' => $_SERVER['HTTP_X_REAL_IP'] ?? 'Not available',
        'HTTP_CF_CONNECTING_IP' => $_SERVER['HTTP_CF_CONNECTING_IP'] ?? 'Not available'
    ];
    
    foreach ($methods as $method => $ip) {
        echo sprintf("%-20s: %s\n", $method, $ip);
    }
    
    $detected_ip = getClientIpAddress();
    echo "\nDetected Client IP: " . COLOR_GREEN . $detected_ip . COLOR_RESET . "\n";
    
    // Test if current IP would be allowed
    echo "\nTesting current IP access:\n";
    testIpAccess($detected_ip);
}

function isValidIpOrCidr($input) {
    // Check if it's a single IP (IPv4 or IPv6)
    if (filter_var($input, FILTER_VALIDATE_IP)) {
        return true;
    }
    
    // Check if it's CIDR notation
    if (strpos($input, '/') !== false) {
        list($ip, $mask) = explode('/', $input, 2);
        
        // Validate IP part
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }
        
        // Validate mask part
        if (!is_numeric($mask)) {
            return false;
        }
        
        $mask = intval($mask);
        
        // IPv4 CIDR: mask should be 0-32
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $mask >= 0 && $mask <= 32;
        }
        
        // IPv6 CIDR: mask should be 0-128
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $mask >= 0 && $mask <= 128;
        }
    }
    
    return false;
}

// Command line argument processing
if ($argc < 2) {
    showHelp();
    exit(1);
}

$command = $argv[1];

switch ($command) {
    case 'list':
        listAllowedIps();
        break;
        
    case 'add':
        if ($argc < 3) {
            echo COLOR_RED . "Error: IP address or CIDR range required" . COLOR_RESET . "\n";
            echo "Usage: php manage-ips.php add <IP/CIDR>\n";
            exit(1);
        }
        addIpToAllowlist($argv[2]);
        break;
        
    case 'remove':
        if ($argc < 3) {
            echo COLOR_RED . "Error: IP address or CIDR range required" . COLOR_RESET . "\n";
            echo "Usage: php manage-ips.php remove <IP/CIDR>\n";
            exit(1);
        }
        removeIpFromAllowlist($argv[2]);
        break;
        
    case 'test':
        if ($argc < 3) {
            echo COLOR_RED . "Error: IP address required" . COLOR_RESET . "\n";
            echo "Usage: php manage-ips.php test <IP>\n";
            exit(1);
        }
        testIpAccess($argv[2]);
        break;
        
    case 'current':
        getCurrentIp();
        break;
        
    case 'help':
    case '--help':
    case '-h':
        showHelp();
        break;
        
    default:
        echo COLOR_RED . "Error: Unknown command '$command'" . COLOR_RESET . "\n";
        showHelp();
        exit(1);
}

?>
