<?php
/**
 * Simple Dutch OpenAPI Translation Generator
 * 
 * Clean approach: Load English spec, apply direct Dutch translations
 */

// Set headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get language parameter
$lang = $_GET['lang'] ?? 'en';

// For English, return original file
if ($lang === 'en') {
    $content = file_get_contents(__DIR__ . '/openapi.json');
    if ($content) {
        echo $content;
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Could not load English spec']);
    }
    exit;
}

// For Dutch, load and translate
$content = file_get_contents(__DIR__ . '/openapi.json');
if (!$content) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not load base spec']);
    exit;
}

$spec = json_decode($content, true);
if (!$spec) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Comprehensive Dutch translations
$translations = [
    // Tag names
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
    'Testing' => 'Testen',
    
    // Tag descriptions
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
    'Testing and validation utilities' => 'Test en validatie hulpmiddelen',
    
    // Endpoint summaries
    'Get accounts with smart filtering' => 'Accounts ophalen met slimme filtering',
    'Create new account' => 'Nieuw account aanmaken',
    'Update account information' => 'Account informatie bijwerken',
    'Delete account' => 'Account verwijderen',
    'Search domains with intelligent detection' => 'Domeinen zoeken met intelligente detectie',
    'Get domain details' => 'Domein details ophalen',
    'Update domain settings' => 'Domein instellingen bijwerken',
    'Delete domain' => 'Domein verwijderen',
    'Get API status and health' => 'API status en gezondheid ophalen',
    'Get domain templates' => 'Domein sjablonen ophalen',
    'Create domain template' => 'Domein sjabloon aanmaken',
    'Update template' => 'Sjabloon bijwerken',
    'Delete template' => 'Sjabloon verwijderen',
    'Create domain from template' => 'Domein aanmaken vanuit sjabloon',
    'Add domain to account' => 'Domein toevoegen aan account',
    'Remove domain from account' => 'Domein verwijderen van account',
    'List account domains' => 'Account domeinen weergeven',
    'Get IP allowlist' => 'IP toegestane lijst ophalen',
    'Add IP to allowlist' => 'IP toevoegen aan toegestane lijst',
    'Update IP allowlist entry' => 'IP toegestane lijst item bijwerken',
    'Remove IP from allowlist' => 'IP verwijderen van toegestane lijst',
    'Test IP allowlist access' => 'IP toegestane lijst toegang testen',
    
    // Parameter descriptions
    'Filter by account ID (can combine with other filters for validation)' => 'Filteren op account ID (combineerbaar met andere filters voor validatie)',
    'Filter by username (can combine with ID for validation)' => 'Filteren op gebruikersnaam (combineerbaar met ID voor validatie)',
    'Filter by email address (can combine with other filters)' => 'Filteren op e-mailadres (combineerbaar met andere filters)',
    'Sync from PowerDNS Admin before filtering' => 'Synchroniseren vanuit PowerDNS Admin voor filteren',
    'Search query - auto-detects search type (ID/name/pattern/contains)' => 'Zoekterm - detecteert automatisch zoektype (ID/naam/patroon/bevat)',
    'Filter by domain type' => 'Filteren op domeintype',
    'Domain ID for operations' => 'Domein ID voor operaties',
    'Template ID for operations' => 'Sjabloon ID voor operaties',
    'IP allowlist entry ID' => 'IP toegestane lijst item ID',
    'IP address to test against allowlist' => 'IP-adres om te testen tegen toegestane lijst',
    
    // Common field descriptions
    'Account ID' => 'Account ID',
    'Username (unique)' => 'Gebruikersnaam (uniek)',
    'First name' => 'Voornaam',
    'Last name' => 'Achternaam',
    'Email address' => 'E-mailadres',
    'Role ID (2 = User, 1 = Admin)' => 'Rol ID (2 = Gebruiker, 1 = Beheerder)',
    'List of IP addresses (IPv4/IPv6, local storage only)' => 'Lijst van IP-adressen (IPv4/IPv6, alleen lokale opslag)',
    'Customer ID for business relationships' => 'Klant ID voor zakelijke relaties',
    'PowerDNS Admin account ID (synced from PowerDNS Admin)' => 'PowerDNS Admin account ID (gesynchroniseerd vanuit PowerDNS Admin)',
    'Creation timestamp' => 'Aanmaak tijdstempel',
    'Last update timestamp' => 'Laatste update tijdstempel',
    'Username for PowerDNS Admin (must be unique)' => 'Gebruikersnaam voor PowerDNS Admin (moet uniek zijn)',
    'Plain text password for PowerDNS Admin user' => 'Platte tekst wachtwoord voor PowerDNS Admin gebruiker',
    'User role object' => 'Gebruikersrol object',
    'Role name' => 'Rolnaam',
    'Domain ID' => 'Domein ID',
    'Domain name' => 'Domeinnaam',
    'Domain type' => 'Domeintype',
    'Associated account ID' => 'Gekoppelde account ID',
    'Associated account name' => 'Gekoppelde accountnaam',
    'PDNSAdmin zone ID' => 'PDNSAdmin zone ID',
    'Zone kind' => 'Zone type',
    'Comma-separated list of master servers' => 'Kommagescheiden lijst van master servers',
    'DNSSEC enabled' => 'DNSSEC ingeschakeld',
    'Account name from PDNSAdmin' => 'Accountnaam vanuit PDNSAdmin',
    
    // Response descriptions
    'Successful operation' => 'Succesvolle operatie',
    'Resource created successfully' => 'Bron succesvol aangemaakt',
    'Resource updated successfully' => 'Bron succesvol bijgewerkt',
    'Resource deleted successfully' => 'Bron succesvol verwijderd',
    'Bad request - Invalid input data' => 'Slecht verzoek - Ongeldige invoergegevens',
    'Unauthorized - Invalid or missing API key' => 'Ongeautoriseerd - Ongeldige of ontbrekende API sleutel',
    'Forbidden - Insufficient permissions' => 'Verboden - Onvoldoende rechten',
    'Resource not found' => 'Bron niet gevonden',
    'Conflict - Resource already exists' => 'Conflict - Bron bestaat al',
    'Internal server error' => 'Interne server fout',
    
    // Example values
    'admin' => 'beheerder',
    'newuser' => 'nieuwgebruiker',
    'john@example.com' => 'john@voorbeeld.nl',
    'admin@example.com' => 'beheerder@voorbeeld.nl',
    'example.com' => 'voorbeeld.nl',
    'test.com' => 'test.nl',
    'newdomain.com' => 'nieuwdomein.nl',
    'Administrator' => 'Beheerder',
    'User' => 'Gebruiker',
    'securepassword123' => 'veiligwachtwoord123',
    'operational' => 'operationeel',
    'connected' => 'verbonden',
    'API key required' => 'API sleutel vereist',
    'Invalid API key' => 'Ongeldige API sleutel',
    'Account created successfully' => 'Account succesvol aangemaakt',
    'Account updated successfully' => 'Account succesvol bijgewerkt',
    'Account deleted successfully' => 'Account succesvol verwijderd',
    'Domain created successfully' => 'Domein succesvol aangemaakt',
    'Domain updated successfully' => 'Domein succesvol bijgewerkt',
    'Domain deleted successfully' => 'Domein succesvol verwijderd',
    'Template created successfully' => 'Sjabloon succesvol aangemaakt',
    'IP added to allowlist' => 'IP toegevoegd aan toegestane lijst',
    'IP removed from allowlist' => 'IP verwijderd van toegestane lijst',
    'Access allowed' => 'Toegang toegestaan',
    'Access denied' => 'Toegang geweigerd'
];

// Apply translations to tags first
if (isset($spec['tags']) && is_array($spec['tags'])) {
    foreach ($spec['tags'] as $i => $tag) {
        if (isset($tag['name']) && isset($translations[$tag['name']])) {
            $spec['tags'][$i]['name'] = $translations[$tag['name']];
        }
        if (isset($tag['description']) && isset($translations[$tag['description']])) {
            $spec['tags'][$i]['description'] = $translations[$tag['description']];
        }
    }
}

// Simple recursive translation function for other content
function translateContent($data, $translations) {
    if (is_array($data)) {
        $result = [];
        foreach ($data as $key => $value) {
            $result[$key] = translateContent($value, $translations);
        }
        return $result;
    }
    
    if (is_string($data) && isset($translations[$data])) {
        return $translations[$data];
    }
    
    return $data;
}

// Apply translations to the rest of the spec (but preserve already translated tags)
$translatedTags = $spec['tags']; // Save the translated tags
$spec = translateContent($spec, $translations); 
$spec['tags'] = $translatedTags; // Restore the properly translated tags

// Output the translated spec
echo json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
