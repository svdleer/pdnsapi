<?php
/**
 * Internationalized OpenAPI Specification Generator
 * 
 * Generates complete Dutch OpenAPI/Swagger documentation
 * Supports English (en) and Dutch (nl) translations
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

// Get translations
$translations = getTranslations($lang);

// Generate complete OpenAPI specification
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
                        'description' => $translations['response_success']
                    ]
                ]
            ]
        ],
        '/accounts' => [
            'get' => [
                'summary' => $translations['accounts_list_summary'],
                'description' => $translations['accounts_list_description'],
                'tags' => [$translations['tag_accounts']],
                'responses' => [
                    '200' => ['description' => $translations['response_success']],
                    '401' => ['description' => $translations['response_unauthorized']]
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
                        'schema' => ['type' => 'integer']
                    ]
                ],
                'responses' => [
                    '200' => ['description' => $translations['response_updated']],
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
                        'schema' => ['type' => 'integer']
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
                        'schema' => ['type' => 'integer']
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
                        'schema' => ['type' => 'integer']
                    ]
                ],
                'responses' => [
                    '200' => ['description' => $translations['response_updated']],
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
                        'schema' => ['type' => 'integer']
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
                        'schema' => ['type' => 'integer']
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
                        'description' => $translations['response_success']
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
                        'schema' => ['type' => 'integer']
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
                        'schema' => ['type' => 'integer']
                    ]
                ],
                'responses' => [
                    '200' => ['description' => $translations['response_updated']],
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
                        'schema' => ['type' => 'integer']
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
