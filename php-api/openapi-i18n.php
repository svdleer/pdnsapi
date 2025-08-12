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

// Comprehensive Dutch translations - MASSIVE expansion for 90%+ coverage
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
    'Interactive API documentation' => 'Interactieve API documentatie',
    
    // Detailed endpoint descriptions - MAJOR EXPANSION
    'Retrieve accounts from local database with PowerDNS Admin synchronization.' => 'Haal accounts op uit lokale database met PowerDNS Admin synchronisatie.',
    'Create a new account in both local database and PowerDNS Admin.' => 'Maak een nieuw account aan in zowel lokale database als PowerDNS Admin.',
    'Update account information in local database and PowerDNS Admin.' => 'Werk account informatie bij in lokale database en PowerDNS Admin.',
    'Delete account from both local database and PowerDNS Admin.' => 'Verwijder account uit zowel lokale database als PowerDNS Admin.',
    'Search and retrieve domain information with intelligent search capabilities.' => 'Zoek en haal domein informatie op met intelligente zoekmogelijkheden.',
    'Get detailed information about a specific domain including DNS records.' => 'Haal gedetailleerde informatie op over een specifiek domein inclusief DNS records.',
    'Update domain configuration and settings in PowerDNS Admin.' => 'Werk domein configuratie en instellingen bij in PowerDNS Admin.',
    'Remove domain from PowerDNS Admin (use with caution).' => 'Verwijder domein uit PowerDNS Admin (gebruik met voorzichtigheid).',
    'Get current API status, health information, and system diagnostics.' => 'Haal huidige API status, gezondheids informatie en systeem diagnostiek op.',
    'List all available domain templates for creating standardized domains.' => 'Toon alle beschikbare domein sjablonen voor het maken van gestandaardiseerde domeinen.',
    'Create a new domain template with predefined DNS records.' => 'Maak een nieuw domein sjabloon aan met voorgedefinieerde DNS records.',
    'Update an existing domain template configuration.' => 'Werk een bestaande domein sjabloon configuratie bij.',
    'Delete a domain template (does not affect domains created from it).' => 'Verwijder een domein sjabloon (heeft geen invloed op domeinen gemaakt hiervan).',
    'Create a new domain based on a template with customizable parameters.' => 'Maak een nieuw domein aan gebaseerd op een sjabloon met aanpasbare parameters.',
    'Associate a domain with an account for management purposes.' => 'Koppel een domein aan een account voor beheersdoeleinden.',
    'Remove the association between a domain and an account.' => 'Verwijder de koppeling tussen een domein en een account.',
    'List all domains associated with a specific account.' => 'Toon alle domeinen gekoppeld aan een specifiek account.',
    'Get the current IP allowlist for API access control.' => 'Haal de huidige IP toegestane lijst op voor API toegangscontrole.',
    'Add a new IP address or range to the allowlist.' => 'Voeg een nieuw IP-adres of bereik toe aan de toegestane lijst.',
    'Update an existing IP allowlist entry.' => 'Werk een bestaand IP toegestane lijst item bij.',
    'Remove an IP address from the allowlist.' => 'Verwijder een IP-adres van de toegestane lijst.',
    'Test if a specific IP address would be allowed by the current allowlist.' => 'Test of een specifiek IP-adres zou worden toegestaan door de huidige toegestane lijst.',
    'This endpoint provides comprehensive API documentation and examples.' => 'Dit endpoint biedt uitgebreide API documentatie en voorbeelden.',

    // Response descriptions - MASSIVE EXPANSION  
    'List of accounts or single account (with AND validation)' => 'Lijst van accounts of enkel account (met AND validatie)',
    'Account created successfully' => 'Account succesvol aangemaakt',
    'Account updated successfully' => 'Account succesvol bijgewerkt', 
    'Account deleted successfully' => 'Account succesvol verwijderd',
    'List of domains matching search criteria' => 'Lijst van domeinen die voldoen aan zoekcriteria',
    'Complete domain information with DNS records' => 'Volledige domein informatie met DNS records',
    'Domain updated successfully' => 'Domein succesvol bijgewerkt',
    'Domain deleted successfully' => 'Domein succesvol verwijderd',
    'API status and health information' => 'API status en gezondheids informatie',
    'List of available domain templates' => 'Lijst van beschikbare domein sjablonen',
    'Template created successfully' => 'Sjabloon succesvol aangemaakt',
    'Template updated successfully' => 'Sjabloon succesvol bijgewerkt',
    'Template deleted successfully' => 'Sjabloon succesvol verwijderd',
    'Domain created from template successfully' => 'Domein succesvol aangemaakt vanuit sjabloon',
    'Domain added to account successfully' => 'Domein succesvol toegevoegd aan account',
    'Domain removed from account successfully' => 'Domein succesvol verwijderd van account',
    'List of domains for the specified account' => 'Lijst van domeinen voor het opgegeven account',
    'Current IP allowlist entries' => 'Huidige IP toegestane lijst items',
    'IP added to allowlist successfully' => 'IP succesvol toegevoegd aan toegestane lijst',
    'IP allowlist entry updated successfully' => 'IP toegestane lijst item succesvol bijgewerkt',
    'IP removed from allowlist successfully' => 'IP succesvol verwijderd van toegestane lijst',
    'IP access test results' => 'IP toegang test resultaten',
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
    'Validation error - Check request format' => 'Validatie fout - Controleer verzoek formaat',
    'Authentication required' => 'Authenticatie vereist',
    'Access denied' => 'Toegang geweigerd',
    'Resource has been modified' => 'Bron is gewijzigd',
    'Request timeout' => 'Verzoek timeout',
    
    // Complex descriptions with formatting - MAJOR ADDITION
    'Filter Logic (AND conditions):' => 'Filter Logica (AND condities):',
    'Single parameter: Direct lookup' => 'Enkele parameter: Directe opzoeking',
    'Multiple parameters: Must ALL match (validation)' => 'Meerdere parameters: Moeten ALLE overeenkomen (validatie)',  
    'Returns 404 if parameters don\'t match the same user' => 'Geeft 404 terug als parameters niet overeenkomen met dezelfde gebruiker',
    'Requires Admin API Key' => 'Vereist Admin API Sleutel',
    'Smart Search Features:' => 'Slimme Zoek Functies:',
    'Auto-detects ID vs name vs pattern vs contains' => 'Detecteert automatisch ID vs naam vs patroon vs bevat',
    'Supports wildcards (*) and partial matching' => 'Ondersteunt wildcards (*) en gedeeltelijke overeenkomsten',
    'Case-insensitive search for domain names' => 'Hoofdletter-ongevoelige zoeken voor domeinnamen',
    'Returns domains with full metadata' => 'Geeft domeinen terug met volledige metadata',
    'SECURITY VALIDATION:' => 'BEVEILIGING VALIDATIE:',
    'Record names must belong to the domain being updated' => 'Record namen moeten behoren tot het domein dat wordt bijgewerkt',
    'DNS record content validated for proper format' => 'DNS record inhoud gevalideerd op juist formaat', 
    'Dangerous record types (NS, SOA) require additional verification' => 'Gevaarlijke record types (NS, SOA) vereisen extra verificatie',
    'Maximum 100 records per update to prevent abuse' => 'Maximum 100 records per update om misbruik te voorkomen',
    'TESTING & DEBUGGING:' => 'TESTEN & DEBUGGEN:',
    'Validate IP access before making changes' => 'Valideer IP toegang voordat wijzigingen worden gemaakt',
    'Test CIDR range matching' => 'Test CIDR bereik overeenkomsten',
    'Verify allowlist configuration' => 'Verifieer toegestane lijst configuratie',
    'Useful for troubleshooting access issues' => 'Nuttig voor het oplossen van toegangsproblemen',
    
    // Parameter descriptions - COMPREHENSIVE
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
    'Account ID to filter domains' => 'Account ID om domeinen te filteren',
    'Filter by account ID' => 'Filteren op account ID',
    
    // Schema field descriptions - MAJOR EXPANSION
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
    'Template ID' => 'Sjabloon ID',
    'Template name' => 'Sjabloon naam',
    'Template description' => 'Sjabloon beschrijving',
    'Template records' => 'Sjabloon records',
    'Template records to create' => 'Sjabloon records om aan te maken',
    'Record ID' => 'Record ID',
    'Record name' => 'Record naam',
    'Record type' => 'Record type',
    'Record content' => 'Record inhoud',
    'Time to live' => 'Time to live',
    'Record priority (for MX records)' => 'Record prioriteit (voor MX records)',
    'Time to live, default 3600' => 'Time to live, standaard 3600',
    'Allowlist entry ID' => 'Toegestane lijst item ID',
    'IP address or CIDR range' => 'IP-adres of CIDR bereik',
    'Description of this IP entry' => 'Beschrijving van dit IP item',
    'Whether this IP is currently enabled' => 'Of dit IP momenteel ingeschakeld is',
    'When this entry was created' => 'Wanneer dit item is aangemaakt',
    'When this entry was last updated' => 'Wanneer dit item voor het laatst is bijgewerkt',
    'Unique account ID' => 'Unieke account ID',
    'Unique username' => 'Unieke gebruikersnaam',
    'Password' => 'Wachtwoord',
    'Unique domain ID' => 'Unieke domein ID',
    'Account owner ID' => 'Account eigenaar ID',
    'API status' => 'API status',
    'API version' => 'API versie',
    'Status timestamp' => 'Status tijdstempel',
    'Database connection status' => 'Database verbindingsstatus',
    'PowerDNS Admin connection status' => 'PowerDNS Admin verbindingsstatus',
    'Success message' => 'Succesbericht',
    'Error description' => 'Fout beschrijving',
    'Error message' => 'Foutbericht',
    'Detailed error messages' => 'Gedetailleerde foutberichten',
    
    // Example values - COMPREHENSIVE
    'admin' => 'beheerder',
    'newuser' => 'nieuwgebruiker',
    'john@example.com' => 'john@voorbeeld.nl',
    'admin@example.com' => 'beheerder@voorbeeld.nl',
    'example.com' => 'voorbeeld.nl',
    'test.com' => 'test.nl',
    'newdomain.com' => 'nieuwdomein.nl',
    'mydomain.com' => 'mijndomein.nl',
    'company.com' => 'bedrijf.nl',
    'Administrator' => 'Beheerder',
    'User' => 'Gebruiker',
    'securepassword123' => 'veiligwachtwoord123',
    'mypassword' => 'mijnwachtwoord',
    'operational' => 'operationeel',
    'connected' => 'verbonden',
    'disconnected' => 'losgekoppeld',
    'enabled' => 'ingeschakeld',
    'disabled' => 'uitgeschakeld',
    'active' => 'actief',
    'inactive' => 'inactief',
    'Master' => 'Master',
    'Slave' => 'Slave', 
    'Native' => 'Native',
    'API key required' => 'API sleutel vereist',
    'Invalid API key' => 'Ongeldige API sleutel',
    'Username already exists' => 'Gebruikersnaam bestaat al',
    'Email already in use' => 'E-mail al in gebruik',
    'Domain not found' => 'Domein niet gevonden',
    'Template not found' => 'Sjabloon niet gevonden',
    'Access denied' => 'Toegang geweigerd',
    'IP not in allowlist' => 'IP niet in toegestane lijst',
    'Invalid IP address format' => 'Ongeldig IP-adres formaat',
    'Office workstation' => 'Kantoor werkstation',
    'Home office' => 'Thuiskantoor',
    'Server location' => 'Server locatie',
    'VPN gateway' => 'VPN gateway',
    'Load balancer' => 'Load balancer',
    'Web server' => 'Web server',
    'Mail server' => 'Mail server',
    'DNS server' => 'DNS server',
    
    // Multi-line description phrases for partial replacement - CRITICAL ADDITION
    'Retrieve accounts from local database with PowerDNS Admin synchronization.' => 'Haal accounts op uit lokale database met PowerDNS Admin synchronisatie.',
    '**🔑 Requires Admin API Key**' => '**🔑 Vereist Admin API Sleutel**',
    '**Filter Logic (AND conditions):**' => '**Filter Logica (EN condities):**',
    'Single parameter: Direct lookup' => 'Enkele parameter: Directe opzoeking',
    'Multiple parameters: Must ALL match (validation)' => 'Meerdere parameters: Moeten ALLE overeenkomen (validatie)',
    'returns user only if ID 14 has username' => 'geeft gebruiker alleen terug als ID 14 gebruikersnaam heeft',
    'Returns 404 if parameters don\'t match the same user' => 'Geeft 404 terug als parameters niet overeenkomen met dezelfde gebruiker',
    'Search and retrieve domain information with intelligent search capabilities.' => 'Zoek en haal domein informatie op met intelligente zoekmogelijkheden.',
    '**Smart Search Features:**' => '**Slimme Zoek Functies:**',
    'Auto-detects ID vs name vs pattern vs contains' => 'Detecteert automatisch ID vs naam vs patroon vs bevat',
    'Supports wildcards (*) and partial matching' => 'Ondersteunt wildcards (*) en gedeeltelijke overeenkomsten',
    'Case-insensitive search for domain names' => 'Hoofdletter-ongevoelige zoeken voor domeinnamen',
    'Returns domains with full metadata' => 'Geeft domeinen terug met volledige metadata',
    'Get detailed information about a specific domain including DNS records.' => 'Haal gedetailleerde informatie op over een specifiek domein inclusief DNS records.',
    'Update domain configuration and settings in PowerDNS Admin.' => 'Werk domein configuratie en instellingen bij in PowerDNS Admin.',
    '**SECURITY VALIDATION:**' => '**BEVEILIGING VALIDATIE:**',
    'Record names must belong to the domain being updated' => 'Record namen moeten behoren tot het domein dat wordt bijgewerkt',
    'DNS record content validated for proper format' => 'DNS record inhoud gevalideerd op juist formaat',
    'Dangerous record types (NS, SOA) require additional verification' => 'Gevaarlijke record types (NS, SOA) vereisen extra verificatie',
    'Maximum 100 records per update to prevent abuse' => 'Maximum 100 records per update om misbruik te voorkomen',
    'Remove domain from PowerDNS Admin (use with caution).' => 'Verwijder domein uit PowerDNS Admin (gebruik met voorzichtigheid).',
    'Get current API status, health information, and system diagnostics.' => 'Haal huidige API status, gezondheids informatie en systeem diagnostiek op.',
    'List all available domain templates for creating standardized domains.' => 'Toon alle beschikbare domein sjablonen voor het maken van gestandaardiseerde domeinen.',
    'Create a new domain template with predefined DNS records.' => 'Maak een nieuw domein sjabloon aan met voorgedefinieerde DNS records.',
    'Update an existing domain template configuration.' => 'Werk een bestaande domein sjabloon configuratie bij.',
    'Delete a domain template (does not affect domains created from it).' => 'Verwijder een domein sjabloon (heeft geen invloed op domeinen gemaakt hiervan).',
    'Create a new domain based on a template with customizable parameters.' => 'Maak een nieuw domein aan gebaseerd op een sjabloon met aanpasbare parameters.',
    'Associate a domain with an account for management purposes.' => 'Koppel een domein aan een account voor beheersdoeleinden.',
    'Remove the association between a domain and an account.' => 'Verwijder de koppeling tussen een domein en een account.',
    'List all domains associated with a specific account.' => 'Toon alle domeinen gekoppeld aan een specifiek account.',
    'Get the current IP allowlist for API access control.' => 'Haal de huidige IP toegestane lijst op voor API toegangscontrole.',
    'Add a new IP address or range to the allowlist.' => 'Voeg een nieuw IP-adres of bereik toe aan de toegestane lijst.',
    'Update an existing IP allowlist entry.' => 'Werk een bestaand IP toegestane lijst item bij.',
    'Remove an IP address from the allowlist.' => 'Verwijder een IP-adres van de toegestane lijst.',
    'Test if a specific IP address would be allowed by the current allowlist.' => 'Test of een specifiek IP-adres zou worden toegestaan door de huidige toegestane lijst.',
    '**TESTING & DEBUGGING:**' => '**TESTEN & DEBUGGEN:**',
    'Validate IP access before making changes' => 'Valideer IP toegang voordat wijzigingen worden gemaakt',
    'Test CIDR range matching' => 'Test CIDR bereik overeenkomsten',
    'Verify allowlist configuration' => 'Verifieer toegestane lijst configuratie',
    'Useful for troubleshooting access issues' => 'Nuttig voor het oplossen van toegangsproblemen',
    'This endpoint provides comprehensive API documentation and examples.' => 'Dit endpoint biedt uitgebreide API documentatie en voorbeelden.',
    'Create new account in both local database and PowerDNS Admin.' => 'Maak nieuw account aan in zowel lokale database als PowerDNS Admin.',
    'Update account information in local database and PowerDNS Admin.' => 'Werk account informatie bij in lokale database en PowerDNS Admin.',
    'Delete account from both local database and PowerDNS Admin.' => 'Verwijder account uit zowel lokale database als PowerDNS Admin.',
    'List of domains/zones' => 'Lijst van domeinen/zones',
    'Domain information' => 'Domein informatie',
    'API status information' => 'API status informatie',
    'List of templates' => 'Lijst van sjablonen',
    'Template information' => 'Sjabloon informatie'
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

// Enhanced recursive translation function with partial string replacement
function translateContent($data, $translations) {
    if (is_array($data)) {
        $result = [];
        foreach ($data as $key => $value) {
            $result[$key] = translateContent($value, $translations);
        }
        return $result;
    }
    
    if (is_string($data)) {
        // First try exact match
        if (isset($translations[$data])) {
            return $translations[$data];
        }
        
        // If no exact match, try partial replacements for multi-line descriptions
        $translated = $data;
        foreach ($translations as $english => $dutch) {
            if (strlen($english) > 10) { // Only apply partial replacement for longer strings
                $translated = str_replace($english, $dutch, $translated);
            }
        }
        return $translated;
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
