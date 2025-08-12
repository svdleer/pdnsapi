<?php
/**
 * Internationalized OpenAPI Specification Generator
 * 
 * Generates localized OpenAPI/Swagger documentation
 * Supports English (default) and Dutch translations
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
    echo json_encode(['error' => 'Could not decode OpenAPI specification']);
    exit;
}

// Get translations
$translations = getTranslations($lang);

// Complete parameter and example translations for Dutch
$parameterTranslations = [
    // Tag/Chapter names - using the exact English names from OpenAPI spec
    'Documentation' => $translations['tag_documentation'] ?? 'Documentatie',
    'Accounts' => $translations['tag_accounts'] ?? 'Accounts', 
    'Domains' => $translations['tag_domains'] ?? 'Domeinen',
    'Domain Search' => 'Domein Zoeken',
    'Domain Management' => 'Domein Beheer', 
    'Templates' => $translations['tag_templates'] ?? 'Sjablonen',
    'Domain-Account' => $translations['tag_assignments'] ?? 'Domein-Account',
    'Status' => $translations['tag_system'] ?? 'Status',
    'IP Allowlist' => $translations['tag_ip_allowlist'] ?? 'IP Toegestane Lijst',
    'Security' => 'Beveiliging',
    'Testing' => 'Testen',
    
    // Tag descriptions - using translations.php values
    'API documentation and information' => $translations['tag_documentation_description'] ?? 'API documentatie en informatie',
    'Account management operations with IP address support' => $translations['tag_accounts_description'] ?? 'Account beheer operaties met IP-adres ondersteuning', 
    'Smart domain management with ID/name detection, DNS records, and PowerDNS Server API integration' => $translations['tag_domains_description'] ?? 'Slimme domein beheer met ID/naam detectie, DNS records, en PowerDNS Server API integratie',
    'Intelligent domain search by ID, name, pattern, or contains matching' => 'Intelligente domein zoeken op ID, naam, patroon, of bevat overeenkomst',
    'Advanced domain operations including updates, deletion and DNS record management' => 'Geavanceerde domein operaties inclusief updates, verwijdering en DNS record beheer',
    'Domain template management operations' => $translations['tag_templates_description'] ?? 'Domein sjabloon beheer operaties',
    'Domain-account relationship management' => $translations['tag_assignments_description'] ?? 'Domein-account relatie beheer', 
    'API status, health checks, and synchronization' => $translations['tag_system_description'] ?? 'API status, health checks, en synchronisatie',
    'IP allowlist management for API security' => $translations['tag_ip_allowlist_description'] ?? 'IP toegestane lijst beheer voor API beveiliging',
    'Security-related operations and access control' => 'Beveiliging-gerelateerde operaties en toegangscontrole',
    'Testing and validation utilities' => 'Test en validatie hulpmiddelen',
    
    // Account parameters
    'Filter by account ID (can combine with other filters for validation)' => 'Filteren op account ID (combineerbaar met andere filters voor validatie)',
    'Filter by username (can combine with ID for validation)' => 'Filteren op gebruikersnaam (combineerbaar met ID voor validatie)', 
    'Filter by email address (can combine with other filters)' => 'Filteren op e-mailadres (combineerbaar met andere filters)',
    'Sync from PowerDNS Admin before filtering' => 'Synchroniseren vanuit PowerDNS Admin voor filteren',
    'Filter by first name' => 'Filteren op voornaam',
    'Filter by last name' => 'Filteren op achternaam',
    
    // Domain parameters  
    'Search query - auto-detects search type (ID/name/pattern/contains)' => 'Zoekterm - detecteert automatisch zoektype (ID/naam/patroon/bevat)',
    'Filter by account ID' => 'Filteren op account ID',
    'Filter by domain type' => 'Filteren op domeintype',
    'Domain name to assign' => 'Domeinnaam om toe te wijzen',
    'Target account ID' => 'Doel account ID',
    
    // Schema descriptions
    'Unique account ID' => 'Unieke account ID',
    'Username' => 'Gebruikersnaam', 
    'First name' => 'Voornaam',
    'Last name' => 'Achternaam',
    'Email address' => 'E-mailadres',
    'User role' => 'Gebruikersrol',
    'Allowed IP addresses' => 'Toegestane IP-adressen',
    'Unique username' => 'Unieke gebruikersnaam',
    'Password' => 'Wachtwoord',
    'Account ID to update' => 'Account ID om bij te werken',
    'Alternative: username to update' => 'Alternatief: gebruikersnaam om bij te werken',
    'New first name' => 'Nieuwe voornaam',
    'New last name' => 'Nieuwe achternaam', 
    'New email address' => 'Nieuw e-mailadres',
    'Updated IP addresses list' => 'Bijgewerkte IP-adressen lijst',
    'Account identification object (either ID or username, not both)' => 'Account identificatie object (óf ID óf gebruikersnaam, niet beide)',
    'Account ID' => 'Account ID',
    'Unique domain ID' => 'Unieke domein ID',
    'Domain name' => 'Domeinnaam',
    'Domain type' => 'Domeintype',
    'Account owner ID' => 'Account eigenaar ID',
    'Creation date' => 'Aanmaakdatum',
    'API status' => 'API status',
    'API version' => 'API versie',
    'Status timestamp' => 'Status tijdstempel',
    'Database connection status' => 'Database verbindingsstatus',
    'PowerDNS Admin connection status' => 'PowerDNS Admin verbindingsstatus',
    'Success message' => 'Succesbericht',
    'Error description' => 'Foutbeschrijving'
];

// Example translations
$exampleTranslations = [
    'admin' => 'beheerder',
    'newuser' => 'nieuwgebruiker', 
    'Administrator' => 'Beheerder',
    'User' => 'Gebruiker',
    'example.com' => 'voorbeeld.nl',
    'newdomain.com' => 'nieuwdomein.nl',
    'operational' => 'operationeel',
    'connected' => 'verbonden',
    'Account deleted successfully' => 'Account succesvol verwijderd',
    'API key required' => 'API sleutel vereist',
    'Username already exists' => 'Gebruikersnaam bestaat al',
    'securepassword123' => 'veiligwachtwoord123',
    'newuser@example.com' => 'nieuwgebruiker@voorbeeld.nl',
    'admin@example.com' => 'beheerder@voorbeeld.nl',
    'Updated John' => 'Bijgewerkte John',
    'Updated Doe' => 'Bijgewerkte Doe', 
    'newemail@example.com' => 'nieuweemail@voorbeeld.nl'
];

// Translate strings recursively
function translateStrings($data, $translations, $parameterTranslations, $exampleTranslations) {
    if (is_array($data)) {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_array($key) || !is_string($key)) {
                // Invalid key type, skip
                continue;
            }
            $result[$key] = translateStrings($value, $translations, $parameterTranslations, $exampleTranslations);
        }
        return $result;
    }
    
    if (is_string($data)) {
        // First check direct translations
        if (isset($translations[$data])) {
            return $translations[$data];
        }
        
        // Then check parameter translations
        if (isset($parameterTranslations[$data])) {
            return $parameterTranslations[$data];
        }
        
        // Then check example translations
        if (isset($exampleTranslations[$data])) {
            return $exampleTranslations[$data];
        }
        
        // Debug: Log untranslated strings to error log for debugging
        if (in_array($data, ['Documentation', 'Accounts', 'Domains', 'Templates', 'Status'])) {
            error_log("DEBUG: Processing tag name: '$data'");
            if (isset($parameterTranslations[$data])) {
                error_log("DEBUG: Translating '$data' to '{$parameterTranslations[$data]}'");
                return $parameterTranslations[$data];
            } else {
                error_log("DEBUG: No translation found for tag: '$data'");
            }
        }
    }
    
    return $data;
}

// Apply translations
$translatedOpenapi = translateStrings($openapi, $translations, $parameterTranslations, $exampleTranslations);

// Ensure tags are properly translated (sometimes the recursive function misses them)
if (isset($translatedOpenapi['tags']) && is_array($translatedOpenapi['tags'])) {
    error_log("DEBUG: Processing " . count($translatedOpenapi['tags']) . " tags for translation");
    foreach ($translatedOpenapi['tags'] as $index => $tag) {
        if (isset($tag['name'])) {
            error_log("DEBUG: Processing tag: " . $tag['name']);
            // Direct mapping of English tag names to Dutch
            $tagTranslations = [
                'Documentation' => 'Documentatie',
                'Accounts' => 'Accounts', 
                'Domains' => 'Domeinen',
                'Domain Search' => 'Domein Zoeken',
                'Domain Management' => 'Domein Beheer',
                'Templates' => 'Sjablonen',
                'Domain-Account' => 'Domein-Account',
                'Status' => 'Status',
                'IP Allowlist' => 'IP Toegestane Lijst',
                'Security' => 'Beveiliging',
                'Testing' => 'Testen'
            ];
            
            if (isset($tagTranslations[$tag['name']])) {
                error_log("DEBUG: Translating tag '{$tag['name']}' to '{$tagTranslations[$tag['name']]}'");
                $translatedOpenapi['tags'][$index]['name'] = $tagTranslations[$tag['name']];
            }
        }
        
        if (isset($tag['description'])) {
            // Direct mapping of tag descriptions to Dutch
            $descTranslations = [
                'API documentation and information' => 'API documentatie en informatie',
                'Account management operations with IP address support' => 'Account beheer operaties met IP-adres ondersteuning',
                'Smart domain management with ID/name detection, DNS records, and PowerDNS Server API integration' => 'Domein beheer met ID/naam detectie, DNS records en PowerDNS Server API integratie',
                'Intelligent domain search by ID, name, pattern, or contains matching' => 'Intelligente domein zoeken op ID, naam, patroon of bevat overeenkomst',
                'Advanced domain operations including updates, deletion and DNS record management' => 'Geavanceerde domein operaties inclusief updates, verwijdering en DNS record beheer',
                'Domain template management operations' => 'Domein sjabloon beheer operaties',
                'Domain-account relationship management' => 'Domein-account relatie beheer',
                'API status, health checks, and synchronization' => 'API status, health checks en synchronisatie',
                'IP allowlist management for API security' => 'IP toegestane lijst beheer voor API beveiliging',
                'Security-related operations and access control' => 'Beveiliging-gerelateerde operaties en toegangscontrole',
                'Testing and validation utilities' => 'Test en validatie hulpmiddelen'
            ];
            
            if (isset($descTranslations[$tag['description']])) {
                $translatedOpenapi['tags'][$index]['description'] = $descTranslations[$tag['description']];
            }
        }
    }
}

// Output the translated specification
echo json_encode($translatedOpenapi, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
