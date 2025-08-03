# API Key Authentication

The PDNSAdmin PHP API uses API key authentication to secure endpoints and ensure only authorized users can access the API.

## Overview

- **Authentication Method**: API Key
- **Required for**: All endpoints except documentation and OpenAPI spec
- **Supported Methods**: Header, Bearer token, Query parameter
- **Security**: 64-character hex keys generated with `random_bytes()`

## Configuration

### 1. Generate API Keys

Use the included generator:
```bash
php generate-api-keys.php
```

Or generate manually:
```bash
openssl rand -hex 32
```

### 2. Update Configuration

Edit `config/config.php` and update the `api_keys` array:

```php
'api_keys' => [
    'your-secure-64-char-api-key-here' => 'Production Key',
    'another-key-for-development-use' => 'Development Key',
    // Add more keys as needed
],
```

### 3. Enable/Disable Authentication

```php
'require_api_key' => true,  // Set to false to disable authentication
```

## Authentication Methods

### 1. X-API-Key Header (Recommended)
```bash
curl -H "X-API-Key: your-api-key-here" http://localhost/php-api/accounts
```

### 2. Authorization Bearer Token
```bash
curl -H "Authorization: Bearer your-api-key-here" http://localhost/php-api/accounts
```

### 3. Query Parameter (Development Only)
```bash
curl "http://localhost/php-api/accounts?api_key=your-api-key-here"
```

## Exempt Endpoints

These endpoints don't require authentication:

- `/` - API documentation
- `/docs` - Swagger UI
- `/openapi*` - OpenAPI specifications
- `/swagger*` - Swagger files

## Error Responses

### 401 Unauthorized
```json
{
  "status": 401,
  "error": "Unauthorized: Valid API key required",
  "details": {
    "authentication_methods": {
      "X-API-Key header": "X-API-Key: your-api-key",
      "Authorization header": "Authorization: Bearer your-api-key",
      "Query parameter (dev only)": "?api_key=your-api-key"
    },
    "documentation": "See /docs for API documentation"
  }
}
```

## Security Best Practices

### 1. Key Management
- Use different keys for different environments
- Rotate keys regularly
- Store keys securely (environment variables, secret management)
- Never commit keys to version control

### 2. Transmission Security
- Always use HTTPS in production
- Prefer header-based authentication over query parameters
- Monitor and log API usage

### 3. Access Control
- Use descriptive key names to track usage
- Implement key rotation procedures
- Monitor for unusual access patterns

## Implementation Examples

### PHP (cURL)
```php
$api_key = 'your-api-key-here';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/php-api/accounts');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-API-Key: ' . $api_key,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
```

### JavaScript (Fetch)
```javascript
const apiKey = 'your-api-key-here';
fetch('http://localhost/php-api/accounts', {
    headers: {
        'X-API-Key': apiKey,
        'Content-Type': 'application/json'
    }
})
.then(response => response.json())
.then(data => console.log(data));
```

### Python (requests)
```python
import requests

api_key = 'your-api-key-here'
headers = {
    'X-API-Key': api_key,
    'Content-Type': 'application/json'
}
response = requests.get('http://localhost/php-api/accounts', headers=headers)
data = response.json()
```

## Troubleshooting

### Common Issues

1. **401 Unauthorized**
   - Check if API key is correct
   - Verify key exists in configuration
   - Ensure authentication is enabled

2. **Missing Header**
   - Verify header name: `X-API-Key`
   - Check for typos in header value
   - Ensure header is being sent

3. **Configuration Issues**
   - Check `config/config.php` syntax
   - Verify `require_api_key` setting
   - Ensure keys array is properly formatted

### Debug Mode

Enable debug mode in `config/config.php`:
```php
'debug_mode' => true,
```

This will log all API requests with authentication details.

## Monitoring and Logging

The API automatically logs requests when debug mode is enabled:

```json
{
  "timestamp": "2025-08-03 12:00:00",
  "endpoint": "accounts",
  "method": "GET",
  "status_code": 200,
  "api_key_used": "Production Key",
  "ip_address": "192.168.1.100"
}
```

Check your PHP error log for these entries.
