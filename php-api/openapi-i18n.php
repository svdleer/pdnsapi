<?php
/**
 * Complete Internationalized OpenAPI Specification Generator
 * 
 * Generates complete Dutch/English OpenAPI/Swagger documentation
 * Based on the full English openapi.json with proper translations
 */

// Set CORS headers to allow browser access
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight OPTIONS requests
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load translations
try {
    if (file_exists(__DIR__ . '/translations.php')) {
        require_once __DIR__ . '/translations.php';
    } else {
        throw new Exception('translations.php not found');
    }
    
    if (!function_exists('getTranslations')) {
        throw new Exception('getTranslations function not defined');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Configuration error: ' . $e->getMessage()]);
    exit;
}

// Get requested language, default to English
$lang = $_GET['lang'] ?? 'en';
if (!in_array($lang, ['en', 'nl'])) {
    $lang = 'en';
}

// If English is requested, just return the original openapi.json content
if ($lang === 'en') {
    $englishSpec = file_get_contents(__DIR__ . '/openapi.json');
    if ($englishSpec === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Could not load English OpenAPI specification']);
        exit;
    }
    echo $englishSpec;
    exit;
}

// For Dutch, load the English spec and translate it
$englishSpec = file_get_contents(__DIR__ . '/openapi.json');
if ($englishSpec === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not load base OpenAPI specification']);
    exit;
}

$openapi = json_decode($englishSpec, true);
if ($openapi === null) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not parse base OpenAPI specification']);
    exit;
}

// Get translations
$translations = getTranslations($lang);

// Translate the main info section
$openapi['info']['title'] = $translations['api_title'];
$openapi['info']['description'] = $translations['api_description'];

// Translate server description
if (isset($openapi['servers'][0]['description'])) {
    $openapi['servers'][0]['description'] = $translations['server_description'];
}

// Translate all tags
$tagTranslationMap = [
    'Documentation' => $translations['tag_documentation'],
    'Accounts' => $translations['tag_accounts'], 
    'Domains' => $translations['tag_domains'],
    'Domain Search' => $translations['tag_domains'],
    'Domain Management' => $translations['tag_domains'],
    'Templates' => $translations['tag_templates'],
    'Domain-Account' => $translations['tag_assignments'],
    'Status' => $translations['tag_system'],
    'IP Allowlist' => $translations['tag_ip_allowlist'],
    'Security' => $translations['tag_ip_allowlist'],
    'Testing' => $translations['tag_system']
];

// Update tags section
if (isset($openapi['tags'])) {
    foreach ($openapi['tags'] as &$tag) {
        if (isset($tagTranslationMap[$tag['name']])) {
            $tag['name'] = $tagTranslationMap[$tag['name']];
        }
        
        // Translate tag descriptions
        switch ($tag['name']) {
            case $translations['tag_documentation']:
                $tag['description'] = $translations['tag_documentation_description'];
                break;
            case $translations['tag_accounts']:
                $tag['description'] = $translations['tag_accounts_description'];
                break;
            case $translations['tag_domains']:
                $tag['description'] = $translations['tag_domains_description'];
                break;
            case $translations['tag_templates']:
                $tag['description'] = $translations['tag_templates_description'];
                break;
            case $translations['tag_assignments']:
                $tag['description'] = $translations['tag_assignments_description'];
                break;
            case $translations['tag_system']:
                $tag['description'] = $translations['tag_system_description'];
                break;
            case $translations['tag_ip_allowlist']:
                $tag['description'] = $translations['tag_ip_allowlist_description'];
                break;
        }
    }
}

// Translate all path operations
$pathTranslationMap = [
    // Documentation
    'API Documentation' => $translations['documentation_summary'],
    'Returns API documentation and available endpoints' => $translations['documentation_description'],
    
    // Accounts
    'Get accounts with smart filtering' => $translations['accounts_list_summary'],
    'Create a new account' => $translations['accounts_create_summary'],
    'Update account' => $translations['accounts_update_summary'],
    'Delete account' => $translations['accounts_delete_summary'],
    
    // Domains
    'Get domains with intelligent search' => $translations['domains_list_summary'],
    'Add domain to account' => $translations['domains_create_summary'],
    'Update domain by ID or name' => $translations['domains_update_summary'],
    'Delete domain by ID or name' => $translations['domains_delete_summary'],
    'Get individual domain by ID' => $translations['domains_get_summary'],
    'Update domain by ID (path parameter)' => $translations['domains_update_summary'],
    'Delete domain by ID' => $translations['domains_delete_summary'],
    
    // Templates
    'Get all domain templates' => $translations['templates_list_summary'],
    'Create new domain template' => $translations['templates_create_summary'],
    'Get template by ID' => $translations['templates_get_summary'],
    'Update template' => $translations['templates_update_summary'],
    'Delete template' => $translations['templates_delete_summary'],
    'Create domain from template' => $translations['templates_create_domain_summary'],
    
    // Domain-Account
    'Domain-Account operations' => $translations['assignments_create_summary'],
    
    // Status
    'API status and health check' => $translations['status_summary'],
    
    // IP Allowlist
    'List IP allowlist entries' => $translations['ip_allowlist_list_summary'],
    'Add IP to allowlist' => $translations['ip_allowlist_create_summary'],
    'Update IP allowlist entry' => $translations['ip_allowlist_update_summary'],
    'Remove IP from allowlist' => $translations['ip_allowlist_delete_summary'],
    'Test IP allowlist access' => $translations['ip_allowlist_test_summary']
];

// Response translations
$responseTranslationMap = [
    'Success' => $translations['response_success'],
    'Created successfully' => $translations['response_created'],
    'Updated successfully' => $translations['response_updated'],
    'Deleted successfully' => $translations['response_deleted'],
    'Bad Request' => $translations['response_bad_request'],
    'Unauthorized' => $translations['response_unauthorized'],
    'Not Found' => $translations['response_not_found'],
    'Internal Server Error' => $translations['response_server_error']
];

// Function to recursively translate strings in the OpenAPI structure
function translateStrings(&$data, $translations, $pathMap, $responseMap) {
    if (is_array($data)) {
        foreach ($data as $key => &$value) {
            if ($key === 'summary' && isset($pathMap[$value])) {
                $value = $pathMap[$value];
            } elseif ($key === 'description') {
                if (isset($responseMap[$value])) {
                    $value = $responseMap[$value];
                } elseif (isset($pathMap[$value])) {
                    $value = $pathMap[$value];
                }
            } elseif (is_array($value) && isset($value[0]) && isset($pathMap[$value[0]])) {
                // Handle tags arrays
                $value[0] = $pathMap[$value[0]] ?? $value[0];
            } else {
                translateStrings($value, $translations, $pathMap, $responseMap);
            }
        }
    }
}

// Apply translations to all paths
if (isset($openapi['paths'])) {
    translateStrings($openapi['paths'], $translations, $pathTranslationMap, $responseTranslationMap);
    
    // Fix tags in paths to use translated tag names
    foreach ($openapi['paths'] as &$path) {
        foreach ($path as &$operation) {
            if (isset($operation['tags'])) {
                foreach ($operation['tags'] as &$tag) {
                    $tag = $tagTranslationMap[$tag] ?? $tag;
                }
            }
        }
    }
}

// Translate security scheme descriptions
if (isset($openapi['components']['securitySchemes']['AdminApiKey']['description'])) {
    $openapi['components']['securitySchemes']['AdminApiKey']['description'] = $translations['api_key_description'];
}

echo json_encode($openapi, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
