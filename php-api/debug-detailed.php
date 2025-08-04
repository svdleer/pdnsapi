<?php
echo "=== DETAILED DOMAIN MODEL DEBUG ===\n\n";

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/database-compat.php';
require_once 'models/Domain.php';

$database = new Database();
$db = $database->getConnection();

$domain = new Domain($db);

echo "1. TESTING DOMAIN MODEL READ METHOD\n";

try {
    $stmt = $domain->read();
    echo "✅ read() method executed successfully\n";
    echo "Statement object: " . get_class($stmt) . "\n";
    echo "Row count: " . $stmt->rowCount() . "\n";
    
    echo "\n2. TESTING FETCH MODES\n";
    
    // Test different fetch modes
    $row1 = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "fetch(PDO::FETCH_ASSOC) returned: " . gettype($row1) . "\n";
    
    if (is_array($row1)) {
        echo "✅ First row is an array with " . count($row1) . " columns\n";
        echo "Columns: " . implode(', ', array_keys($row1)) . "\n";
        echo "Sample values: name=" . ($row1['name'] ?? 'NULL') . ", id=" . ($row1['id'] ?? 'NULL') . "\n";
    } else {
        echo "❌ First row is not an array: " . var_dump($row1) . "\n";
    }
    
    // Try fetching a few more rows
    echo "\n3. TESTING MULTIPLE ROWS\n";
    for ($i = 0; $i < 3; $i++) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (is_array($row)) {
            echo "  Row " . ($i + 2) . ": " . ($row['name'] ?? 'NO NAME') . " (ID: " . ($row['id'] ?? 'NO ID') . ")\n";
        } else {
            echo "  Row " . ($i + 2) . ": " . gettype($row) . " - " . var_export($row, true) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n4. TESTING DIRECT DATABASE QUERY\n";

try {
    $direct_query = "SELECT d.*, a.name as account_name 
                    FROM domains d
                    LEFT JOIN accounts a ON d.account_id = a.id
                    ORDER BY d.created_at DESC 
                    LIMIT 5";
    
    $stmt = $db->prepare($direct_query);
    $stmt->execute();
    
    echo "Direct query executed successfully\n";
    echo "Row count: " . $stmt->rowCount() . "\n";
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "fetchAll() returned " . count($results) . " rows\n";
    
    if (count($results) > 0) {
        echo "Sample direct results:\n";
        foreach (array_slice($results, 0, 3) as $i => $row) {
            echo "  Row " . ($i + 1) . ": " . ($row['name'] ?? 'NO NAME') . " (ID: " . ($row['id'] ?? 'NO ID') . ")\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Direct query error: " . $e->getMessage() . "\n";
}

echo "\n5. CHECKING DATABASE CONNECTION STATUS\n";
echo "Connection status: " . ($db ? "✅ Connected" : "❌ Not connected") . "\n";
if ($db) {
    $status = $db->getAttribute(PDO::ATTR_CONNECTION_STATUS);
    echo "Connection details: " . $status . "\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
?>
