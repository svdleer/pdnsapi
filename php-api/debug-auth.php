<?php
// Debug authentication configuration
require_once 'config/config.php';

header('Content-Type: application/json');

$debug_info = [
    'api_settings_exist' => isset($api_settings),
    'api_security_exist' => isset($api_security),
    'config_security_exist' => isset($config['security']),
    'require_api_key' => isset($api_settings['require_api_key']) ? $api_settings['require_api_key'] : 'NOT SET',
    'ip_validation_enabled' => isset($config['security']['ip_validation_enabled']) ? $config['security']['ip_validation_enabled'] : 'NOT SET',
    'require_ip_allowlist' => isset($api_security['require_ip_allowlist']) ? $api_security['require_ip_allowlist'] : 'NOT SET',
    'client_ip' => function_exists('getClientIpAddress') ? getClientIpAddress() : 'FUNCTION NOT EXISTS',
    'api_key_from_request' => function_exists('getApiKeyFromRequest') ? (getApiKeyFromRequest() ? 'PRESENT' : 'MISSING') : 'FUNCTION NOT EXISTS',
    'is_ip_allowed' => function_exists('isIpAllowed') ? (isIpAllowed() ? 'ALLOWED' : 'BLOCKED') : 'FUNCTION NOT EXISTS',
    'current_path' => $_SERVER['REQUEST_URI'] ?? 'NOT SET'
];

echo json_encode($debug_info, JSON_PRETTY_PRINT);
?>
