<?php
// Determine the correct base path
$base_path = realpath(__DIR__ . '/..');

require_once $base_path . '/config/config.php';
require_once $base_path . '/config/database.php';
require_once $base_path . '/includes/database-compat.php';
require_once $base_path . '/classes/PDNSAdminClient.php';

// CRITICAL: Enforce authentication for direct API file access
enforceHTTPS();
addSecurityHeaders();
requireApiKey(); // This will exit with 401/403 if auth fails

// Log successful authenticated request
logApiRequest('status', $_SERVER['REQUEST_METHOD'], 200);

// Database class should now be available through compatibility layer
if (!class_exists('Database')) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database compatibility layer failed']);
    exit;
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize PDNSAdmin client
$pdns_client = new PDNSAdminClient($pdns_config);

// Get the HTTP method
$request_method = $_SERVER["REQUEST_METHOD"];

switch($request_method) {
    case 'GET':
        $action = $_GET['action'] ?? '';
        
        switch($action) {
            case 'test_connection':
                testConnection($pdns_client);
                break;
            case 'sync_all':
                syncAllData($pdns_client, $db);
                break;
            case 'health':
                healthCheck($db);
                break;
            default:
                getApiStatus($pdns_client, $db);
                break;
        }
        break;
        
    default:
        sendError(405, "Method not allowed");
        break;
}

function testConnection($pdns_client) {
    $response = $pdns_client->getAllDomains();
    
    if($response['status_code'] == 200) {
        sendResponse(200, [
            'status' => 'connected',
            'pdns_domains_count' => count($response['data'])
        ], "Connection to PDNSAdmin successful");
    } else {
        sendError($response['status_code'], "Failed to connect to PDNSAdmin", $response['data']);
    }
}

function syncAllData($pdns_client, $db) {
    $results = [];
    
    // Sync accounts
    $accounts_response = $pdns_client->getAllAccounts();
    if($accounts_response['status_code'] == 200) {
        $results['accounts'] = [
            'status' => 'success',
            'count' => count($accounts_response['data'])
        ];
    } else {
        $results['accounts'] = [
            'status' => 'failed',
            'error' => $accounts_response['data']['error'] ?? 'Unknown error'
        ];
    }
    
    // Sync domains
    $domains_response = $pdns_client->getAllDomains();
    if($domains_response['status_code'] == 200) {
        $results['domains'] = [
            'status' => 'success',
            'count' => count($domains_response['data'])
        ];
    } else {
        $results['domains'] = [
            'status' => 'failed',
            'error' => $domains_response['data']['error'] ?? 'Unknown error'
        ];
    }
    
    sendResponse(200, $results, "Data synchronization completed");
}

function healthCheck($db) {
    $health = [
        'database' => 'unknown',
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '1.0.0'
    ];
    
    try {
        // Test database connection
        $stmt = $db->query("SELECT 1");
        if($stmt) {
            $health['database'] = 'connected';
        }
    } catch(Exception $e) {
        $health['database'] = 'disconnected';
        $health['database_error'] = $e->getMessage();
    }
    
    // Get counts from database
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM accounts");
        $accounts_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        $health['local_accounts_count'] = $accounts_count;
        
        $stmt = $db->query("SELECT COUNT(*) as count FROM domains");
        $domains_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        $health['local_domains_count'] = $domains_count;
    } catch(Exception $e) {
        $health['count_error'] = $e->getMessage();
    }
    
    sendResponse(200, $health, "Health check completed");
}

function getApiStatus($pdns_client, $db) {
    $status = [
        'api_version' => '1.0.0',
        'timestamp' => date('Y-m-d H:i:s'),
        'endpoints' => [
            'accounts' => '/api/accounts.php',
            'domains' => '/api/domains.php',
            'status' => '/api/status.php'
        ]
    ];
    
    // Test PDNSAdmin connection
    $pdns_test = $pdns_client->getAllDomains();
    $status['pdns_admin_status'] = $pdns_test['status_code'] == 200 ? 'connected' : 'disconnected';
    
    // Test database connection
    try {
        $db->query("SELECT 1");
        $status['database_status'] = 'connected';
    } catch(Exception $e) {
        $status['database_status'] = 'disconnected';
    }
    
    sendResponse(200, $status, "API status retrieved successfully");
}
?>
