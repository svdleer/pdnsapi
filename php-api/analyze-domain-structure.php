<?php
require_once 'classes/PDNSAdminClient.php';
require_once 'config/config.php';

echo "=== Domain Structure Analysis ===\n\n";

$client = new PDNSAdminClient($pdns_config);

echo "1. Fetching sample domains to analyze structure...\n";
$domains_response = $client->getAllDomains();

if ($domains_response['status_code'] == 200) {
    $domains = $domains_response['data'];
    echo "✅ Found " . count($domains) . " domains\n\n";
    
    if (count($domains) > 0) {
        echo "2. Domain structure analysis:\n";
        $sample_domain = $domains[0];
        echo "Available fields in domain object:\n";
        foreach ($sample_domain as $key => $value) {
            $type = gettype($value);
            $preview = is_array($value) ? '[array with ' . count($value) . ' items]' : 
                      (is_string($value) ? '"' . substr($value, 0, 50) . (strlen($value) > 50 ? '...' : '') . '"' : 
                       json_encode($value));
            echo "  - $key ($type): $preview\n";
        }
        
        echo "\n3. Sample domains with account information:\n";
        foreach (array_slice($domains, 0, 10) as $i => $domain) {
            $name = $domain['name'] ?? 'N/A';
            $account_id = $domain['account_id'] ?? 'N/A';
            $account = $domain['account'] ?? 'N/A';
            echo sprintf("  %2d. %-30s | Account ID: %-10s | Account: %s\n", 
                        $i + 1, $name, $account_id, $account);
        }
        
        echo "\n4. Unique account analysis:\n";
        $accounts = [];
        foreach ($domains as $domain) {
            if (isset($domain['account_id']) && $domain['account_id']) {
                $account_id = $domain['account_id'];
                if (!isset($accounts[$account_id])) {
                    $accounts[$account_id] = [
                        'account_id' => $account_id,
                        'account_name' => $domain['account'] ?? 'N/A',
                        'domain_count' => 0
                    ];
                }
                $accounts[$account_id]['domain_count']++;
            }
        }
        
        echo "Found " . count($accounts) . " unique accounts:\n";
        foreach (array_slice($accounts, 0, 20) as $account) {
            echo sprintf("  - Account ID: %-10s | Name: %-20s | Domains: %d\n", 
                        $account['account_id'], $account['account_name'], $account['domain_count']);
        }
        
        if (count($accounts) > 20) {
            echo "  ... and " . (count($accounts) - 20) . " more accounts\n";
        }
    }
} else {
    echo "❌ Failed to fetch domains: HTTP " . $domains_response['status_code'] . "\n";
    echo "Error: " . $domains_response['raw_response'] . "\n";
}

echo "\n=== Analysis Complete ===\n";
?>
