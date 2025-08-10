<?php
/**
 * Internationalized OpenAPI Specification Generator
 * 
 * Generates OpenAPI/Swagger documentation in different languages
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
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
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

// Get translations for the requested language
$translations = getTranslations($lang);

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
