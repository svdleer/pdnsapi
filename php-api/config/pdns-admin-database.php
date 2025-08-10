<?php
/**
 * PowerDNS Admin Database Configuration
 */

try {
    // PowerDNS Admin database connection
    $pdns_admin_config = [
        'host' => $_ENV['PDNS_ADMIN_DB_HOST'] ?? 'cora.avant.nl',
        'port' => $_ENV['PDNS_ADMIN_DB_PORT'] ?? 3306,
        'database' => $_ENV['PDNS_ADMIN_DB_NAME'] ?? 'pda',
        'username' => $_ENV['PDNS_ADMIN_DB_USER'] ?? 'pdns_api_db',
        'password' => $_ENV['PDNS_ADMIN_DB_PASS'] ?? '8swoajKuchij]'
    ];

    $dsn = sprintf(
        "mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4",
        $pdns_admin_config['host'],
        $pdns_admin_config['port'],
        $pdns_admin_config['database']
    );

    $pdns_admin_pdo = new PDO($dsn, $pdns_admin_config['username'], $pdns_admin_config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ]);
    
    // Set global variable for use in other files
    global $pdns_admin_pdo;
    
} catch (PDOException $e) {
    error_log("PowerDNS Admin database connection failed: " . $e->getMessage());
    $pdns_admin_pdo = null;
}

// Set the config array for PDNSAdminClient
$pdns_config = [
    'base_url' => $_ENV['PDNS_BASE_URL'] ?? 'https://cora.avant.nl/api/v1',
    'auth_type' => 'basic',
    'api_key' => $_ENV['PDNS_API_KEY'] ?? 'your-pdns-api-key-here',
    'pdns_server_key' => $_ENV['PDNS_SERVER_KEY'] ?? 'your-pdns-server-key-here'
];
?>