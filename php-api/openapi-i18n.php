<?php
/**
 * Complete Internationalized OpenAPI Specification Generator
 * 
 * Generates fully interactive Dutch/English OpenAPI/Swagger documentation
 * with all schemas, parameters, examples, and request bodies
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

// Base OpenAPI specification with complete schemas and parameters
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
                            'text/html' => [
                                'schema' => [
                                    'type' => 'string',
                                    'example' => '<!DOCTYPE html><html>...</html>'
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
                        'description' => $lang === 'nl' ? 'Filteren op account ID (combineerbaar met andere filters voor validatie)' : 'Filter by account ID (can combine with other filters for validation)',
                        'example' => 14
                    ],
                    [
                        'name' => 'username',
                        'in' => 'query',
                        'schema' => ['type' => 'string'],
                        'description' => $lang === 'nl' ? 'Filteren op gebruikersnaam (combineerbaar met andere filters voor validatie)' : 'Filter by username (can combine with other filters for validation)',
                        'example' => 'admin'
                    ],
                    [
                        'name' => 'firstname',
                        'in' => 'query',
                        'schema' => ['type' => 'string'],
                        'description' => $lang === 'nl' ? 'Filteren op voornaam' : 'Filter by first name',
                        'example' => 'John'
                    ],
                    [
                        'name' => 'lastname',
                        'in' => 'query',
                        'schema' => ['type' => 'string'],
                        'description' => $lang === 'nl' ? 'Filteren op achternaam' : 'Filter by last name',
                        'example' => 'Doe'
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => $translations['response_success'],
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'array',
                                    'items' => ['$ref' => '#/components/schemas/Account']
                                ],
                                'example' => [
                                    [
                                        'id' => 14,
                                        'username' => 'admin',
                                        'firstname' => 'John',
                                        'lastname' => 'Doe',
                                        'email' => 'admin@example.com',
                                        'role' => 'Administrator',
                                        'ip_addresses' => ['192.168.1.100', '10.0.0.5']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '401' => [
                        'description' => $translations['response_unauthorized'],
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/Error'],
                                'example' => ['error' => 'API key required']
                            ]
                        ]
                    ]
                ]
            ],
            'post' => [
                'summary' => $translations['accounts_create_summary'],
                'description' => $translations['accounts_create_description'],
                'tags' => [$translations['tag_accounts']],
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => ['$ref' => '#/components/schemas/AccountCreate'],
                            'example' => [
                                'username' => 'newuser',
                                'firstname' => 'Jane',
                                'lastname' => 'Smith',
                                'email' => 'jane@example.com',
                                'password' => 'securepassword123',
                                'role' => 'User',
                                'ip_addresses' => ['192.168.1.200']
                            ]
                        ]
                    ]
                ],
                'responses' => [
                    '201' => [
                        'description' => $translations['response_created'],
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/Account'],
                                'example' => [
                                    'id' => 25,
                                    'username' => 'newuser',
                                    'firstname' => 'Jane',
                                    'lastname' => 'Smith',
                                    'email' => 'jane@example.com',
                                    'role' => 'User',
                                    'created_at' => '2025-08-12T12:00:00Z'
                                ]
                            ]
                        ]
                    ],
                    '400' => [
                        'description' => $translations['response_bad_request'],
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/Error'],
                                'example' => ['error' => 'Username already exists']
                            ]
                        ]
                    ],
                    '401' => [
                        'description' => $translations['response_unauthorized']
                    ]
                ]
            ],
            'put' => [
                'summary' => $translations['accounts_update_summary'],
                'description' => $translations['accounts_update_description'],
                'tags' => [$translations['tag_accounts']],
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => ['$ref' => '#/components/schemas/AccountUpdate'],
                            'example' => [
                                'id' => 14,
                                'firstname' => 'Updated John',
                                'email' => 'newemail@example.com',
                                'ip_addresses' => ['192.168.1.100', '10.0.0.10']
                            ]
                        ]
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => $translations['response_updated'],
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/Account']
                            ]
                        ]
                    ],
                    '401' => ['description' => $translations['response_unauthorized']],
                    '404' => ['description' => $translations['response_not_found']]
                ]
            ],
            'delete' => [
                'summary' => $translations['accounts_delete_summary'],
                'description' => $translations['accounts_delete_description'],
                'tags' => [$translations['tag_accounts']],
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => ['$ref' => '#/components/schemas/AccountIdentifier'],
                            'examples' => [
                                'delete_by_id' => [
                                    'summary' => $lang === 'nl' ? 'Verwijderen op ID' : 'Delete by ID',
                                    'value' => ['id' => 94]
                                ],
                                'delete_by_username' => [
                                    'summary' => $lang === 'nl' ? 'Verwijderen op gebruikersnaam' : 'Delete by username',
                                    'value' => ['username' => 'johndoe']
                                ]
                            ]
                        ]
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => $translations['response_deleted'],
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/SuccessMessage']
                            ]
                        ]
                    ],
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
                        'description' => $lang === 'nl' ? 'Zoekterm - detecteert automatisch zoektype (ID/naam/patroon/bevat)' : 'Search query - auto-detects search type (ID/name/pattern/contains)',
                        'example' => 'example.com'
                    ],
                    [
                        'name' => 'account_id',
                        'in' => 'query',
                        'schema' => ['type' => 'integer'],
                        'description' => $lang === 'nl' ? 'Filteren op account ID' : 'Filter by account ID',
                        'example' => 5
                    ],
                    [
                        'name' => 'type',
                        'in' => 'query',
                        'schema' => ['type' => 'string', 'enum' => ['Master', 'Slave', 'Native']],
                        'description' => $lang === 'nl' ? 'Filteren op domeintype' : 'Filter by domain type',
                        'example' => 'Master'
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => $translations['response_success'],
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'array',
                                    'items' => ['$ref' => '#/components/schemas/Domain']
                                ],
                                'example' => [
                                    [
                                        'id' => 123,
                                        'name' => 'example.com',
                                        'type' => 'Master',
                                        'account_id' => 5,
                                        'created_at' => '2025-08-01T10:00:00Z'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '401' => ['description' => $translations['response_unauthorized']]
                ]
            ],
            'post' => [
                'summary' => $translations['domains_create_summary'],
                'description' => $translations['domains_create_description'],
                'tags' => [$translations['tag_domains']],
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['domain_name', 'account_id'],
                                'properties' => [
                                    'domain_name' => [
                                        'type' => 'string',
                                        'description' => $lang === 'nl' ? 'Domeinnaam om toe te wijzen' : 'Domain name to assign',
                                        'example' => 'newdomain.com'
                                    ],
                                    'account_id' => [
                                        'type' => 'integer',
                                        'description' => $lang === 'nl' ? 'Doel account ID' : 'Target account ID',
                                        'example' => 5
                                    ]
                                ]
                            ]
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
                    ],
                    '400' => ['description' => $translations['response_bad_request']],
                    '401' => ['description' => $translations['response_unauthorized']]
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
                                'schema' => ['$ref' => '#/components/schemas/ApiStatus'],
                                'example' => [
                                    'status' => 'operational',
                                    'version' => '1.1.0',
                                    'timestamp' => '2025-08-12T12:00:00Z',
                                    'database_status' => 'connected',
                                    'pdns_admin_status' => 'connected'
                                ]
                            ]
                        ]
                    ]
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
        ],
        'schemas' => [
            'Account' => [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => 'integer',
                        'description' => $lang === 'nl' ? 'Unieke account ID' : 'Unique account ID',
                        'example' => 14
                    ],
                    'username' => [
                        'type' => 'string',
                        'description' => $lang === 'nl' ? 'Gebruikersnaam' : 'Username',
                        'example' => 'admin'
                    ],
                    'firstname' => [
                        'type' => 'string',
                        'description' => $lang === 'nl' ? 'Voornaam' : 'First name',
                        'example' => 'John'
                    ],
                    'lastname' => [
                        'type' => 'string',
                        'description' => $lang === 'nl' ? 'Achternaam' : 'Last name',
                        'example' => 'Doe'
                    ],
                    'email' => [
                        'type' => 'string',
                        'format' => 'email',
                        'description' => $lang === 'nl' ? 'E-mailadres' : 'Email address',
                        'example' => 'admin@example.com'
                    ],
                    'role' => [
                        'type' => 'string',
                        'description' => $lang === 'nl' ? 'Gebruikersrol' : 'User role',
                        'example' => 'Administrator'
                    ],
                    'ip_addresses' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                        'description' => $lang === 'nl' ? 'Toegestane IP-adressen' : 'Allowed IP addresses',
                        'example' => ['192.168.1.100', '10.0.0.5']
                    ]
                ]
            ],
            'AccountCreate' => [
                'type' => 'object',
                'required' => ['username', 'email', 'password'],
                'properties' => [
                    'username' => [
                        'type' => 'string',
                        'description' => $lang === 'nl' ? 'Unieke gebruikersnaam' : 'Unique username',
                        'example' => 'newuser'
                    ],
                    'firstname' => [
                        'type' => 'string',
                        'description' => $lang === 'nl' ? 'Voornaam' : 'First name',
                        'example' => 'Jane'
                    ],
                    'lastname' => [
                        'type' => 'string',
                        'description' => $lang === 'nl' ? 'Achternaam' : 'Last name',
                        'example' => 'Smith'
                    ],
                    'email' => [
                        'type' => 'string',
                        'format' => 'email',
                        'description' => $lang === 'nl' ? 'E-mailadres' : 'Email address',
                        'example' => 'jane@example.com'
                    ],
                    'password' => [
                        'type' => 'string',
                        'description' => $lang === 'nl' ? 'Wachtwoord' : 'Password',
                        'example' => 'securepassword123'
                    ],
                    'role' => [
                        'type' => 'string',
                        'description' => $lang === 'nl' ? 'Gebruikersrol' : 'User role',
                        'example' => 'User'
                    ],
                    'ip_addresses' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                        'description' => $lang === 'nl' ? 'Toegestane IP-adressen' : 'Allowed IP addresses',
                        'example' => ['192.168.1.200']
                    ]
                ]
            ],
            'AccountUpdate' => [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => 'integer',
                        'description' => $lang === 'nl' ? 'Account ID om bij te werken' : 'Account ID to update',
                        'example' => 14
                    ],
                    'username' => [
                        'type' => 'string',
                        'description' => $lang === 'nl' ? 'Alternatief: gebruikersnaam om bij te werken' : 'Alternative: username to update',
                        'example' => 'admin'
                    ],
                    'firstname' => [
                        'type' => 'string',
                        'description' => $lang === 'nl' ? 'Nieuwe voornaam' : 'New first name',
                        'example' => 'Updated John'
                    ],
                    'lastname' => [
                        'type' => 'string',
                        'description' => $lang === 'nl' ? 'Nieuwe achternaam' : 'New last name',
                        'example' => 'Updated Doe'
                    ],
                    'email' => [
                        'type' => 'string',
                        'format' => 'email',
                        'description' => $lang === 'nl' ? 'Nieuw e-mailadres' : 'New email address',
                        'example' => 'newemail@example.com'
                    ],
                    'ip_addresses' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                        'description' => $lang === 'nl' ? 'Bijgewerkte IP-adressen lijst' : 'Updated IP addresses list',
                        'example' => ['192.168.1.100', '10.0.0.10']
                    ]
                ]
            ],
            'AccountIdentifier' => [
                'type' => 'object',
                'description' => $lang === 'nl' ? 'Account identificatie object (óf ID óf gebruikersnaam, niet beide)' : 'Account identifier object (either ID or username, not both)',
                'oneOf' => [
                    [
                        'type' => 'object',
                        'required' => ['id'],
                        'properties' => [
                            'id' => [
                                'type' => 'integer',
                                'description' => $lang === 'nl' ? 'Account ID' : 'Account ID',
                                'example' => 94
                            ]
                        ]
                    ],
                    [
                        'type' => 'object',
                        'required' => ['username'],
                        'properties' => [
                            'username' => [
                                'type' => 'string',
                                'description' => $lang === 'nl' ? 'Gebruikersnaam' : 'Username',
                                'example' => 'johndoe'
                            ]
                        ]
                    ]
                ]
            ],
            'Domain' => [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => 'integer',
                        'description' => $lang === 'nl' ? 'Unieke domein ID' : 'Unique domain ID',
                        'example' => 123
                    ],
                    'name' => [
                        'type' => 'string',
                        'description' => $lang === 'nl' ? 'Domeinnaam' : 'Domain name',
                        'example' => 'example.com'
                    ],
                    'type' => [
                        'type' => 'string',
                        'enum' => ['Master', 'Slave', 'Native'],
                        'description' => $lang === 'nl' ? 'Domeintype' : 'Domain type',
                        'example' => 'Master'
                    ],
                    'account_id' => [
                        'type' => 'integer',
                        'description' => $lang === 'nl' ? 'Account eigenaar ID' : 'Account owner ID',
                        'example' => 5
                    ],
                    'created_at' => [
                        'type' => 'string',
                        'format' => 'date-time',
                        'description' => $lang === 'nl' ? 'Aanmaakdatum' : 'Creation timestamp',
                        'example' => '2025-08-01T10:00:00Z'
                    ]
                ]
            ],
            'ApiStatus' => [
                'type' => 'object',
                'properties' => [
                    'status' => [
                        'type' => 'string',
                        'description' => $lang === 'nl' ? 'API status' : 'API status',
                        'example' => 'operational'
                    ],
                    'version' => [
                        'type' => 'string',
                        'description' => $lang === 'nl' ? 'API versie' : 'API version',
                        'example' => '1.1.0'
                    ],
                    'timestamp' => [
                        'type' => 'string',
                        'format' => 'date-time',
                        'description' => $lang === 'nl' ? 'Status tijdstempel' : 'Status timestamp',
                        'example' => '2025-08-12T12:00:00Z'
                    ],
                    'database_status' => [
                        'type' => 'string',
                        'description' => $lang === 'nl' ? 'Database verbindingsstatus' : 'Database connection status',
                        'example' => 'connected'
                    ],
                    'pdns_admin_status' => [
                        'type' => 'string',
                        'description' => $lang === 'nl' ? 'PowerDNS Admin verbindingsstatus' : 'PowerDNS Admin connection status',
                        'example' => 'connected'
                    ]
                ]
            ],
            'SuccessMessage' => [
                'type' => 'object',
                'properties' => [
                    'message' => [
                        'type' => 'string',
                        'description' => $lang === 'nl' ? 'Succesbericht' : 'Success message',
                        'example' => $lang === 'nl' ? 'Account succesvol verwijderd' : 'Account deleted successfully'
                    ]
                ]
            ],
            'Error' => [
                'type' => 'object',
                'properties' => [
                    'error' => [
                        'type' => 'string',
                        'description' => $lang === 'nl' ? 'Foutbeschrijving' : 'Error description',
                        'example' => $lang === 'nl' ? 'API sleutel vereist' : 'API key required'
                    ]
                ]
            ]
        ]
    ]
];

echo json_encode($openapi, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
