<?php
// Determine the correct base path
$base_path = realpath(__DIR__ . '/..');

require_once $base_path . '/config/config.php';
require_once $base_path . '/classes/PDNSAdminClient.php';

// API key is already validated in index.php, log the request
logApiRequest('templates', $_SERVER['REQUEST_METHOD'], 200);

// Initialize PDNSAdmin client
$pdns_client = new PDNSAdminClient($pdns_config);

// Get the HTTP method
$request_method = $_SERVER["REQUEST_METHOD"];

// Get parameters from URL
$template_id = isset($_GET['id']) ? $_GET['id'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : null;

// For GET, POST, PUT, DELETE - check for JSON payload
$json_data = null;
$input = file_get_contents("php://input");
if (!empty($input)) {
    $json_data = json_decode($input, true);
}

switch($request_method) {
    case 'GET':
        if ($template_id) {
            getTemplate($pdns_client, $template_id);
        } else {
            getAllTemplates($pdns_client);
        }
        break;
        
    case 'POST':
        if ($action === 'create-domain' && $template_id) {
            createDomainFromTemplate($pdns_client, $template_id, $json_data);
        } else {
            createTemplate($pdns_client, $json_data);
        }
        break;
        
    case 'PUT':
        if ($template_id) {
            updateTemplate($pdns_client, $template_id, $json_data);
        } else {
            sendError(400, "Template ID required for update");
        }
        break;
        
    case 'DELETE':
        if ($template_id) {
            deleteTemplate($pdns_client, $template_id);
        } else {
            sendError(400, "Template ID required for deletion");
        }
        break;
        
    default:
        sendError(405, "Method not allowed");
        break;
}

function getAllTemplates($pdns_client) {
    $response = $pdns_client->getAllTemplates();
    
    if ($response['status_code'] == 200) {
        sendResponse(200, $response['data'], "Templates retrieved successfully");
    } else {
        $error_msg = isset($response['data']['message']) ? $response['data']['message'] : 'Unknown error';
        sendError($response['status_code'], "Failed to fetch templates: " . $error_msg);
    }
}

function getTemplate($pdns_client, $template_id) {
    $response = $pdns_client->getTemplate($template_id);
    
    if ($response['status_code'] == 200) {
        sendResponse(200, $response['data'], "Template retrieved successfully");
    } else {
        $error_msg = isset($response['data']['message']) ? $response['data']['message'] : 'Template not found';
        sendError($response['status_code'], $error_msg);
    }
}

function createTemplate($pdns_client, $data) {
    if (!$data || !isset($data['name'])) {
        sendError(400, "Template name is required");
        return;
    }
    
    $response = $pdns_client->createTemplate($data);
    
    if ($response['status_code'] == 201 || $response['status_code'] == 200) {
        sendResponse(201, $response['data'], "Template created successfully");
    } else {
        $error_msg = isset($response['data']['message']) ? $response['data']['message'] : 'Failed to create template';
        sendError($response['status_code'], $error_msg);
    }
}

function updateTemplate($pdns_client, $template_id, $data) {
    if (!$data) {
        sendError(400, "Update data is required");
        return;
    }
    
    $response = $pdns_client->updateTemplate($template_id, $data);
    
    if ($response['status_code'] == 200 || $response['status_code'] == 204) {
        sendResponse(200, null, "Template updated successfully");
    } else {
        $error_msg = isset($response['data']['message']) ? $response['data']['message'] : 'Failed to update template';
        sendError($response['status_code'], $error_msg);
    }
}

function deleteTemplate($pdns_client, $template_id) {
    $response = $pdns_client->deleteTemplate($template_id);
    
    if ($response['status_code'] == 200 || $response['status_code'] == 204) {
        sendResponse(200, null, "Template deleted successfully");
    } else {
        $error_msg = isset($response['data']['message']) ? $response['data']['message'] : 'Failed to delete template';
        sendError($response['status_code'], $error_msg);
    }
}

function createDomainFromTemplate($pdns_client, $template_id, $data) {
    if (!$data || !isset($data['name'])) {
        sendError(400, "Domain name is required");
        return;
    }
    
    $response = $pdns_client->createDomainFromTemplate($template_id, $data);
    
    if ($response['status_code'] == 201 || $response['status_code'] == 200) {
        sendResponse(201, $response['data'], "Domain created from template successfully");
    } else {
        $error_msg = isset($response['data']['message']) ? $response['data']['message'] : 'Failed to create domain from template';
        sendError($response['status_code'], $error_msg);
    }
}
?>
