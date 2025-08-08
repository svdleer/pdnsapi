<?php
// Determine the correct base path
$base_path = realpath(__DIR__ . '/..');

require_once $base_path . '/config/config.php';
require_once $base_path . '/models/Template.php';

// CRITICAL: Enforce authentication for direct API file access
enforceHTTPS();
addSecurityHeaders();
requireApiKey(); // This will exit with 401/403 if auth fails

// Log successful authenticated request
logApiRequest('templates', $_SERVER['REQUEST_METHOD'], 200);

// Initialize Template model (local database implementation)
// Since PowerDNS Admin API doesn't support templates, we use our local implementation
$template_model = new Template();

// Get the HTTP method
$request_method = $_SERVER["REQUEST_METHOD"];

// Get parameters from URL
$template_id = isset($_GET['id']) ? intval($_GET['id']) : null;
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
            getTemplate($template_model, $template_id);
        } else {
            getAllTemplates($template_model);
        }
        break;
        
    case 'POST':
        if ($action === 'create-domain' && $template_id) {
            createDomainFromTemplate($template_model, $template_id, $json_data);
        } else {
            createTemplate($template_model, $json_data);
        }
        break;
        
    case 'PUT':
        if ($template_id) {
            updateTemplate($template_model, $template_id, $json_data);
        } else {
            sendError(400, "Template ID required for update");
        }
        break;
        
    case 'DELETE':
        if ($template_id) {
            deleteTemplate($template_model, $template_id);
        } else {
            sendError(400, "Template ID required for deletion");
        }
        break;
        
    default:
        sendError(405, "Method not allowed");
        break;
}

function getAllTemplates($template_model) {
    $templates = $template_model->getAllTemplates();
    
    if ($templates !== false) {
        sendResponse(200, $templates, "Templates retrieved successfully");
    } else {
        sendError(500, "Failed to fetch templates");
    }
}

function getTemplate($template_model, $template_id) {
    $template = $template_model->getTemplate($template_id);
    
    if ($template !== false) {
        if ($template) {
            sendResponse(200, $template, "Template retrieved successfully");
        } else {
            sendError(404, "Template not found");
        }
    } else {
        sendError(500, "Failed to fetch template");
    }
}

function createTemplate($template_model, $data) {
    if (!$data || !isset($data['name']) || !isset($data['records'])) {
        sendError(400, "Template name and records are required");
        return;
    }
    
    $template = $template_model->createTemplate($data);
    
    if ($template !== false) {
        sendResponse(201, $template, "Template created successfully");
    } else {
        sendError(500, "Failed to create template");
    }
}

function updateTemplate($template_model, $template_id, $data) {
    if (!$data) {
        sendError(400, "Update data is required");
        return;
    }
    
    $template = $template_model->updateTemplate($template_id, $data);
    
    if ($template !== false) {
        sendResponse(200, $template, "Template updated successfully");
    } else {
        sendError(404, "Template not found or update failed");
    }
}

function deleteTemplate($template_model, $template_id) {
    $result = $template_model->deleteTemplate($template_id);
    
    if ($result) {
        sendResponse(200, null, "Template deleted successfully");
    } else {
        sendError(404, "Template not found or delete failed");
    }
}

function createDomainFromTemplate($template_model, $template_id, $data) {
    if (!$data || !isset($data['name'])) {
        sendError(400, "Domain name is required");
        return;
    }
    
    $result = $template_model->createDomainFromTemplate($template_id, $data);
    
    if ($result && $result['success']) {
        sendResponse(201, $result['data'], $result['message']);
    } else {
        $error_msg = $result['message'] ?? 'Failed to create domain from template';
        sendError(500, $error_msg);
    }
}
?>
