<?php
/**
 * Internationalized OpenAPI Specification Generator
 * 
 * Loads the complete openapi.yaml and translates it to different languages
 * Supports English (en) and Dutch (nl) translations
 */

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Load environment and translations with error handling
try {
    if (file_exists(__DIR__ . '/includes/env-loader.php')) {
        require_once __DIR__ . '/includes/env-loader.php';
    }
    
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

// If English requested, serve the original YAML as JSON
if ($lang === 'en') {
    $yamlFile = __DIR__ . '/openapi.yaml';
    if (file_exists($yamlFile)) {
        // Parse YAML and convert to JSON
        if (function_exists('yaml_parse_file')) {
            $openapi = yaml_parse_file($yamlFile);
        } else {
            // Simple YAML to JSON conversion (fallback)
            $yamlContent = file_get_contents($yamlFile);
            // This is a basic conversion - in production, use a proper YAML parser
            $jsonContent = convertYamlToJson($yamlContent);
            $openapi = json_decode($jsonContent, true);
        }
        
        if ($openapi) {
            echo json_encode($openapi, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }
}

// Get translations for Dutch
$translations = getTranslations($lang);

// For Dutch, use the manual translation system (complete coverage)
// For English, could load YAML in future, but for now use manual system for consistency

// Manual comprehensive OpenAPI specification with complete Dutch translations

/**
 * Simple YAML to JSON converter (basic implementation)
 * In production, use a proper YAML parser library
 */
function convertYamlToJson($yamlContent) {
    // This is a very basic converter - use symfony/yaml or similar in production
    // For now, return the original as we'll rely on the manual translation below
    return json_encode([]);
}

/**
 * Translate OpenAPI specification recursively
 */
function translateOpenAPI($data, $translations) {
    if (is_array($data)) {
        $result = [];
        foreach ($data as $key => $value) {
            $newKey = translateKey($key, $translations);
            $result[$newKey] = translateOpenAPI($value, $translations);
        }
        return $result;
    } elseif (is_string($data)) {
        return translateString($data, $translations);
    }
    return $data;
}

/**
 * Translate specific keys
 */
function translateKey($key, $translations) {
    $keyMap = [
        'title' => $translations['api_title'] ?? $key,
        'description' => $translations['api_description'] ?? $key,
        'summary' => $key, // Will be handled in translateString
        'tags' => $key,
    ];
    return $keyMap[$key] ?? $key;
}

/**
 * Translate specific strings based on context
 */
function translateString($string, $translations) {
    // Direct translation mapping
    $stringMap = [
        // Tags
        'System' => $translations['tag_system'] ?? $string,
        'Accounts' => $translations['tag_accounts'] ?? $string,
        'Domains' => $translations['tag_domains'] ?? $string,
        'Templates' => $translations['tag_templates'] ?? $string,
        'Domain Assignments' => $translations['tag_assignments'] ?? $string,
        'Documentation' => $translations['tag_documentation'] ?? $string,
        'IP Allowlist' => $translations['tag_ip_allowlist'] ?? $string,
        
        // Summaries and descriptions - will be handled by the manual system below
    ];
    
    return $stringMap[$string] ?? $string;
}

// Manual translation system for complete coverage (temporary until YAML parser is added)
// This ensures all endpoints are included with proper Dutch translations

$openapi = [
    'openapi' => '3.0.3',
    'info' => [
        'title' => $translations['api_title'],
        'description' => $translations['api_description'],
        'version' => '1.1.0',
        'contact' => [
            'name' => 'API Support - Silvester van der Leer',
            'email' => 'silvester@avant.nl'
        ],
        'license' => [
            'name' => 'MIT',
            'url' => 'https://opensource.org/licenses/MIT'
        ]
    ],
    'servers' => [
        [
            'url' => 'https://pdnsapi.avant.nl/',
            'description' => $translations['server_description']
        ]
    ],
    'security' => [
        ['AdminApiKey' => []]
    ],
    'tags' => [
        [
            'name' => $translations['tag_documentation'],
            'description' => $translations['tag_documentation_description']
        ],
        [
            'name' => $translations['tag_accounts'],
            'description' => $translations['tag_accounts_description']
        ],
        [
            'name' => $translations['tag_domains'],
            'description' => $translations['tag_domains_description']
        ],
        [
            'name' => $translations['tag_templates'],
            'description' => $translations['tag_templates_description']
        ],
        [
            'name' => $translations['tag_assignments'],
            'description' => $translations['tag_assignments_description']
        ],
        [
            'name' => $translations['tag_system'],
            'description' => $translations['tag_system_description']
        ],
        [
            'name' => $translations['tag_ip_allowlist'],
            'description' => $translations['tag_ip_allowlist_description']
        ]
    ],
    'paths' => [
        '/' => [
            'get' => [
                'summary' => $translations['documentation_summary'],
                'description' => $translations['documentation_description'],
                'tags' => [$translations['tag_documentation']],
                'security' => [],
                'responses' => [
                    '200' => [
                        'description' => $translations['response_success'],
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'message' => ['type' => 'string'],
                                        'endpoints' => ['type' => 'array', 'items' => ['type' => 'string']]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        '/accounts' => [
            'get' => [
                'summary' => $translations['accounts_list_summary'],
                'description' => $translations['accounts_list_description'],
                'tags' => [$translations['tag_accounts']],
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'query',
                        'schema' => ['type' => 'integer'],
                        'description' => 'Filter by account ID'
                    ],
                    [
                        'name' => 'username',
                        'in' => 'query',
                        'schema' => ['type' => 'string'],
                        'description' => 'Filter by username'
                    ],
                    [
                        'name' => 'email',
                        'in' => 'query',
                        'schema' => ['type' => 'string'],
                        'description' => 'Filter by email address'
                    ]
                ],
                'responses' => [
                    '200' => ['description' => $translations['response_success']],
                    '401' => ['description' => $translations['response_unauthorized']],
                    '404' => ['description' => $translations['response_not_found']]
                ]
            ],
            'post' => [
                'summary' => $translations['accounts_create_summary'],
                'description' => $translations['accounts_create_description'],
                'tags' => [$translations['tag_accounts']],
                'responses' => [
                    '201' => ['description' => $translations['response_created']],
                    '400' => ['description' => $translations['response_bad_request']],
                    '401' => ['description' => $translations['response_unauthorized']]
                ]
            ],
            'put' => [
                'summary' => $translations['accounts_update_summary'],
                'description' => $translations['accounts_update_description'],
                'tags' => [$translations['tag_accounts']],
                'responses' => [
                    '200' => ['description' => $translations['response_updated']],
                    '400' => ['description' => $translations['response_bad_request']],
                    '401' => ['description' => $translations['response_unauthorized']],
                    '404' => ['description' => $translations['response_not_found']]
                ]
            ],
            'delete' => [
                'summary' => $translations['accounts_delete_summary'],
                'description' => $translations['accounts_delete_description'],
                'tags' => [$translations['tag_accounts']],
                'responses' => [
                    '200' => ['description' => $translations['response_deleted']],
                    '401' => ['description' => $translations['response_unauthorized']],
                    '404' => ['description' => $translations['response_not_found']]
                ]
            ]
        ],
        '/domains' => [
            'get' => [
                'summary' => $translations['domains_list_summary'],
                'description' => $translations['domains_list_description'],
                'tags' => [$translations['tag_domains']],
                'parameters' => [
                    [
                        'name' => 'q',
                        'in' => 'query',
                        'schema' => ['type' => 'string'],
                        'description' => 'Smart search by ID, name, or pattern'
                    ],
                    [
                        'name' => 'account_id',
                        'in' => 'query',
                        'schema' => ['type' => 'integer'],
                        'description' => 'Filter by account ID'
                    ]
                ],
                'responses' => [
                    '200' => ['description' => $translations['response_success']],
                    '401' => ['description' => $translations['response_unauthorized']]
                ]
            ],
            'post' => [
                'summary' => $translations['domains_create_summary'],
                'description' => $translations['domains_create_description'],
                'tags' => [$translations['tag_domains']],
                'responses' => [
                    '201' => ['description' => $translations['response_created']],
                    '400' => ['description' => $translations['response_bad_request']],
                    '401' => ['description' => $translations['response_unauthorized']]
                ]
            ],
            'put' => [
                'summary' => $translations['domains_update_summary'],
                'description' => $translations['domains_update_description'],
                'tags' => [$translations['tag_domains']],
                'responses' => [
                    '200' => ['description' => $translations['response_updated']],
                    '400' => ['description' => $translations['response_bad_request']],
                    '401' => ['description' => $translations['response_unauthorized']],
                    '404' => ['description' => $translations['response_not_found']]
                ]
            ],
            'delete' => [
                'summary' => $translations['domains_delete_summary'],
                'description' => $translations['domains_delete_description'],
                'tags' => [$translations['tag_domains']],
                'responses' => [
                    '200' => ['description' => $translations['response_deleted']],
                    '401' => ['description' => $translations['response_unauthorized']],
                    '404' => ['description' => $translations['response_not_found']]
                ]
            ]
        ],
        '/domains/{id}' => [
            'get' => [
                'summary' => $translations['domains_get_summary'],
                'description' => $translations['domains_get_description'],
                'tags' => [$translations['tag_domains']],
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'integer'],
                        'description' => 'Domain ID'
                    ]
                ],
                'responses' => [
                    '200' => ['description' => $translations['response_success']],
                    '401' => ['description' => $translations['response_unauthorized']],
                    '404' => ['description' => $translations['response_not_found']]
                ]
            ],
            'put' => [
                'summary' => $translations['domains_update_summary'],
                'description' => $translations['domains_update_description'],
                'tags' => [$translations['tag_domains']],
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'integer'],
                        'description' => 'Domain ID'
                    ]
                ],
                'responses' => [
                    '200' => ['description' => $translations['response_updated']],
                    '400' => ['description' => $translations['response_bad_request']],
                    '401' => ['description' => $translations['response_unauthorized']],
                    '404' => ['description' => $translations['response_not_found']]
                ]
            ],
            'delete' => [
                'summary' => $translations['domains_delete_summary'],
                'description' => $translations['domains_delete_description'],
                'tags' => [$translations['tag_domains']],
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'integer'],
                        'description' => 'Domain ID'
                    ]
                ],
                'responses' => [
                    '200' => ['description' => $translations['response_deleted']],
                    '401' => ['description' => $translations['response_unauthorized']],
                    '404' => ['description' => $translations['response_not_found']]
                ]
            ]
        ],
        '/templates' => [
            'get' => [
                'summary' => $translations['templates_list_summary'],
                'description' => $translations['templates_list_description'],
                'tags' => [$translations['tag_templates']],
                'responses' => [
                    '200' => ['description' => $translations['response_success']],
                    '401' => ['description' => $translations['response_unauthorized']]
                ]
            ],
            'post' => [
                'summary' => $translations['templates_create_summary'],
                'description' => $translations['templates_create_description'],
                'tags' => [$translations['tag_templates']],
                'responses' => [
                    '201' => ['description' => $translations['response_created']],
                    '400' => ['description' => $translations['response_bad_request']],
                    '401' => ['description' => $translations['response_unauthorized']]
                ]
            ]
        ],
        '/templates/{id}' => [
            'get' => [
                'summary' => $translations['templates_get_summary'],
                'description' => $translations['templates_get_description'],
                'tags' => [$translations['tag_templates']],
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'integer'],
                        'description' => 'Template ID'
                    ]
                ],
                'responses' => [
                    '200' => ['description' => $translations['response_success']],
                    '401' => ['description' => $translations['response_unauthorized']],
                    '404' => ['description' => $translations['response_not_found']]
                ]
            ],
            'put' => [
                'summary' => $translations['templates_update_summary'],
                'description' => $translations['templates_update_description'],
                'tags' => [$translations['tag_templates']],
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'integer'],
                        'description' => 'Template ID'
                    ]
                ],
                'responses' => [
                    '200' => ['description' => $translations['response_updated']],
                    '400' => ['description' => $translations['response_bad_request']],
                    '401' => ['description' => $translations['response_unauthorized']],
                    '404' => ['description' => $translations['response_not_found']]
                ]
            ],
            'delete' => [
                'summary' => $translations['templates_delete_summary'],
                'description' => $translations['templates_delete_description'],
                'tags' => [$translations['tag_templates']],
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'integer'],
                        'description' => 'Template ID'
                    ]
                ],
                'responses' => [
                    '200' => ['description' => $translations['response_deleted']],
                    '401' => ['description' => $translations['response_unauthorized']],
                    '404' => ['description' => $translations['response_not_found']]
                ]
            ]
        ],
        '/templates/{id}/create-domain' => [
            'post' => [
                'summary' => $translations['templates_create_domain_summary'],
                'description' => $translations['templates_create_domain_description'],
                'tags' => [$translations['tag_templates']],
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'integer'],
                        'description' => 'Template ID'
                    ]
                ],
                'responses' => [
                    '201' => ['description' => $translations['response_created']],
                    '400' => ['description' => $translations['response_bad_request']],
                    '401' => ['description' => $translations['response_unauthorized']],
                    '404' => ['description' => $translations['response_not_found']]
                ]
            ]
        ],
        '/domain-account' => [
            'get' => [
                'summary' => $translations['assignments_list_summary'],
                'description' => $translations['assignments_list_description'],
                'tags' => [$translations['tag_assignments']],
                'responses' => [
                    '200' => ['description' => $translations['response_success']],
                    '401' => ['description' => $translations['response_unauthorized']]
                ]
            ],
            'post' => [
                'summary' => $translations['assignments_create_summary'],
                'description' => $translations['assignments_create_description'],
                'tags' => [$translations['tag_assignments']],
                'responses' => [
                    '201' => ['description' => $translations['response_created']],
                    '400' => ['description' => $translations['response_bad_request']],
                    '401' => ['description' => $translations['response_unauthorized']]
                ]
            ],
            'delete' => [
                'summary' => $translations['assignments_delete_summary'],
                'description' => $translations['assignments_delete_description'],
                'tags' => [$translations['tag_assignments']],
                'responses' => [
                    '200' => ['description' => $translations['response_deleted']],
                    '401' => ['description' => $translations['response_unauthorized']],
                    '404' => ['description' => $translations['response_not_found']]
                ]
            ]
        ],
        '/status' => [
            'get' => [
                'summary' => $translations['status_summary'],
                'description' => $translations['status_description'],
                'tags' => [$translations['tag_system']],
                'security' => [],
                'responses' => [
                    '200' => [
                        'description' => $translations['response_success'],
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'status' => [
                                            'type' => 'string',
                                            'description' => $translations['status_field']
                                        ],
                                        'database' => [
                                            'type' => 'string',
                                            'description' => $translations['database_field']
                                        ],
                                        'timestamp' => [
                                            'type' => 'string',
                                            'format' => 'date-time',
                                            'description' => $translations['timestamp_field']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        '/ip-allowlist' => [
            'get' => [
                'summary' => $translations['ip_allowlist_list_summary'],
                'description' => $translations['ip_allowlist_list_description'],
                'tags' => [$translations['tag_ip_allowlist']],
                'responses' => [
                    '200' => ['description' => $translations['response_success']],
                    '401' => ['description' => $translations['response_unauthorized']]
                ]
            ],
            'post' => [
                'summary' => $translations['ip_allowlist_create_summary'],
                'description' => $translations['ip_allowlist_create_description'],
                'tags' => [$translations['tag_ip_allowlist']],
                'responses' => [
                    '201' => ['description' => $translations['response_created']],
                    '400' => ['description' => $translations['response_bad_request']],
                    '401' => ['description' => $translations['response_unauthorized']]
                ]
            ]
        ],
        '/ip-allowlist/{id}' => [
            'get' => [
                'summary' => $translations['ip_allowlist_get_summary'],
                'description' => $translations['ip_allowlist_get_description'],
                'tags' => [$translations['tag_ip_allowlist']],
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'integer'],
                        'description' => 'IP allowlist entry ID'
                    ]
                ],
                'responses' => [
                    '200' => ['description' => $translations['response_success']],
                    '401' => ['description' => $translations['response_unauthorized']],
                    '404' => ['description' => $translations['response_not_found']]
                ]
            ],
            'put' => [
                'summary' => $translations['ip_allowlist_update_summary'],
                'description' => $translations['ip_allowlist_update_description'],
                'tags' => [$translations['tag_ip_allowlist']],
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'integer'],
                        'description' => 'IP allowlist entry ID'
                    ]
                ],
                'responses' => [
                    '200' => ['description' => $translations['response_updated']],
                    '400' => ['description' => $translations['response_bad_request']],
                    '401' => ['description' => $translations['response_unauthorized']],
                    '404' => ['description' => $translations['response_not_found']]
                ]
            ],
            'delete' => [
                'summary' => $translations['ip_allowlist_delete_summary'],
                'description' => $translations['ip_allowlist_delete_description'],
                'tags' => [$translations['tag_ip_allowlist']],
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'integer'],
                        'description' => 'IP allowlist entry ID'
                    ]
                ],
                'responses' => [
                    '200' => ['description' => $translations['response_deleted']],
                    '401' => ['description' => $translations['response_unauthorized']],
                    '404' => ['description' => $translations['response_not_found']]
                ]
            ]
        ],
        '/ip-allowlist/test' => [
            'post' => [
                'summary' => $translations['ip_allowlist_test_summary'],
                'description' => $translations['ip_allowlist_test_description'],
                'tags' => [$translations['tag_ip_allowlist']],
                'responses' => [
                    '200' => ['description' => $translations['response_success']],
                    '401' => ['description' => $translations['response_unauthorized']],
                    '403' => ['description' => $translations['response_forbidden']]
                ]
            ]
        ]
    ],
    'components' => [
        'securitySchemes' => [
            'AdminApiKey' => [
                'type' => 'apiKey',
                'in' => 'header',
                'name' => 'X-API-Key',
                'description' => $translations['api_key_description']
            ]
        ]
    ]
];

echo json_encode($openapi, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>

// Generate OpenAPI specification with translations
$openapi = [
    'openapi' => '3.0.0',
    'info' => [
        'title' => $translations['api_title'],
        'description' => $translations['api_description'],
        'version' => '1.1.0',
        'contact' => [
            'name' => 'API Support - Silvester van der Leer',
            'email' => 'silvester@avant.nl'
        ],
        'license' => [
            'name' => 'MIT',
            'url' => 'https://opensource.org/licenses/MIT'
        ]
    ],
    'servers' => [
        [
            'url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
                     '://' . $_SERVER['HTTP_HOST'] . '/',
            'description' => $translations['server_description']
        ]
    ],
    'paths' => [
        '/api/status' => [
            'get' => [
                'tags' => [$translations['tag_system']],
                'summary' => $translations['status_summary'],
                'description' => $translations['status_description'],
                'responses' => [
                    '200' => [
                        'description' => $translations['response_success'],
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'status' => [
                                            'type' => 'string',
                                            'description' => $translations['status_field']
                                        ],
                                        'database' => [
                                            'type' => 'string',
                                            'description' => $translations['database_field']
                                        ],
                                        'timestamp' => [
                                            'type' => 'string',
                                            'format' => 'date-time',
                                            'description' => $translations['timestamp_field']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        '/api/accounts' => [
            'get' => [
                'tags' => [$translations['tag_accounts']],
                'summary' => $translations['accounts_list_summary'],
                'description' => $translations['accounts_list_description'],
                'parameters' => [
                    [
                        'name' => 'page',
                        'in' => 'query',
                        'description' => $translations['page_param'],
                        'schema' => ['type' => 'integer', 'default' => 1]
                    ],
                    [
                        'name' => 'limit',
                        'in' => 'query',
                        'description' => $translations['limit_param'],
                        'schema' => ['type' => 'integer', 'default' => 50]
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => $translations['response_success'],
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'accounts' => [
                                            'type' => 'array',
                                            'items' => ['$ref' => '#/components/schemas/Account']
                                        ],
                                        'pagination' => ['$ref' => '#/components/schemas/Pagination']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'post' => [
                'tags' => [$translations['tag_accounts']],
                'summary' => $translations['accounts_create_summary'],
                'description' => $translations['accounts_create_description'],
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => ['$ref' => '#/components/schemas/AccountCreate']
                        ]
                    ]
                ],
                'responses' => [
                    '201' => [
                        'description' => $translations['response_created'],
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/Account']
                            ]
                        ]
                    ]
                ]
            ]
        ],
        '/api/domains' => [
            'get' => [
                'tags' => [$translations['tag_domains']],
                'summary' => $translations['domains_list_summary'],
                'description' => $translations['domains_list_description'],
                'responses' => [
                    '200' => [
                        'description' => $translations['response_success'],
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'domains' => [
                                            'type' => 'array',
                                            'items' => ['$ref' => '#/components/schemas/Domain']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'post' => [
                'tags' => [$translations['tag_domains']],
                'summary' => $translations['domains_create_summary'],
                'description' => $translations['domains_create_description'],
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => ['$ref' => '#/components/schemas/DomainCreate']
                        ]
                    ]
                ],
                'responses' => [
                    '201' => [
                        'description' => $translations['response_created'],
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/Domain']
                            ]
                        ]
                    ]
                ]
            ]
        ],
        '/api/domain-assignments' => [
            'get' => [
                'tags' => [$translations['tag_assignments']],
                'summary' => $translations['assignments_list_summary'],
                'description' => $translations['assignments_list_description'],
                'responses' => [
                    '200' => [
                        'description' => $translations['response_success'],
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'assignments' => [
                                            'type' => 'array',
                                            'items' => ['$ref' => '#/components/schemas/DomainAssignment']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'post' => [
                'tags' => [$translations['tag_assignments']],
                'summary' => $translations['assignments_create_summary'],
                'description' => $translations['assignments_create_description'],
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => ['$ref' => '#/components/schemas/DomainAssignmentCreate']
                        ]
                    ]
                ],
                'responses' => [
                    '201' => [
                        'description' => $translations['response_created'],
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/DomainAssignment']
                            ]
                        ]
                    ]
                ]
            ]
        ],
        '/api/ip-allowlist' => [
            'get' => [
                'tags' => [$translations['tag_system']],
                'summary' => ($lang === 'nl') ? 'IP Allowlist Weergeven' : 'List IP Allowlist',
                'description' => ($lang === 'nl') ? 
                    'Alle IP adressen in de globale allowlist ophalen. ' . 
                    (isset($translations['security_requirements_title']) ? $translations['security_requirements_title'] : '') : 
                    'Retrieve all IP addresses in the global allowlist.',
                'responses' => [
                    '200' => [
                        'description' => $translations['response_success'],
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'success' => ['type' => 'boolean'],
                                        'data' => [
                                            'type' => 'array',
                                            'items' => ['$ref' => '#/components/schemas/IPAllowlistEntry']
                                        ],
                                        'count' => ['type' => 'integer']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '401' => ['description' => $translations['response_unauthorized']],
                    '500' => ['description' => $translations['response_server_error']]
                ]
            ],
            'post' => [
                'tags' => [$translations['tag_system']],
                'summary' => ($lang === 'nl') ? 'IP Toevoegen aan Allowlist' : 'Add IP to Allowlist',
                'description' => ($lang === 'nl') ? 
                    'Een nieuw IP adres of CIDR bereik toevoegen aan de globale allowlist. ' . 
                    (isset($translations['ip_security_note']) ? $translations['ip_security_note'] : '') : 
                    'Add a new IP address or CIDR range to the global allowlist.',
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['ip_address'],
                                'properties' => [
                                    'ip_address' => ['type' => 'string', 'description' => ($lang === 'nl') ? 'IP adres of CIDR bereik' : 'IP address or CIDR range'],
                                    'description' => ['type' => 'string', 'description' => ($lang === 'nl') ? 'Beschrijving voor dit IP item' : 'Description for this IP entry'],
                                    'enabled' => ['type' => 'boolean', 'description' => ($lang === 'nl') ? 'Of dit IP ingeschakeld moet zijn' : 'Whether this IP should be enabled']
                                ]
                            ]
                        ]
                    ]
                ],
                'responses' => [
                    '201' => ['description' => $translations['response_created']],
                    '400' => ['description' => $translations['response_bad_request']],
                    '401' => ['description' => $translations['response_unauthorized']],
                    '409' => ['description' => $translations['response_conflict']],
                    '500' => ['description' => $translations['response_server_error']]
                ]
            ]
        ]
    ],
    'components' => [
        'schemas' => [
            'Account' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer', 'description' => $translations['account_id']],
                    'name' => ['type' => 'string', 'description' => $translations['account_name']],
                    'description' => ['type' => 'string', 'description' => $translations['account_description']],
                    'contact' => ['type' => 'string', 'description' => $translations['account_contact']],
                    'mail' => ['type' => 'string', 'description' => $translations['account_email']],
                    'ip_addresses' => ['type' => 'string', 'description' => $translations['account_ips']],
                    'created_at' => ['type' => 'string', 'format' => 'date-time', 'description' => $translations['created_at']],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time', 'description' => $translations['updated_at']]
                ]
            ],
            'AccountCreate' => [
                'type' => 'object',
                'required' => ['name'],
                'properties' => [
                    'name' => ['type' => 'string', 'description' => $translations['account_name']],
                    'description' => ['type' => 'string', 'description' => $translations['account_description']],
                    'contact' => ['type' => 'string', 'description' => $translations['account_contact']],
                    'mail' => ['type' => 'string', 'description' => $translations['account_email']],
                    'ip_addresses' => ['type' => 'string', 'description' => $translations['account_ips']]
                ]
            ],
            'Domain' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer', 'description' => $translations['domain_id']],
                    'name' => ['type' => 'string', 'description' => $translations['domain_name']],
                    'type' => ['type' => 'string', 'description' => $translations['domain_type']],
                    'account_id' => ['type' => 'integer', 'description' => $translations['domain_account_id']],
                    'created_at' => ['type' => 'string', 'format' => 'date-time', 'description' => $translations['created_at']],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time', 'description' => $translations['updated_at']]
                ]
            ],
            'DomainCreate' => [
                'type' => 'object',
                'required' => ['name', 'type'],
                'properties' => [
                    'name' => ['type' => 'string', 'description' => $translations['domain_name']],
                    'type' => ['type' => 'string', 'description' => $translations['domain_type']],
                    'account_id' => ['type' => 'integer', 'description' => $translations['domain_account_id']]
                ]
            ],
            'DomainAssignment' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer', 'description' => $translations['assignment_id']],
                    'domain_id' => ['type' => 'integer', 'description' => $translations['assignment_domain_id']],
                    'account_id' => ['type' => 'integer', 'description' => $translations['assignment_account_id']],
                    'domain_name' => ['type' => 'string', 'description' => $translations['domain_name']],
                    'account_name' => ['type' => 'string', 'description' => $translations['account_name']],
                    'created_at' => ['type' => 'string', 'format' => 'date-time', 'description' => $translations['created_at']]
                ]
            ],
            'DomainAssignmentCreate' => [
                'type' => 'object',
                'required' => ['domain_id', 'account_id'],
                'properties' => [
                    'domain_id' => ['type' => 'integer', 'description' => $translations['assignment_domain_id']],
                    'account_id' => ['type' => 'integer', 'description' => $translations['assignment_account_id']]
                ]
            ],
            'Pagination' => [
                'type' => 'object',
                'properties' => [
                    'current_page' => ['type' => 'integer', 'description' => $translations['current_page']],
                    'per_page' => ['type' => 'integer', 'description' => $translations['per_page']],
                    'total' => ['type' => 'integer', 'description' => $translations['total_items']],
                    'total_pages' => ['type' => 'integer', 'description' => $translations['total_pages']]
                ]
            ],
            'IPAllowlistEntry' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer', 'description' => ($lang === 'nl') ? 'Allowlist item ID' : 'Allowlist entry ID'],
                    'ip_address' => ['type' => 'string', 'description' => ($lang === 'nl') ? 'IP adres of CIDR bereik' : 'IP address or CIDR range'],
                    'description' => ['type' => 'string', 'description' => ($lang === 'nl') ? 'Beschrijving van dit IP item' : 'Description of this IP entry'],
                    'enabled' => ['type' => 'boolean', 'description' => ($lang === 'nl') ? 'Of dit IP momenteel ingeschakeld is' : 'Whether this IP is currently enabled'],
                    'created_at' => ['type' => 'string', 'format' => 'date-time', 'description' => ($lang === 'nl') ? 'Wanneer dit item werd aangemaakt' : 'When this entry was created'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time', 'description' => ($lang === 'nl') ? 'Wanneer dit item laatst werd bijgewerkt' : 'When this entry was last updated']
                ]
            ]
        ],
        'securitySchemes' => [
            'AdminApiKey' => [
                'type' => 'apiKey',
                'in' => 'header',
                'name' => 'X-API-Key',
                'description' => $translations['api_key_description'] . "\n\n" .
                    (isset($translations['authentication_note']) ? $translations['authentication_note'] . "\n\n" : '') .
                    (isset($translations['security_warnings']) ? $translations['security_warnings'] . "\n\n" : '') .
                    (isset($translations['cli_management_title']) ? $translations['cli_management_title'] . "\n" : '') .
                    (isset($translations['cli_examples']) ? $translations['cli_examples'] . "\n\n" : '') .
                    (isset($translations['database_storage_title']) ? $translations['database_storage_title'] . "\n" : '') .
                    (isset($translations['database_example']) ? $translations['database_example'] . "\n\n" : '') .
                    (isset($translations['security_benefits_title']) ? $translations['security_benefits_title'] . "\n" : '') .
                    (isset($translations['security_benefits']) ? $translations['security_benefits'] . "\n\n" : '') .
                    (isset($translations['security_notes_title']) ? $translations['security_notes_title'] . "\n" : '') .
                    (isset($translations['security_notes']) ? $translations['security_notes'] : '')
            ]
        ]
    ],
    'security' => [
        ['AdminApiKey' => []]
    ],
    'tags' => [
        ['name' => $translations['tag_system'], 'description' => $translations['tag_system_description']],
        ['name' => $translations['tag_accounts'], 'description' => $translations['tag_accounts_description']],
        ['name' => $translations['tag_domains'], 'description' => $translations['tag_domains_description']],
        ['name' => $translations['tag_assignments'], 'description' => $translations['tag_assignments_description']]
    ]
];

// Output the OpenAPI specification as JSON
echo json_encode($openapi, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
