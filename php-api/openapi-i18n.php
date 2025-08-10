<?php
/**
 * Internationalized OpenAPI Specification Generator
 * 
 * Generates OpenAPI/Swagger documentation in different languages
 * Supports English (en) and Dutch (nl) translations
 */

// Set CORS headers to allow browser access
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load environment and translations
require_once __DIR__ . '/includes/env-loader.php';
require_once __DIR__ . '/translations.php';

// Get requested language, default to English
$lang = $_GET['lang'] ?? 'en';
if (!in_array($lang, ['en', 'nl'])) {
    $lang = 'en';
}

// Get translations for the requested language
$translations = getTranslations($lang);

// Generate OpenAPI specification with translations
$openapi = [
    'openapi' => '3.0.0',
    'info' => [
        'title' => $translations['api_title'],
        'description' => $translations['api_description'],
        'version' => '1.0.0',
        'contact' => [
            'name' => 'API Support',
            'email' => 'support@avant.nl'
        ]
    ],
    'servers' => [
        [
            'url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
                     '://' . $_SERVER['HTTP_HOST'],
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
            ]
        ],
        'securitySchemes' => [
            'ApiKeyAuth' => [
                'type' => 'apiKey',
                'in' => 'header',
                'name' => 'X-API-Key',
                'description' => $translations['api_key_description']
            ]
        ]
    ],
    'security' => [
        ['ApiKeyAuth' => []]
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
