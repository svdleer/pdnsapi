<?php
// Determine the correct base path
$base_path = realpath(__DIR__ . '/..');

require_once $base_path . '/config/config.php';
require_once $base_path . '/config/database.php';
require_once $base_path . '/includes/database-compat.php';

// CRITICAL: Enforce authentication for direct API file access
enforceHTTPS();
addSecurityHeaders();
requireApiKey(); // This will exit with 401/403 if auth fails

// Only admin API keys can manage API keys
if (!isAdminApiKey()) {
    sendError(403, "Only admin API keys can manage API keys");
}

// Log successful authenticated request
logApiRequest('api-keys', $_SERVER['REQUEST_METHOD'], 200);

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get the HTTP method
$request_method = $_SERVER["REQUEST_METHOD"];

// Get parameters from URL
$key_id = isset($_GET['id']) ? $_GET['id'] : null;

// For POST, PUT, DELETE - check for JSON payload
$json_data = null;
$input = file_get_contents("php://input");
if (!empty($input)) {
    $json_data = json_decode($input, true);
}

switch($request_method) {
    case 'GET':
        if ($key_id) {
            getApiKey($db, $key_id);
        } else {
            listApiKeys($db);
        }
        break;
        
    case 'POST':
        createApiKey($db, $json_data);
        break;
        
    case 'PUT':
        if ($key_id || (isset($json_data['id']))) {
            $update_id = $key_id ?? $json_data['id'];
            updateApiKey($db, $update_id, $json_data);
        } else {
            sendError(400, "API key ID required for update");
        }
        break;
        
    case 'DELETE':
        if ($key_id) {
            deleteApiKey($db, $key_id);
        } else {
            sendError(400, "API key ID required for deletion");
        }
        break;
        
    default:
        sendError(405, "Method not allowed");
        break;
}

/**
 * List all API keys (excluding the actual key values)
 */
function listApiKeys($db) {
    try {
        $account_id_filter = isset($_GET['account_id']) ? $_GET['account_id'] : null;
        
        $query = "SELECT id, LEFT(api_key, 12) as key_prefix, account_id, description, 
                         permissions, enabled, created_at, updated_at, expires_at, 
                         last_used_at, created_by
                  FROM api_keys";
        
        if ($account_id_filter !== null) {
            $query .= " WHERE account_id = ?";
        }
        
        $query .= " ORDER BY created_at DESC";
        
        $stmt = $db->prepare($query);
        
        if ($account_id_filter !== null) {
            $stmt->execute([$account_id_filter]);
        } else {
            $stmt->execute();
        }
        
        $keys = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse JSON permissions for each key
        foreach ($keys as &$key) {
            $key['permissions'] = $key['permissions'] ? json_decode($key['permissions'], true) : null;
            $key['key_preview'] = $key['key_prefix'] . '...';
            unset($key['key_prefix']);
        }
        
        sendResponse(200, $keys);
        
    } catch (Exception $e) {
        error_log("Error listing API keys: " . $e->getMessage());
        sendError(500, "Failed to list API keys");
    }
}

/**
 * Get a specific API key details (excluding the actual key value)
 */
function getApiKey($db, $key_id) {
    try {
        $stmt = $db->prepare("
            SELECT id, LEFT(api_key, 12) as key_prefix, account_id, description, 
                   permissions, enabled, created_at, updated_at, expires_at, 
                   last_used_at, created_by
            FROM api_keys 
            WHERE id = ?
        ");
        $stmt->execute([$key_id]);
        $key = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$key) {
            sendError(404, "API key not found");
            return;
        }
        
        // Parse JSON permissions
        $key['permissions'] = $key['permissions'] ? json_decode($key['permissions'], true) : null;
        $key['key_preview'] = $key['key_prefix'] . '...';
        unset($key['key_prefix']);
        
        sendResponse(200, $key);
        
    } catch (Exception $e) {
        error_log("Error getting API key: " . $e->getMessage());
        sendError(500, "Failed to get API key");
    }
}

/**
 * Create a new account-scoped API key
 */
function createApiKey($db, $data) {
    // Validate required fields
    if (empty($data['account_id'])) {
        sendError(400, "account_id is required");
        return;
    }
    
    // Validate account exists
    $stmt = $db->prepare("SELECT id, username FROM accounts WHERE id = ?");
    $stmt->execute([$data['account_id']]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$account) {
        sendError(404, "Account not found");
        return;
    }
    
    // Generate a secure API key
    $api_key = 'pdns_' . bin2hex(random_bytes(32));
    $key_hash = hash('sha256', $api_key);
    
    // Default permissions for account-scoped keys
    $default_permissions = [
        'domains' => 'rw',           // read-write access to domains
        'create_domains' => true,    // can create new domains
        'delete_domains' => false,   // cannot delete domains by default
        'scope' => 'account'         // scope limited to account's domains
    ];
    
    // Merge with provided permissions
    $permissions = isset($data['permissions']) ? array_merge($default_permissions, $data['permissions']) : $default_permissions;
    
    // Sanitize delete_domains based on config (can be overridden)
    if (isset($data['allow_delete']) && $data['allow_delete'] === true) {
        $permissions['delete_domains'] = true;
    }
    
    $description = $data['description'] ?? "API key for account: {$account['username']}";
    $expires_at = isset($data['expires_at']) ? $data['expires_at'] : null;
    $enabled = isset($data['enabled']) ? (bool)$data['enabled'] : true;
    
    try {
        $stmt = $db->prepare("
            INSERT INTO api_keys (api_key, key_hash, account_id, description, permissions, enabled, expires_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $api_key,
            $key_hash,
            $data['account_id'],
            $description,
            json_encode($permissions),
            $enabled,
            $expires_at
        ]);
        
        $key_id = $db->lastInsertId();
        
        // Return the full API key ONLY on creation (this is the only time it will be shown)
        sendResponse(201, [
            'id' => $key_id,
            'api_key' => $api_key,  // IMPORTANT: Save this, it won't be shown again
            'account_id' => $data['account_id'],
            'account_username' => $account['username'],
            'description' => $description,
            'permissions' => $permissions,
            'enabled' => $enabled,
            'expires_at' => $expires_at,
            'warning' => 'Save this API key securely. It will not be displayed again.'
        ], "API key created successfully");
        
    } catch (Exception $e) {
        error_log("Error creating API key: " . $e->getMessage());
        sendError(500, "Failed to create API key");
    }
}

/**
 * Update an API key (permissions, description, enabled status)
 */
function updateApiKey($db, $key_id, $data) {
    try {
        // Get current key data
        $stmt = $db->prepare("SELECT id, account_id FROM api_keys WHERE id = ?");
        $stmt->execute([$key_id]);
        $key = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$key) {
            sendError(404, "API key not found");
            return;
        }
        
        // Build update query dynamically
        $updates = [];
        $params = [];
        
        if (isset($data['description'])) {
            $updates[] = "description = ?";
            $params[] = $data['description'];
        }
        
        if (isset($data['permissions'])) {
            $updates[] = "permissions = ?";
            $params[] = json_encode($data['permissions']);
        }
        
        if (isset($data['enabled'])) {
            $updates[] = "enabled = ?";
            $params[] = (bool)$data['enabled'];
        }
        
        if (isset($data['expires_at'])) {
            $updates[] = "expires_at = ?";
            $params[] = $data['expires_at'];
        }
        
        if (empty($updates)) {
            sendError(400, "No fields to update");
            return;
        }
        
        $updates[] = "updated_at = NOW()";
        $params[] = $key_id;
        
        $query = "UPDATE api_keys SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        sendResponse(200, null, "API key updated successfully");
        
    } catch (Exception $e) {
        error_log("Error updating API key: " . $e->getMessage());
        sendError(500, "Failed to update API key");
    }
}

/**
 * Delete an API key
 */
function deleteApiKey($db, $key_id) {
    try {
        $stmt = $db->prepare("DELETE FROM api_keys WHERE id = ?");
        $stmt->execute([$key_id]);
        
        if ($stmt->rowCount() === 0) {
            sendError(404, "API key not found");
            return;
        }
        
        sendResponse(200, null, "API key deleted successfully");
        
    } catch (Exception $e) {
        error_log("Error deleting API key: " . $e->getMessage());
        sendError(500, "Failed to delete API key");
    }
}
?>
