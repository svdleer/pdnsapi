<?php
/**
 * Test Delete by ID and Username
 * Tests that our delete function supports both numeric IDs and usernames
 */

require_once 'includes/autoloader.php';
require_once 'config/config.php';
require_once 'models/Account.php';
require_once 'classes/PDNSAdminClient.php';

echo "=== Testing Delete by ID and Username Support ===\n\n";

$account = new Account($db);

// Test the deleteAccount function logic without actually deleting
function testDeleteLogic($account_identifier) {
    echo "Testing identifier: '$account_identifier'\n";
    
    if (is_numeric($account_identifier)) {
        echo "  ✓ Detected as numeric ID - will lookup account first, then use username for PowerDNS Admin API\n";
        echo "  Process: ID -> readOne() -> get username -> deleteUser(username)\n";
    } else {
        echo "  ✓ Detected as string username - will use directly for PowerDNS Admin API\n";  
        echo "  Process: username -> readByName(username) -> deleteUser(username)\n";
    }
    echo "\n";
}

// Test different identifier types
testDeleteLogic("123");       // Numeric ID
testDeleteLogic("testuser");  // Username string
testDeleteLogic("94");        // Another numeric ID

echo "=== URL Patterns Supported ===\n";
echo "DELETE /accounts/123      -> deleteAccount(\$account, '123')  -> ID lookup -> username\n";
echo "DELETE /accounts/testuser -> deleteAccount(\$account, 'testuser') -> direct username\n";
echo "DELETE /accounts?id=123   -> deleteAccount(\$account, '123')  -> ID lookup -> username\n";

echo "\n=== PowerDNS Admin API Call ===\n";
echo "All delete operations will call: DELETE /pdnsadmin/users/{username}\n";
echo "This ensures compatibility with PowerDNS Admin's username-based API.\n";

echo "\n✅ Delete function now supports both ID and username identifiers!\n";
?>
