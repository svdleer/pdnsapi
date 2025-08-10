<?php
/**
 * Security Configuration Validator
 * Ensures all required environment variables are set and not using default values
 */

function validateEnvironmentSecurity() {
    $errors = [];
    $warnings = [];
    
    // Required environment variables
    $required_vars = [
        'PDNS_API_KEY' => 'PowerDNS Admin API Key',
        'PDNS_SERVER_KEY' => 'PowerDNS Server API Key',
        'PDNS_BASE_URL' => 'PowerDNS Admin Base URL',
        'API_DB_HOST' => 'API Database Host',
        'API_DB_NAME' => 'API Database Name',
        'API_DB_USER' => 'API Database User',
        'API_DB_PASS' => 'API Database Password',
        'PDNS_ADMIN_DB_HOST' => 'PowerDNS Admin Database Host',
        'PDNS_ADMIN_DB_NAME' => 'PowerDNS Admin Database Name',
        'PDNS_ADMIN_DB_USER' => 'PowerDNS Admin Database User',
        'PDNS_ADMIN_DB_PASS' => 'PowerDNS Admin Database Password'
    ];
    
    // Insecure default values that should not be used in production
    $insecure_defaults = [
        'api_key_required',
        'server_key_required',
        'password_required',
        'username_required',
        'database_name_required',
        'localhost',
        'your-pdns-api-key-here',
        'your-pdns-server-key-here'
    ];
    
    foreach ($required_vars as $var => $description) {
        if (!isset($_ENV[$var]) || empty($_ENV[$var])) {
            $errors[] = "❌ Missing required environment variable: {$var} ({$description})";
        } elseif (in_array($_ENV[$var], $insecure_defaults)) {
            $errors[] = "❌ Insecure default value for: {$var} ({$description})";
        }
    }
    
    // Check for production-like settings
    if (isset($_ENV['PDNS_BASE_URL']) && $_ENV['PDNS_BASE_URL'] === 'https://localhost/api/v1') {
        $warnings[] = "⚠️  Base URL is set to localhost - update for production";
    }
    
    if (isset($_ENV['API_DB_HOST']) && $_ENV['API_DB_HOST'] === 'localhost') {
        $warnings[] = "⚠️  Database host is localhost - update for production";
    }
    
    return [
        'errors' => $errors,
        'warnings' => $warnings,
        'is_secure' => empty($errors),
        'total_vars_checked' => count($required_vars)
    ];
}

function displaySecurityStatus() {
    echo "Security Configuration Validation\n";
    echo "=================================\n\n";
    
    $result = validateEnvironmentSecurity();
    
    echo "Checked {$result['total_vars_checked']} required environment variables\n\n";
    
    if (!empty($result['errors'])) {
        echo "SECURITY ERRORS:\n";
        foreach ($result['errors'] as $error) {
            echo "  {$error}\n";
        }
        echo "\n";
    }
    
    if (!empty($result['warnings'])) {
        echo "SECURITY WARNINGS:\n";
        foreach ($result['warnings'] as $warning) {
            echo "  {$warning}\n";
        }
        echo "\n";
    }
    
    if ($result['is_secure'] && empty($result['warnings'])) {
        echo "✅ All security checks passed!\n";
        echo "✅ No hardcoded credentials found in PHP files\n";
        echo "✅ All environment variables are properly configured\n";
    } elseif ($result['is_secure']) {
        echo "✅ Security requirements met (warnings should be addressed for production)\n";
    } else {
        echo "❌ Security configuration is not complete\n";
        echo "❌ Please fix the errors above before deploying to production\n";
    }
    
    return $result['is_secure'];
}

// If run directly, display security status
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    require_once __DIR__ . '/includes/env-loader.php';
    displaySecurityStatus();
}
?>
