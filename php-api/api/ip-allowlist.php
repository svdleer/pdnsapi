<?php
/**
 * IP Allowlist Management API
 * 
 * Provides API endpoints for managing the global IP allowlist used for API security.
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display, but log them
ini_set('log_errors', 1);

try {
    // Determine the correct base path
    $base_path = realpath(__DIR__ . '/..');

    require_once $base_path . '/config/config.php';
    require_once $base_path . '/config/database.php';
    require_once $base_path . '/includes/database-compat.php';

    // CRITICAL: Enforce authentication for direct API file access
    enforceHTTPS();
    addSecurityHeaders();
    requireApiKey();

    // Log successful authenticated request
    logApiRequest('ip-allowlist', $_SERVER['REQUEST_METHOD'], 200);

    header('Content-Type: application/json');

// Database class should now be available through compatibility layer
if (!class_exists('Database')) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database compatibility layer failed']);
    exit;
}

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Get request method and path
    $method = $_SERVER['REQUEST_METHOD'];
    $path = $_SERVER['PATH_INFO'] ?? '';
    $pathParts = array_filter(explode('/', $path));

    // Route to appropriate handler
    switch ($method) {
        case 'GET':
            handleGetRequest($conn, $pathParts);
            break;
        case 'POST':
            handlePostRequest($conn, $pathParts);
            break;
        case 'PUT':
            handlePutRequest($conn, $pathParts);
            break;
        case 'DELETE':
            handleDeleteRequest($conn, $pathParts);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

/**
 * Handle GET requests - List IP allowlist entries
 */
function handleGetRequest($conn, $pathParts) {
    try {
        $query = "SELECT id, ip_address, description, enabled, created_at, updated_at FROM ip_allowlist ORDER BY created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert boolean values to proper JSON booleans
        foreach ($results as &$row) {
            $row['enabled'] = (bool)$row['enabled'];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $results,
            'count' => count($results)
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to retrieve IP allowlist: ' . $e->getMessage()
        ]);
    }
}

/**
 * Handle POST requests - Add IP or test IP
 */
function handlePostRequest($conn, $pathParts) {
    // Check if this is a test request
    if (!empty($pathParts) && $pathParts[0] === 'test') {
        handleTestRequest($conn);
        return;
    }
    
    // Add new IP to allowlist
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['ip_address'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'IP address is required']);
        return;
    }
    
    $ip_address = trim($input['ip_address']);
    $description = $input['description'] ?? '';
    $enabled = isset($input['enabled']) ? (bool)$input['enabled'] : true;
    
    // Validate IP address
    if (!validateIpAddress($ip_address)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid IP address or CIDR notation']);
        return;
    }
    
    try {
        $query = "INSERT INTO ip_allowlist (ip_address, description, enabled) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $result = $stmt->execute([$ip_address, $description, $enabled]);
        
        if ($result) {
            $id = $conn->lastInsertId();
            echo json_encode([
                'success' => true,
                'message' => 'IP address added to allowlist',
                'data' => [
                    'id' => $id,
                    'ip_address' => $ip_address,
                    'description' => $description,
                    'enabled' => $enabled
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to add IP to allowlist']);
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicate entry
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => 'IP address already exists in allowlist']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
    }
}

/**
 * Handle PUT requests - Update existing IP allowlist entry
 */
function handlePutRequest($conn, $pathParts) {
    if (empty($pathParts) || !is_numeric($pathParts[0])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Valid ID required']);
        return;
    }
    
    $id = (int)$pathParts[0];
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Request body required']);
        return;
    }
    
    // Build update query dynamically based on provided fields
    $updateFields = [];
    $values = [];
    
    if (isset($input['ip_address'])) {
        $ip_address = trim($input['ip_address']);
        if (!validateIpAddress($ip_address)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid IP address or CIDR notation']);
            return;
        }
        $updateFields[] = 'ip_address = ?';
        $values[] = $ip_address;
    }
    
    if (isset($input['description'])) {
        $updateFields[] = 'description = ?';
        $values[] = $input['description'];
    }
    
    if (isset($input['enabled'])) {
        $updateFields[] = 'enabled = ?';
        $values[] = (bool)$input['enabled'];
    }
    
    if (empty($updateFields)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No fields to update']);
        return;
    }
    
    $values[] = $id; // For WHERE clause
    
    try {
        $query = "UPDATE ip_allowlist SET " . implode(', ', $updateFields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $conn->prepare($query);
        $result = $stmt->execute($values);
        
        if ($stmt->rowCount() > 0) {
            // Fetch updated record
            $selectQuery = "SELECT id, ip_address, description, enabled, created_at, updated_at FROM ip_allowlist WHERE id = ?";
            $selectStmt = $conn->prepare($selectQuery);
            $selectStmt->execute([$id]);
            $updatedRecord = $selectStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($updatedRecord) {
                $updatedRecord['enabled'] = (bool)$updatedRecord['enabled'];
                echo json_encode([
                    'success' => true,
                    'message' => 'IP allowlist entry updated',
                    'data' => $updatedRecord
                ]);
            } else {
                echo json_encode(['success' => true, 'message' => 'IP allowlist entry updated']);
            }
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'IP allowlist entry not found']);
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicate entry
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => 'IP address already exists in allowlist']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
    }
}

/**
 * Handle DELETE requests - Remove IP from allowlist
 */
function handleDeleteRequest($conn, $pathParts) {
    if (empty($pathParts) || !is_numeric($pathParts[0])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Valid ID required']);
        return;
    }
    
    $id = (int)$pathParts[0];
    
    try {
        // First check if the IP exists
        $checkQuery = "SELECT ip_address FROM ip_allowlist WHERE id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->execute([$id]);
        $ipRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$ipRecord) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'IP allowlist entry not found']);
            return;
        }
        
        // Delete the IP
        $query = "DELETE FROM ip_allowlist WHERE id = ?";
        $stmt = $conn->prepare($query);
        $result = $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'IP address removed from allowlist',
                'data' => ['ip_address' => $ipRecord['ip_address']]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to remove IP from allowlist']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Handle IP test requests
 */
function handleTestRequest($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['ip_address'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'IP address is required']);
        return;
    }
    
    $testIp = trim($input['ip_address']);
    
    // Validate IP address format
    if (!filter_var($testIp, FILTER_VALIDATE_IP)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid IP address format']);
        return;
    }
    
    try {
        // Check if IP is allowed
        $isAllowed = checkIpAllowed($conn, $testIp);
        
        echo json_encode([
            'success' => true,
            'ip_address' => $testIp,
            'allowed' => $isAllowed,
            'message' => $isAllowed ? 'IP address is allowed' : 'IP address is not in allowlist or is disabled'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Test failed: ' . $e->getMessage()]);
    }
}

/**
 * Validate IP address or CIDR notation
 */
function validateIpAddress($ip) {
    // Handle CIDR notation
    if (strpos($ip, '/') !== false) {
        $parts = explode('/', $ip);
        if (count($parts) !== 2) {
            return false;
        }
        
        $ipAddress = $parts[0];
        $mask = $parts[1];
        
        // Validate IP part
        if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            return false;
        }
        
        // Validate mask part
        if (!is_numeric($mask) || $mask < 0) {
            return false;
        }
        
        // Validate mask range based on IP type
        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $mask <= 32;
        } else if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $mask <= 128;
        }
        
        return false;
    } else {
        // Single IP address
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }
}

/**
 * Check if an IP is allowed (reusing logic from manage-ips-clean.php)
 */
function checkIpAllowed($conn, $clientIp) {
    try {
        $query = "SELECT ip_address FROM ip_allowlist WHERE enabled = 1";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $allowedIps = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($allowedIps as $allowedIp) {
            if (ipInRangeAllowlist($clientIp, $allowedIp)) {
                return true;
            }
        }
        
        return false;
    } catch (Exception $e) {
        error_log("IP allowlist check failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if an IP is within a given range (supports CIDR)
 */
function ipInRangeAllowlist($ip, $range) {
    if (strpos($range, '/') === false) {
        // Single IP comparison
        return $ip === $range;
    }
    
    list($rangeIp, $netmask) = explode('/', $range, 2);
    
    // IPv4 CIDR
    if (filter_var($rangeIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $rangeDecimal = ip2long($rangeIp);
        $ipDecimal = ip2long($ip);
        $wildcardDecimal = pow(2, (32 - $netmask)) - 1;
        $netmaskDecimal = ~ $wildcardDecimal;
        return ($ipDecimal & $netmaskDecimal) === ($rangeDecimal & $netmaskDecimal);
    }
    
    // IPv6 CIDR
    if (filter_var($rangeIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $rangeHex = inet_pton($rangeIp);
        $ipHex = inet_pton($ip);
        
        // Calculate how many bits to check
        $bitsToCheck = $netmask;
        
        for ($i = 0; $i < strlen($rangeHex); $i++) {
            if ($bitsToCheck <= 0) break;
            
            $rangeByte = ord($rangeHex[$i]);
            $ipByte = ord($ipHex[$i]);
            
            if ($bitsToCheck >= 8) {
                if ($rangeByte !== $ipByte) return false;
                $bitsToCheck -= 8;
            } else {
                $mask = 0xFF << (8 - $bitsToCheck);
                if (($rangeByte & $mask) !== ($ipByte & $mask)) return false;
                break;
            }
        }
        
        return true;
    }
    
    return false;
}
?>
