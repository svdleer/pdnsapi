<?php
// Minimal test
$openapi = json_decode(file_get_contents(__DIR__ . '/openapi.json'), true);
$translations = ['Documentation' => 'Documentatie'];

echo "Before: " . $openapi['tags'][0]['name'] . PHP_EOL;

if (isset($translations[$openapi['tags'][0]['name']])) {
    $openapi['tags'][0]['name'] = $translations[$openapi['tags'][0]['name']];
    echo "After: " . $openapi['tags'][0]['name'] . PHP_EOL;
} else {
    echo "No translation found for: " . $openapi['tags'][0]['name'] . PHP_EOL;
}
?>
