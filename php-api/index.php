<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Enforce API key authentication
requireApiKey();

// Simple routing
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = trim($path, '/');

// Remove base path if exists
$base_path = 'php-api';
if (strpos($path, $base_path) === 0) {
    $path = substr($path, strlen($base_path));
    $path = trim($path, '/');
}

// Route to appropriate API endpoint
switch($path) {
    case 'accounts':
    case 'api/accounts':
        require_once 'api/accounts.php';
        break;
        
    case 'domains':
    case 'api/domains':
        require_once 'api/domains.php';
        break;
        
    case 'status':
    case 'api/status':
        require_once 'api/status.php';
        break;
        
    case 'openapi':
    case 'openapi.json':
    case 'swagger.json':
        // Serve OpenAPI JSON specification
        header('Content-Type: application/json');
        readfile('openapi.json');
        break;
        
    case 'openapi.yaml':
    case 'swagger.yaml':
        // Serve OpenAPI YAML specification
        header('Content-Type: application/yaml');
        readfile('openapi.yaml');
        break;
        
    case 'docs':
    case 'swagger':
    case 'swagger-ui':
        // Serve Swagger UI (if you want to add it later)
        serveSwaggerUI();
        break;
        
    case '':
    case 'index':
        // API documentation/welcome page
        $docs = [
            'name' => 'PDNSAdmin PHP API',
            'version' => '1.0.0',
            'description' => 'PHP API wrapper for PDNSAdmin with local database storage',
            'endpoints' => [
                'GET /accounts' => 'Get all accounts',
                'GET /accounts?id={id}' => 'Get specific account by ID',
                'GET /accounts?name={name}' => 'Get specific account by name',
                'POST /accounts' => 'Create new account (with IP addresses)',
                'PUT /accounts?id={id}' => 'Update account (including IP addresses)',
                'DELETE /accounts?id={id}' => 'Delete account',
                
                'GET /domains' => 'Get all domains',
                'GET /domains?id={id}' => 'Get specific domain',
                'GET /domains?account_id={id}' => 'Get domains by account',
                'GET /domains?sync=true' => 'Sync domains from PDNSAdmin',
                'POST /domains' => 'Create new domain (auto-assigns to account)',
                'PUT /domains?id={id}' => 'Update domain (updates account in PDNSAdmin)',
                'POST /domains?action=add_to_account' => 'Add domain to account',
                
                'GET /status' => 'API status and health check',
                'GET /status?action=test_connection' => 'Test PDNSAdmin connection',
                'GET /status?action=sync_all' => 'Sync all data from PDNSAdmin',
                'GET /status?action=health' => 'Detailed health check',
                
                'GET /openapi' => 'OpenAPI 3.0 specification (JSON)',
                'GET /openapi.yaml' => 'OpenAPI 3.0 specification (YAML)',
                'GET /docs' => 'Swagger UI documentation'
            ],
            'setup_instructions' => [
                '1. Import database/schema.sql to create the database schema',
                '2. Update config/database.php with your database credentials',
                '3. Update config/config.php with your PDNSAdmin API details',
                '4. Test the connection using GET /status?action=test_connection',
                '5. Sync initial data using GET /domains?sync=true'
            ]
        ];
        
        sendResponse(200, $docs, 'PDNSAdmin PHP API Documentation');
        break;
        
    default:
        sendError(404, "Endpoint not found", [
            'available_endpoints' => [
                '/accounts', '/domains', '/status', '/openapi', '/docs', '/'
            ]
        ]);
        break;
}

function serveSwaggerUI() {
    // Simple Swagger UI HTML page
    $html = '<!DOCTYPE html>
<html>
<head>
    <title>PDNSAdmin PHP API - Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui.css" />
    <style>
        html { box-sizing: border-box; overflow: -moz-scrollbars-vertical; overflow-y: scroll; }
        *, *:before, *:after { box-sizing: inherit; }
        body { margin:0; background: #fafafa; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: window.location.origin + window.location.pathname.replace("/docs", "") + "/openapi",
                dom_id: "#swagger-ui",
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout"
            });
        };
    </script>
</body>
</html>';
    
    header('Content-Type: text/html');
    echo $html;
}
?>
