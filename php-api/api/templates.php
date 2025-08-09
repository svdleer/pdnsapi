<?php
// Determine the correct base path
$base_path = realpath(__DIR__ . '/..');

require_once $base_path . '/config/config.php';
require_once $base_path . '/models/PowerDNSAdminTemplate.php';

// CRITICAL: Enforce authentication for direct API file access
enforceHTTPS();
addSecurityHeaders();
requireApiKey(); // This will exit with 401/403 if auth fails

// Log successful authenticated request
logApiRequest('templates', $_SERVER['REQUEST_METHOD'], 200);

// Initialize PowerDNS Admin Template model (uses PowerDNS Admin's existing template tables)
// PowerDNS Admin IS the source of truth for templates
$template_model = new PowerDNSAdminTemplate();

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
            sendError(400, "Templates should be managed through PowerDNS Admin web interface. Use POST /templates/{id}/create-domain to create domains from templates.");
        }
        break;
        
    case 'PUT':
        sendError(400, "Templates should be managed through PowerDNS Admin web interface. This API provides read-only access to PowerDNS Admin templates.");
        break;
        
    case 'DELETE':
        sendError(400, "Templates should be managed through PowerDNS Admin web interface. This API provides read-only access to PowerDNS Admin templates.");
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

function createDomainFromTemplate($template_model, $template_id, $data) {
    if (!$data || !isset($data['name'])) {
        sendError(400, "Domain name is required");
        return;
    }
    
    $result = $template_model->createDomainFromTemplate($template_id, $data);
    
    if ($result && $result['success']) {
        sendResponse(201, $result['data'], $result['message']);
    } else {
        $error_msg = $result['message'] ?? 'Failed to create domain from PowerDNS Admin template';
        sendError(500, $error_msg);
    }
}
?>
