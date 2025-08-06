<?php
// Test direct curl approach
$url = "https://dnsadmin.avant.nl/api/v1/pdnsadmin/zones";
$auth = base64_encode("admin:dnVeku8Jeku");

echo "Testing direct curl approach...\n";
echo "URL: $url\n";
echo "Auth: admin:dnVeku8Jeku (base64: $auth)\n\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Basic ' . $auth
    ],
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $http_code\n";
echo "cURL Error: " . ($curl_error ?: 'None') . "\n";
echo "Response: $response\n";

if ($response) {
    $decoded = json_decode($response, true);
    if (is_array($decoded)) {
        echo "Array Count: " . count($decoded) . "\n";
        if (count($decoded) > 0) {
            echo "First item: " . json_encode($decoded[0]) . "\n";
        }
    }
}
?>
