<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Handle OPTIONS requests for CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    global $api_settings;
    if ($api_settings['enable_cors']) {
        header('Access-Control-Allow-Origin: ' . implode(', ', $api_settings['cors_origins']));
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');
        header('Access-Control-Max-Age: 86400');
    }
    http_response_code(200);
    exit;
}

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
    // Swagger UI HTML page with API key authentication enforced
    $html = '<!DOCTYPE html>
<html>
<head>
    <title>PDNSAdmin PHP API - Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui.css" />
    <style>
        html { box-sizing: border-box; overflow: -moz-scrollbars-vertical; overflow-y: scroll; }
        *, *:before, *:after { box-sizing: inherit; }
        body { margin:0; background: #fafafa; }
        .auth-notice {
            background: #f0f8ff;
            border: 1px solid #4CAF50;
            border-radius: 5px;
            padding: 15px;
            margin: 20px;
            color: #2c5530;
        }
        .auth-notice h3 {
            margin-top: 0;
            color: #1976d2;
        }
        .auth-notice code {
            background: #e8f5e8;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="auth-notice">
        <h3>🔐 Authentication Required</h3>
        <p><strong>This API requires authentication to test endpoints.</strong></p>
        <p>To test API endpoints in Swagger:</p>
        <ol>
            <li>Click the <strong>"Authorize"</strong> button (🔒) at the top right</li>
            <li>Enter your API key in the <code>X-API-Key</code> field</li>
            <li>Click <strong>"Authorize"</strong> to save your credentials</li>
            <li>Now you can test all endpoints with proper authentication</li>
        </ol>
        <p><em>Note: The API key provides full administrative access. Keep it secure!</em></p>
    </div>
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
                layout: "StandaloneLayout",
                // Pre-configure API key authentication
                initOAuth: {
                    clientId: "pdns-admin-api",
                    appName: "PDNSAdmin PHP API"
                },
                // Show authorization UI prominently
                persistAuthorization: true,
                // Customize request interceptor to ensure API key is always included
                requestInterceptor: function(request) {
                    // Get API key from Swagger UI authorization
                    const apiKey = ui.getState().getIn(["auth", "authorized", "AdminApiKey", "value"]);
                    if (apiKey) {
                        request.headers["X-API-Key"] = apiKey;
                    }
                    return request;
                }
            });
            
            // Show a prominent notice if no API key is configured
            setTimeout(function() {
                const authButton = document.querySelector(".authorize");
                if (authButton) {
                    authButton.style.backgroundColor = "#ff9800";
                    authButton.style.color = "white";
                    authButton.style.fontWeight = "bold";
                    authButton.style.animation = "pulse 2s infinite";
                }
                
                // Add pulsing animation for the authorize button
                const style = document.createElement("style");
                style.textContent = `
                    @keyframes pulse {
                        0% { box-shadow: 0 0 0 0 rgba(255, 152, 0, 0.7); }
                        70% { box-shadow: 0 0 0 10px rgba(255, 152, 0, 0); }
                        100% { box-shadow: 0 0 0 0 rgba(255, 152, 0, 0); }
                    }
                `;
                document.head.appendChild(style);
            }, 1000);
        };
    </script>
</body>
</html>';
    
    header('Content-Type: text/html');
    echo $html;
}
?>
