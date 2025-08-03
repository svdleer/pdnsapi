<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Enforce HTTPS if configured
enforceHTTPS();

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

// Enforce API key authentication (will check exempt endpoints internally)
requireApiKey();

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
        // Redirect to Swagger UI documentation page
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $path = $_SERVER['REQUEST_URI'];
        
        // Build redirect URL to docs
        $base_url = $protocol . '://' . $host;
        $current_path = parse_url($path, PHP_URL_PATH);
        $current_path = rtrim($current_path, '/');
        
        // If we're in a subdirectory like /php-api, preserve it
        if (strpos($current_path, '/php-api') !== false) {
            $redirect_url = $base_url . $current_path . '/docs';
        } else {
            $redirect_url = $base_url . $current_path . '/docs';
        }
        
        header('Location: ' . $redirect_url, true, 302);
        exit;
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
