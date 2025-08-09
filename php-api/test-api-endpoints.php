<?php
/**
 * Test script for API endpoints - domains and accounts
 */

$base_url = "https://pdnsapi.avant.nl";
$api_key = getenv('PDNS_API_KEY') ?: 'your-api-key-here';

echo "🧪 Testing PowerDNS Admin API Integration\n";
echo "========================================\n\n";

function makeApiRequest($endpoint, $method = 'GET', $data = null) {
    global $base_url, $api_key;
    
    $url = $base_url . $endpoint;
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-API-Key: ' . $api_key,
        'Content-Type: application/json'
    ]);
    
    if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status_code' => $http_code,
        'data' => json_decode($response, true),
        'raw' => $response
    ];
}

echo "1️⃣ Testing Account Endpoints\n";
echo "----------------------------\n";

// Test accounts list
echo "📋 GET /api/accounts - List all accounts:\n";
$response = makeApiRequest('/api/accounts');
echo "Status: {$response['status_code']}\n";
if ($response['status_code'] == 200 && is_array($response['data'])) {
    echo "✅ Found " . count($response['data']) . " accounts\n";
    if (!empty($response['data'])) {
        $first_account = $response['data'][0];
        echo "   First account: {$first_account['username']} (ID: {$first_account['id']})\n";
        $test_account_id = $first_account['id'];
    }
} else {
    echo "❌ Error: " . ($response['data']['error'] ?? 'Unknown error') . "\n";
    $test_account_id = 1; // Fallback
}
echo "\n";

// Test account sync
echo "🔄 GET /api/accounts?sync=true - Sync accounts from PowerDNS Admin:\n";
$response = makeApiRequest('/api/accounts?sync=true');
echo "Status: {$response['status_code']}\n";
if ($response['status_code'] == 200) {
    echo "✅ Sync completed\n";
    if (isset($response['data']['total_processed'])) {
        echo "   Processed: {$response['data']['total_processed']} accounts\n";
    }
} else {
    echo "❌ Sync failed: " . ($response['data']['error'] ?? 'Unknown error') . "\n";
}
echo "\n";

echo "2️⃣ Testing Domain Endpoints\n";
echo "---------------------------\n";

// Test domains list
echo "📋 GET /api/domains - List all domains:\n";
$response = makeApiRequest('/api/domains');
echo "Status: {$response['status_code']}\n";
if ($response['status_code'] == 200 && is_array($response['data'])) {
    echo "✅ Found " . count($response['data']) . " domains\n";
    if (!empty($response['data'])) {
        $first_domain = $response['data'][0];
        echo "   First domain: {$first_domain['name']} (Account ID: " . ($first_domain['account_id'] ?? 'none') . ")\n";
        $test_domain_name = $first_domain['name'];
    }
} else {
    echo "❌ Error: " . ($response['data']['error'] ?? 'Unknown error') . "\n";
    $test_domain_name = null;
}
echo "\n";

// Test domain sync
echo "🔄 GET /api/domains?sync=true - Sync domains from PowerDNS Admin:\n";
$response = makeApiRequest('/api/domains?sync=true');
echo "Status: {$response['status_code']}\n";
if ($response['status_code'] == 200) {
    echo "✅ Domain sync completed\n";
    if (isset($response['data']['synced'])) {
        echo "   Synced: {$response['data']['synced']} new domains\n";
    }
    if (isset($response['data']['updated'])) {
        echo "   Updated: {$response['data']['updated']} existing domains\n";
    }
    if (isset($response['data']['accounts_synced'])) {
        echo "   Accounts synced: {$response['data']['accounts_synced']} accounts\n";
    }
} else {
    echo "❌ Sync failed: " . ($response['data']['error'] ?? 'Unknown error') . "\n";
}
echo "\n";

// Test domains by account
if (isset($test_account_id)) {
    echo "🏢 GET /api/domains?account_id={$test_account_id} - Get domains for account:\n";
    $response = makeApiRequest("/api/domains?account_id={$test_account_id}");
    echo "Status: {$response['status_code']}\n";
    if ($response['status_code'] == 200 && is_array($response['data'])) {
        echo "✅ Found " . count($response['data']) . " domains for account {$test_account_id}\n";
        foreach (array_slice($response['data'], 0, 3) as $domain) {
            echo "   - {$domain['name']}\n";
        }
    } else {
        echo "❌ Error: " . ($response['data']['error'] ?? 'Unknown error') . "\n";
    }
    echo "\n";
}

echo "3️⃣ Testing Domain-Account API\n";
echo "-----------------------------\n";

if (isset($test_account_id)) {
    // Test domain-account list
    echo "📋 POST /api/domain-account.php?action=list - List domains for account:\n";
    $response = makeApiRequest('/api/domain-account.php?action=list', 'POST', [
        'account_id' => $test_account_id
    ]);
    echo "Status: {$response['status_code']}\n";
    if ($response['status_code'] == 200) {
        echo "✅ Domain-account API working\n";
        if (isset($response['data']['domains']) && is_array($response['data']['domains'])) {
            echo "   Found " . count($response['data']['domains']) . " domains for account\n";
            foreach (array_slice($response['data']['domains'], 0, 3) as $domain) {
                echo "   - {$domain['name']}\n";
            }
        }
        if (isset($response['data']['account'])) {
            echo "   Account: {$response['data']['account']['name']} (ID: {$response['data']['account']['id']})\n";
        }
    } else {
        echo "❌ Error: " . ($response['data']['error'] ?? 'Unknown error') . "\n";
    }
    echo "\n";
}

echo "4️⃣ Testing Database Relationships\n";
echo "---------------------------------\n";

// Test if domains are properly linked to accounts
echo "🔗 Testing domain-account linkage in database:\n";
$response = makeApiRequest('/api/domains');
if ($response['status_code'] == 200 && is_array($response['data'])) {
    $domains_with_accounts = 0;
    $domains_without_accounts = 0;
    
    foreach ($response['data'] as $domain) {
        if (!empty($domain['account_id'])) {
            $domains_with_accounts++;
        } else {
            $domains_without_accounts++;
        }
    }
    
    echo "✅ Domain-account linkage analysis:\n";
    echo "   📎 Domains WITH accounts: {$domains_with_accounts}\n";
    echo "   ❓ Domains WITHOUT accounts: {$domains_without_accounts}\n";
    
    $total_domains = count($response['data']);
    $percentage = $domains_with_accounts > 0 ? round(($domains_with_accounts / $total_domains) * 100, 1) : 0;
    echo "   📊 Account linkage rate: {$percentage}%\n";
    
    if ($percentage > 50) {
        echo "   ✅ Good! Most domains are linked to accounts\n";
    } elseif ($percentage > 0) {
        echo "   ⚠️  Some domains are linked, but many are unassigned\n";
    } else {
        echo "   ❌ No domains are linked to accounts - sync may not be working\n";
    }
} else {
    echo "❌ Could not analyze domain-account linkage\n";
}
echo "\n";

echo "5️⃣ Testing PowerDNS Admin API Connection\n";
echo "----------------------------------------\n";

// This would test the underlying PowerDNS Admin connection
echo "🔌 Connection test (via domain sync):\n";
$response = makeApiRequest('/api/domains?sync=true');
if ($response['status_code'] == 200) {
    echo "✅ PowerDNS Admin API connection working\n";
    echo "   Sync process completed successfully\n";
} else {
    echo "❌ PowerDNS Admin API connection issue\n";
    echo "   Error: " . ($response['data']['error'] ?? 'Unknown error') . "\n";
}
echo "\n";

echo "📊 Test Summary\n";
echo "==============\n";
echo "API Key: " . (strlen($api_key) > 10 ? substr($api_key, 0, 8) . "..." : $api_key) . "\n";
echo "Base URL: {$base_url}\n";
echo "\n";
echo "✅ = Working properly\n";
echo "⚠️  = Working but with issues\n";  
echo "❌ = Not working\n";
echo "\nTest completed at " . date('Y-m-d H:i:s') . "\n";
?>
