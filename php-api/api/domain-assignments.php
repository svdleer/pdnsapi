<?php
// Determine the correct base path
$base_path = realpath(__DIR__ . '/..');

require_once $base_path . '/config/config.php';
require_once $base_path . '/config/database.php';
require_once $base_path . '/includes/database-compat.php';
require_once $base_path . '/models/Domain.php';
require_once $base_path . '/models/Account.php';

// API key is already validated in index.php, log the request
logApiRequest('domain-assignments', $_SERVER['REQUEST_METHOD'], 200);

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

// Initialize objects
$domain = new Domain($db);
$account = new Account($db);

// Get the HTTP method
$request_method = $_SERVER["REQUEST_METHOD"];

// Get parameters from URL
$domain_id = isset($_GET['domain_id']) ? $_GET['domain_id'] : null;
$account_id = isset($_GET['account_id']) ? $_GET['account_id'] : null;

switch($request_method) {
    case 'GET':
        if ($domain_id && $account_id) {
            getAssignment($domain_id, $account_id);
        } elseif ($domain_id) {
            getAssignmentsByDomain($domain_id);
        } elseif ($account_id) {
            getAssignmentsByAccount($account_id);
        } else {
            getAllAssignments();
        }
        break;
        
    case 'POST':
        createAssignment();
        break;
        
    case 'DELETE':
        if ($domain_id && $account_id) {
            deleteAssignment($domain_id, $account_id);
        } else {
            sendError(400, "Both domain_id and account_id are required for deletion");
        }
        break;
        
    default:
        sendError(405, "Method not allowed");
        break;
}

function getAllAssignments() {
    global $db;
    
    $query = "
        SELECT 
            uda.domain_id,
            uda.account_id,
            uda.assigned_at,
            d.name as domain_name,
            d.pdns_zone_id,
            a.name as account_name,
            a.mail as account_email
        FROM user_domain_assignments uda
        JOIN domains d ON uda.domain_id = d.id
        JOIN accounts a ON uda.account_id = a.id
        ORDER BY d.name, a.name
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendResponse(200, $assignments);
}

function getAssignmentsByDomain($domain_id) {
    global $db;
    
    $query = "
        SELECT 
            uda.domain_id,
            uda.account_id,
            uda.assigned_at,
            d.name as domain_name,
            d.pdns_zone_id,
            a.name as account_name,
            a.mail as account_email
        FROM user_domain_assignments uda
        JOIN domains d ON uda.domain_id = d.id
        JOIN accounts a ON uda.account_id = a.id
        WHERE uda.domain_id = ?
        ORDER BY a.name
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $domain_id);
    $stmt->execute();
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendResponse(200, $assignments);
}

function getAssignmentsByAccount($account_id) {
    global $db;
    
    $query = "
        SELECT 
            uda.domain_id,
            uda.account_id,
            uda.assigned_at,
            d.name as domain_name,
            d.pdns_zone_id,
            a.name as account_name,
            a.mail as account_email
        FROM user_domain_assignments uda
        JOIN domains d ON uda.domain_id = d.id
        JOIN accounts a ON uda.account_id = a.id
        WHERE uda.account_id = ?
        ORDER BY d.name
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $account_id);
    $stmt->execute();
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendResponse(200, $assignments);
}

function getAssignment($domain_id, $account_id) {
    global $db;
    
    $query = "
        SELECT 
            uda.domain_id,
            uda.account_id,
            uda.assigned_at,
            d.name as domain_name,
            d.pdns_zone_id,
            a.name as account_name,
            a.mail as account_email
        FROM user_domain_assignments uda
        JOIN domains d ON uda.domain_id = d.id
        JOIN accounts a ON uda.account_id = a.id
        WHERE uda.domain_id = ? AND uda.account_id = ?
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $domain_id);
    $stmt->bindParam(2, $account_id);
    $stmt->execute();
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($assignment) {
        sendResponse(200, $assignment);
    } else {
        sendError(404, "Assignment not found");
    }
}

function createAssignment() {
    global $db;
    
    $data = json_decode(file_get_contents("php://input"));
    
    if (empty($data->domain_id) || empty($data->account_id)) {
        sendError(400, "Both domain_id and account_id are required");
        return;
    }
    
    // Check if domain exists
    $domain_query = "SELECT id, name FROM domains WHERE id = ?";
    $domain_stmt = $db->prepare($domain_query);
    $domain_stmt->bindParam(1, $data->domain_id);
    $domain_stmt->execute();
    $domain = $domain_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$domain) {
        sendError(404, "Domain not found");
        return;
    }
    
    // Check if account exists
    $account_query = "SELECT id, name FROM accounts WHERE id = ?";
    $account_stmt = $db->prepare($account_query);
    $account_stmt->bindParam(1, $data->account_id);
    $account_stmt->execute();
    $account = $account_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$account) {
        sendError(404, "Account not found");
        return;
    }
    
    // Check if assignment already exists
    $check_query = "SELECT 1 FROM user_domain_assignments WHERE domain_id = ? AND account_id = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(1, $data->domain_id);
    $check_stmt->bindParam(2, $data->account_id);
    $check_stmt->execute();
    
    if ($check_stmt->fetch()) {
        sendError(409, "Assignment already exists");
        return;
    }
    
    // Create assignment
    $insert_query = "INSERT INTO user_domain_assignments (domain_id, account_id) VALUES (?, ?)";
    $insert_stmt = $db->prepare($insert_query);
    $insert_stmt->bindParam(1, $data->domain_id);
    $insert_stmt->bindParam(2, $data->account_id);
    
    if ($insert_stmt->execute()) {
        sendResponse(201, [
            'domain_id' => $data->domain_id,
            'account_id' => $data->account_id,
            'domain_name' => $domain['name'],
            'account_name' => $account['name']
        ], "Assignment created successfully");
    } else {
        sendError(503, "Unable to create assignment");
    }
}

function deleteAssignment($domain_id, $account_id) {
    global $db;
    
    // Check if assignment exists
    $check_query = "SELECT 1 FROM user_domain_assignments WHERE domain_id = ? AND account_id = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(1, $domain_id);
    $check_stmt->bindParam(2, $account_id);
    $check_stmt->execute();
    
    if (!$check_stmt->fetch()) {
        sendError(404, "Assignment not found");
        return;
    }
    
    // Delete assignment
    $delete_query = "DELETE FROM user_domain_assignments WHERE domain_id = ? AND account_id = ?";
    $delete_stmt = $db->prepare($delete_query);
    $delete_stmt->bindParam(1, $domain_id);
    $delete_stmt->bindParam(2, $account_id);
    
    if ($delete_stmt->execute()) {
        sendResponse(200, null, "Assignment deleted successfully");
    } else {
        sendError(503, "Unable to delete assignment");
    }
}
?>
