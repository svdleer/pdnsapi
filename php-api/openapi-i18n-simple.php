<?php
// Simple test to check if the issue is with our complex translation logic
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

$lang = $_GET['lang'] ?? 'en';

if ($lang === 'en') {
    // For English, just return the original file content
    if (file_exists(__DIR__ . '/openapi.json')) {
        echo file_get_contents(__DIR__ . '/openapi.json');
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'English spec not found']);
    }
    exit;
}

// For Dutch, create a simple translated version
$englishContent = file_get_contents(__DIR__ . '/openapi.json');
if (!$englishContent) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not load base spec']);
    exit;
}

$openapi = json_decode($englishContent, true);
if (!$openapi) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not parse base spec']);
    exit;
}

// Simple translations
$openapi['info']['title'] = 'PDNSAdmin PHP API (Nederlands)';
$openapi['info']['description'] = 'PHP API wrapper voor PowerDNS Admin met lokale database opslag.';

// Translate basic tags
if (isset($openapi['tags'])) {
    foreach ($openapi['tags'] as &$tag) {
        switch ($tag['name']) {
            case 'Documentation':
                $tag['name'] = 'Documentatie';
                $tag['description'] = 'API documentatie en informatie';
                break;
            case 'Accounts':
                $tag['name'] = 'Accounts';
                $tag['description'] = 'Account beheer operaties';
                break;
            case 'Domains':
                $tag['name'] = 'Domeinen';
                $tag['description'] = 'Domein beheer operaties';
                break;
            case 'Templates':
                $tag['name'] = 'Sjablonen';
                $tag['description'] = 'Sjabloon beheer operaties';
                break;
            case 'Status':
                $tag['name'] = 'Systeem';
                $tag['description'] = 'Systeem status en gezondheidscontroles';
                break;
            case 'IP Allowlist':
                $tag['name'] = 'IP Allowlist';
                $tag['description'] = 'IP adres allowlist beheer';
                break;
        }
    }
}

echo json_encode($openapi, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
