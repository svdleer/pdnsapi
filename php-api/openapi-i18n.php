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

// Simple Dutch translations
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
    'IP Allowlist' => 'IP Whitelist',
    'Security' => 'Beveiliging', 
    'Testing' => 'Testen',
    
    // Common descriptions
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
