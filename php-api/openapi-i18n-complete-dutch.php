<?php
/**
 * Complete Internationalized OpenAPI Specification Generator
 * 
 * Generates complete Dutch/English OpenAPI/Swagger documentation
 * Uses the translation system directly for proper content translation
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

// For Dutch, build the complete specification using translation keys
$translations = getTranslations($lang);

// Build the complete Dutch OpenAPI specification
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
                        'description' => 'Filteren op account ID (combineerbaar met andere filters voor validatie)',
                        'example' => 14
                    ],
                    [
                        'name' => 'username',
                        'in' => 'query',
                        'schema' => ['type' => 'string'],
                        'description' => 'Filteren op gebruikersnaam (combineerbaar met andere filters voor validatie)',
                        'example' => 'admin'
                    ],
                    [
                        'name' => 'firstname',
                        'in' => 'query',
                        'schema' => ['type' => 'string'],
                        'description' => 'Filteren op voornaam',
                        'example' => 'John'
                    ],
                    [
                        'name' => 'lastname',
                        'in' => 'query',
                        'schema' => ['type' => 'string'],
                        'description' => 'Filteren op achternaam',
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
                                'example' => ['error' => 'API sleutel vereist']
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
                                'username' => 'nieuwgebruiker',
                                'firstname' => 'Jane',
                                'lastname' => 'Smith',
                                'email' => 'jane@example.com',
                                'password' => 'veiligwachtwoord123',
                                'role' => 'Gebruiker',
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
                                    'username' => 'nieuwgebruiker',
                                    'firstname' => 'Jane',
                                    'lastname' => 'Smith',
                                    'email' => 'jane@example.com',
                                    'role' => 'Gebruiker',
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
                                'example' => ['error' => 'Gebruikersnaam bestaat al']
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
                                'firstname' => 'Bijgewerkte John',
                                'email' => 'nieuwemail@example.com',
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
                                    'summary' => 'Verwijderen op ID',
                                    'value' => ['id' => 94]
                                ],
                                'delete_by_username' => [
                                    'summary' => 'Verwijderen op gebruikersnaam',
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
                        'description' => 'Zoekterm - detecteert automatisch zoektype (ID/naam/patroon/bevat)',
                        'example' => 'example.com'
                    ],
                    [
                        'name' => 'account_id',
                        'in' => 'query',
                        'schema' => ['type' => 'integer'],
                        'description' => 'Filteren op account ID',
                        'example' => 5
                    ],
                    [
                        'name' => 'type',
                        'in' => 'query',
                        'schema' => ['type' => 'string', 'enum' => ['Master', 'Slave', 'Native']],
                        'description' => 'Filteren op domeintype',
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
                                        'description' => 'Domeinnaam om toe te wijzen',
                                        'example' => 'nieuwdomein.com'
                                    ],
                                    'account_id' => [
                                        'type' => 'integer',
                                        'description' => 'Doel account ID',
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
                                    'status' => 'operationeel',
                                    'version' => '1.1.0',
                                    'timestamp' => '2025-08-12T12:00:00Z',
                                    'database_status' => 'verbonden',
                                    'pdns_admin_status' => 'verbonden'
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
                        'description' => 'Unieke account ID',
                        'example' => 14
                    ],
                    'username' => [
                        'type' => 'string',
                        'description' => 'Gebruikersnaam',
                        'example' => 'admin'
                    ],
                    'firstname' => [
                        'type' => 'string',
                        'description' => 'Voornaam',
                        'example' => 'John'
                    ],
                    'lastname' => [
                        'type' => 'string',
                        'description' => 'Achternaam',
                        'example' => 'Doe'
                    ],
                    'email' => [
                        'type' => 'string',
                        'format' => 'email',
                        'description' => 'E-mailadres',
                        'example' => 'admin@example.com'
                    ],
                    'role' => [
                        'type' => 'string',
                        'description' => 'Gebruikersrol',
                        'example' => 'Administrator'
                    ],
                    'ip_addresses' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                        'description' => 'Toegestane IP-adressen',
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
                        'description' => 'Unieke gebruikersnaam',
                        'example' => 'nieuwgebruiker'
                    ],
                    'firstname' => [
                        'type' => 'string',
                        'description' => 'Voornaam',
                        'example' => 'Jane'
                    ],
                    'lastname' => [
                        'type' => 'string',
                        'description' => 'Achternaam',
                        'example' => 'Smith'
                    ],
                    'email' => [
                        'type' => 'string',
                        'format' => 'email',
                        'description' => 'E-mailadres',
                        'example' => 'jane@example.com'
                    ],
                    'password' => [
                        'type' => 'string',
                        'description' => 'Wachtwoord',
                        'example' => 'veiligwachtwoord123'
                    ],
                    'role' => [
                        'type' => 'string',
                        'description' => 'Gebruikersrol',
                        'example' => 'Gebruiker'
                    ],
                    'ip_addresses' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                        'description' => 'Toegestane IP-adressen',
                        'example' => ['192.168.1.200']
                    ]
                ]
            ],
            'AccountUpdate' => [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => 'integer',
                        'description' => 'Account ID om bij te werken',
                        'example' => 14
                    ],
                    'username' => [
                        'type' => 'string',
                        'description' => 'Alternatief: gebruikersnaam om bij te werken',
                        'example' => 'admin'
                    ],
                    'firstname' => [
                        'type' => 'string',
                        'description' => 'Nieuwe voornaam',
                        'example' => 'Bijgewerkte John'
                    ],
                    'lastname' => [
                        'type' => 'string',
                        'description' => 'Nieuwe achternaam',
                        'example' => 'Bijgewerkte Doe'
                    ],
                    'email' => [
                        'type' => 'string',
                        'format' => 'email',
                        'description' => 'Nieuw e-mailadres',
                        'example' => 'nieuwemail@example.com'
                    ],
                    'ip_addresses' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                        'description' => 'Bijgewerkte IP-adressen lijst',
                        'example' => ['192.168.1.100', '10.0.0.10']
                    ]
                ]
            ],
            'AccountIdentifier' => [
                'type' => 'object',
                'description' => 'Account identificatie object (óf ID óf gebruikersnaam, niet beide)',
                'oneOf' => [
                    [
                        'type' => 'object',
                        'required' => ['id'],
                        'properties' => [
                            'id' => [
                                'type' => 'integer',
                                'description' => 'Account ID',
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
                                'description' => 'Gebruikersnaam',
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
                        'description' => 'Unieke domein ID',
                        'example' => 123
                    ],
                    'name' => [
                        'type' => 'string',
                        'description' => 'Domeinnaam',
                        'example' => 'example.com'
                    ],
                    'type' => [
                        'type' => 'string',
                        'enum' => ['Master', 'Slave', 'Native'],
                        'description' => 'Domeintype',
                        'example' => 'Master'
                    ],
                    'account_id' => [
                        'type' => 'integer',
                        'description' => 'Account eigenaar ID',
                        'example' => 5
                    ],
                    'created_at' => [
                        'type' => 'string',
                        'format' => 'date-time',
                        'description' => 'Aanmaakdatum',
                        'example' => '2025-08-01T10:00:00Z'
                    ]
                ]
            ],
            'ApiStatus' => [
                'type' => 'object',
                'properties' => [
                    'status' => [
                        'type' => 'string',
                        'description' => 'API status',
                        'example' => 'operationeel'
                    ],
                    'version' => [
                        'type' => 'string',
                        'description' => 'API versie',
                        'example' => '1.1.0'
                    ],
                    'timestamp' => [
                        'type' => 'string',
                        'format' => 'date-time',
                        'description' => 'Status tijdstempel',
                        'example' => '2025-08-12T12:00:00Z'
                    ],
                    'database_status' => [
                        'type' => 'string',
                        'description' => 'Database verbindingsstatus',
                        'example' => 'verbonden'
                    ],
                    'pdns_admin_status' => [
                        'type' => 'string',
                        'description' => 'PowerDNS Admin verbindingsstatus',
                        'example' => 'verbonden'
                    ]
                ]
            ],
            'SuccessMessage' => [
                'type' => 'object',
                'properties' => [
                    'message' => [
                        'type' => 'string',
                        'description' => 'Succesbericht',
                        'example' => 'Account succesvol verwijderd'
                    ]
                ]
            ],
            'Error' => [
                'type' => 'object',
                'properties' => [
                    'error' => [
                        'type' => 'string',
                        'description' => 'Foutbeschrijving',
                        'example' => 'API sleutel vereist'
                    ]
                ]
            ]
        ]
    ]
];

echo json_encode($openapi, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
