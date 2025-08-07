<?php
/**
 * Templates API Endpoint
 * 
 * Provides access to PowerDNS Admin domain templates for domain creation
 * 
 * Endpoints:
 * - GET /templates           - List all available templates
 * - GET /templates?id=X      - Get specific template by ID with records
 * - GET /templates?name=X    - Get specific template by name with records
 * 
 * @author PowerDNS API Development Team
 * @version 1.0.0
 */

// Determine the correct base path
$base_path = realpath(__DIR__ . '/..');

// Load required dependencies
require_once $base_path . '/config/config.php';
require_once $base_path . '/config/database.php';
require_once $base_path . '/includes/database-compat.php';

// API key is already validated in index.php, log the request
logApiRequest('templates', $_SERVER['REQUEST_METHOD'], 200);

// Get PowerDNS Admin database connection
$pdns_admin_conn = null;
if (class_exists('PDNSAdminDatabase')) {
    $pdns_admin_db = new PDNSAdminDatabase();
    $pdns_admin_conn = $pdns_admin_db->getConnection();
}

// Get the HTTP method and parameters
$request_method = $_SERVER["REQUEST_METHOD"];
$template_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$template_name = isset($_GET['name']) ? trim($_GET['name']) : null;

// Route the request
switch($request_method) {
    case 'GET':
        if ($template_id) {
            // Get specific template by ID
            getTemplateById($template_id);
        } elseif ($template_name) {
            // Get specific template by name
            getTemplateByName($template_name);
        } else {
            // Get all templates
            getAllTemplates();
        }
        break;
        
    default:
        sendError(405, "Method not allowed");
        break;
}

/**
 * Get all available templates
 * 
 * @return void Sends JSON response
 */
function getAllTemplates() {
    global $pdns_admin_conn;
    
    if (!$pdns_admin_conn) {
        sendError(500, "Database connection failed");
        return;
    }
    
    try {
        $stmt = $pdns_admin_conn->prepare("
            SELECT id, name, description 
            FROM domain_template 
            ORDER BY name ASC
        ");
        
        $stmt->execute();
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert string IDs to integers for consistency
        foreach ($templates as &$template) {
            $template['id'] = intval($template['id']);
        }
        
        sendResponse(200, $templates, "Templates retrieved successfully");
        
    } catch (PDOException $e) {
        error_log("Error getting templates: " . $e->getMessage());
        sendError(500, "Failed to retrieve templates");
    }
}

/**
 * Get template by ID with all its records
 * 
 * @param int $template_id The template ID
 * @return void Sends JSON response
 */
function getTemplateById($template_id) {
    global $pdns_admin_conn;
    
    if (!$pdns_admin_conn) {
        sendError(500, "Database connection failed");
        return;
    }
    
    if (!$template_id || $template_id <= 0) {
        sendError(400, "Invalid template ID");
        return;
    }
    
    try {
        // Get template info
        $stmt = $pdns_admin_conn->prepare("
            SELECT id, name, description 
            FROM domain_template 
            WHERE id = ?
        ");
        
        $stmt->execute([$template_id]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$template) {
            sendError(404, "Template not found");
            return;
        }
        
        // Convert ID to integer
        $template['id'] = intval($template['id']);
        
        // Get template records
        $stmt = $pdns_admin_conn->prepare("
            SELECT 
                name as record_name,
                type,
                data as content,
                ttl,
                comment
            FROM domain_template_record 
            WHERE template_id = ? AND status = 1 
            ORDER BY name ASC, type ASC
        ");
        
        $stmt->execute([$template_id]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process records for consistent formatting
        foreach ($records as &$record) {
            // Ensure TTL is an integer
            $record['ttl'] = intval($record['ttl']);
            
            // Clean up record name (replace @ with empty for root domain)
            if ($record['record_name'] === '@') {
                $record['name'] = '@';
            } else {
                $record['name'] = $record['record_name'];
            }
            
            // Remove the duplicate record_name field
            unset($record['record_name']);
        }
        
        $template['records'] = $records;
        $template['record_count'] = count($records);
        
        sendResponse(200, $template, "Template retrieved successfully");
        
    } catch (PDOException $e) {
        error_log("Error getting template by ID: " . $e->getMessage());
        sendError(500, "Failed to retrieve template");
    }
}

/**
 * Get template by name with all its records
 * 
 * @param string $template_name The template name
 * @return void Sends JSON response
 */
function getTemplateByName($template_name) {
    global $pdns_admin_conn;
    
    if (!$pdns_admin_conn) {
        sendError(500, "Database connection failed");
        return;
    }
    
    if (empty($template_name)) {
        sendError(400, "Template name is required");
        return;
    }
    
    try {
        // Get template info
        $stmt = $pdns_admin_conn->prepare("
            SELECT id, name, description 
            FROM domain_template 
            WHERE name = ?
        ");
        
        $stmt->execute([$template_name]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$template) {
            sendError(404, "Template not found: " . $template_name);
            return;
        }
        
        // Convert ID to integer
        $template['id'] = intval($template['id']);
        
        // Get template records
        $stmt = $pdns_admin_conn->prepare("
            SELECT 
                name as record_name,
                type,
                data as content,
                ttl,
                comment
            FROM domain_template_record 
            WHERE template_id = ? AND status = 1 
            ORDER BY name ASC, type ASC
        ");
        
        $stmt->execute([$template['id']]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process records for consistent formatting
        foreach ($records as &$record) {
            // Ensure TTL is an integer
            $record['ttl'] = intval($record['ttl']);
            
            // Clean up record name
            if ($record['record_name'] === '@') {
                $record['name'] = '@';
            } else {
                $record['name'] = $record['record_name'];
            }
            
            // Remove the duplicate record_name field
            unset($record['record_name']);
        }
        
        $template['records'] = $records;
        $template['record_count'] = count($records);
        
        sendResponse(200, $template, "Template retrieved successfully");
        
    } catch (PDOException $e) {
        error_log("Error getting template by name: " . $e->getMessage());
        sendError(500, "Failed to retrieve template");
    }
}

?>