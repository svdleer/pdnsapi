# PDNSAdmin PHP API

A PHP-based API wrapper for PDNSAdmin that provides local database storage and extended functionality for managing DNS accounts and domains.

## Features

- **Account Management**: Create, read, update, delete accounts with local database storage and IP address management
- **Domain Management**: Manage domains with synchronization from PDNSAdmin (creation only, no deletion)
- **Domain-Account Association**: Link domains to accounts with automatic PDNSAdmin synchronization
- **IP Address Management**: Store IPv4/IPv6 addresses for accounts (local only, not sent to PDNSAdmin)
- **Data Synchronization**: Sync domains and accounts from PDNSAdmin API
- **Local Database**: Store data locally for better performance and offline access
- **RESTful API**: Clean REST endpoints for all operations
- **Error Handling**: Comprehensive error handling and validation

## Requirements

- PHP 7.4 or higher
- MySQL/MariaDB database
- PDO extension
- cURL extension
- PDNSAdmin instance with API access
- Apache/Nginx web server with proper configuration

## Installation

1. **Clone/Download the API files**

2. **Setup Database**:
   ```bash
   mysql -u root -p < database/schema.sql
   ```

3. **Configure Database Connection**:
   Copy and edit the configuration files:
   ```bash
   cp config/database.php.example config/database.php
   cp config/config.php.example config/config.php
   ```
   
   Edit `config/database.php`:
   ```php
   $config = [
       'host' => 'localhost',
       'dbname' => 'pdns_api_db',
       'username' => 'your_username',
       'password' => 'your_password'
   ];
   ```

4. **Configure PDNSAdmin Connection**:
   Edit `config/config.php`:
   ```php
   $pdns_config = [
       'api_url' => 'http://your-pdnsadmin-host:port/api/v1',
       'api_key' => 'your-pdnsadmin-api-key'
   ];
   ```

5. **Generate API Keys for Authentication**:
   ```bash
   php generate-api-keys.php
   ```
   
   Update the API keys in `config/config.php`:
   ```php
   'api_keys' => [
       'your-generated-64-char-key' => 'Production Key',
       'another-key-for-development' => 'Development Key'
   ],
   ```

6. **SSL/HTTPS Setup** (Recommended for production):
   ```bash
   # Enable Apache SSL module
   sudo a2enmod ssl rewrite headers
   
   # Get Let's Encrypt certificate
   sudo certbot --apache -d your-domain.com
   
   # Or use the provided SSL configuration
   # See SSL_SETUP.md for detailed instructions
   ```

7. **Web Server Setup**:
   - **Apache**: Copy `apache.conf.example` to your Apache sites configuration
   - **Nginx**: Use `nginx.conf.example` for Nginx configuration
   - Both configurations include HTTP→HTTPS redirect and security headers
   - Ensure proper directory permissions:
     ```bash
     sudo chown -R www-data:www-data /path/to/php-api
     sudo chmod -R 755 /path/to/php-api
     ```

## Authentication

All API endpoints (except documentation) require authentication using API keys.

### Authentication Methods

1. **X-API-Key Header (Recommended)**:
   ```bash
   curl -H "X-API-Key: your-api-key-here" http://your-api/accounts
   ```

2. **Authorization Bearer Token**:
   ```bash
   curl -H "Authorization: Bearer your-api-key-here" http://your-api/accounts
   ```

3. **Query Parameter (Development only)**:
   ```bash
   curl "http://your-api/accounts?api_key=your-api-key-here"
   ```

### Exempt Endpoints (No Authentication Required)
- `/` - API documentation
- `/docs` - Swagger UI
- `/openapi*` - OpenAPI specifications

## API Endpoints

All examples below include authentication headers.

### Account Management

#### Get All Accounts
```bash
curl -H "X-API-Key: your-api-key" GET /accounts
```

#### Get Account by ID
```
GET /accounts?id={account_id}
```

#### Get Account by Name
```
GET /accounts?name={account_name}
```

#### Create Account
```
POST /accounts
Content-Type: application/json

{
    "name": "example-account",
    "description": "Example account description",
    "contact": "John Doe",
    "mail": "john@example.com",
    "ip_addresses": ["192.168.1.100", "2001:db8::1"]
}
```

#### Update Account
```
PUT /accounts?id={account_id}
Content-Type: application/json

{
    "description": "Updated description",
    "contact": "Jane Doe",
    "mail": "jane@example.com",
    "ip_addresses": ["192.168.1.101", "192.168.1.102"]
}
```

#### Delete Account
```
DELETE /accounts?id={account_id}
```

### Domain Management

#### Get All Domains
```
GET /domains
```

#### Get Domain by ID
```
GET /domains?id={domain_id}
```

#### Get Domains by Account
```
GET /domains?account_id={account_id}
```

#### Sync Domains from PDNSAdmin
```
GET /domains?sync=true
```

#### Create Domain
```
POST /domains
Content-Type: application/json

{
    "name": "example.com.",
    "kind": "Master",
    "account_id": 1,
    "nameservers": ["ns1.example.com.", "ns2.example.com."]
}
```
*Note: When account_id is provided, the domain is automatically assigned to that account in both local database and PDNSAdmin.*

#### Update Domain
```
PUT /domains?id={domain_id}
Content-Type: application/json

{
    "account_id": 2,
    "kind": "Slave",
    "masters": ["192.168.1.100"]
}
```
*Note: When account_id is changed, the domain account assignment is automatically updated in PDNSAdmin.*

#### Delete Domain
*Domain deletion has been removed for safety. Domains can only be created and updated.*

### Domain-Account Operations

#### Add Domain to Account
```
POST /domain-account?action=add
Content-Type: application/json

{
    "domain_name": "example.com.",
    "account_id": 1
}
```

#### Remove Domain from Account
```
POST /domain-account?action=remove
Content-Type: application/json

{
    "domain_name": "example.com."
}
```

#### List Account Domains
```
POST /domain-account?action=list
Content-Type: application/json

{
    "account_id": 1
}
```

### System Status and Health

#### API Status
```
GET /status
```

#### Test PDNSAdmin Connection
```
GET /status?action=test_connection
```

#### Sync All Data
```
GET /status?action=sync_all
```

#### Health Check
```
GET /status?action=health
```

## Database Schema

The API uses the following database tables:

- **accounts**: Store account information
- **domains**: Store domain information with account associations
- **api_logs**: Log API calls for debugging
- **domain_sync**: Track synchronization status

## Configuration

### Authentication Types

The API supports two authentication methods for PDNSAdmin:

1. **Basic Authentication**:
   ```php
   'auth_type' => 'basic',
   'username' => 'your_username',
   'password' => 'your_password'
   ```

2. **API Key Authentication**:
   ```php
   'auth_type' => 'apikey',
   'api_key' => 'your_api_key'
   ```

### CORS Configuration

CORS headers are automatically set to allow cross-origin requests. Modify the headers in `config/config.php` if needed.

## Usage Examples

### Initial Setup

1. **Test Connection**:
   ```bash
   curl -H "X-API-Key: your-api-key" https://your-api-host/status?action=test_connection
   ```

2. **Sync Initial Data**:
   ```bash
   curl -H "X-API-Key: your-api-key" https://your-api-host/domains?sync=true
   ```

### Create Account and Add Domain

1. **Create Account**:
   ```bash
   curl -X POST https://your-api-host/accounts \
     -H "X-API-Key: your-api-key" \
     -H "Content-Type: application/json" \
     -d '{"name":"customer1","description":"Customer 1","contact":"John Doe","mail":"john@customer1.com"}'
   ```

2. **Add Domain to Account**:
   ```bash
   curl -X POST https://your-api-host/domain-account?action=add \
     -H "X-API-Key: your-api-key" \
     -H "Content-Type: application/json" \
     -d '{"domain_name":"customer1.com.","account_id":1}'
   ```

## Error Handling

The API returns standardized error responses:

```json
{
    "error": "Error message",
    "errors": ["Additional error details"]
}
```

Common HTTP status codes:
- `200`: Success
- `201`: Created
- `204`: No Content (successful deletion/update)
- `400`: Bad Request
- `401`: Unauthorized
- `403`: Forbidden
- `404`: Not Found
- `409`: Conflict
- `500`: Internal Server Error
- `503`: Service Unavailable

## Security Considerations

1. **SSL/HTTPS**: 
   - Use HTTPS in production (required)
   - Automatic HTTP to HTTPS redirection configured
   - Modern TLS 1.2/1.3 encryption
   - HSTS headers for security

2. **API Authentication**: 
   - 64-character secure API keys
   - Multiple authentication methods supported
   - API key rotation capabilities

3. **Security Headers**:
   - HSTS (HTTP Strict Transport Security)
   - CSP (Content Security Policy)
   - X-Frame-Options, X-XSS-Protection
   - Referrer-Policy controls

4. **Database Security**: Use strong database credentials and limit access

5. **Web Server**: Configure proper access controls and file permissions

6. **Input Validation**: The API includes comprehensive input validation

7. **Certificate Management**: 
   - Use trusted SSL certificates (Let's Encrypt recommended)
   - Set up automatic certificate renewal

## Troubleshooting

### Common Issues

1. **Database Connection Errors**:
   - Check database credentials in `config/database.php`
   - Ensure database exists and schema is imported

2. **PDNSAdmin Connection Errors**:
   - Verify PDNSAdmin URL and credentials in `config/config.php`
   - Check network connectivity to PDNSAdmin instance

3. **Permission Errors**:
   - Ensure web server has read/write access to the API directory
   - Check database user permissions

4. **Apache "AH01630: client denied by server configuration"**:
   ```bash
   # Set proper permissions
   sudo chown -R www-data:www-data /opt/web/pdnsapi.avant.nl
   sudo chmod -R 755 /opt/web/pdnsapi.avant.nl
   
   # Check Apache virtual host configuration
   # See APACHE_TROUBLESHOOTING.md for detailed solutions
   ```

5. **SSL/HTTPS Issues**:
   ```bash
   # Test SSL certificate
   openssl s_client -connect your-domain:443 -servername your-domain
   
   # Check certificate expiration
   openssl s_client -connect your-domain:443 2>/dev/null | openssl x509 -noout -dates
   
   # Verify HTTP to HTTPS redirect
   curl -I http://your-domain/
   
   # See SSL_SETUP.md for complete SSL troubleshooting
   ```

6. **Authentication Issues**:
   - Verify API key in config/config.php
   - Check X-API-Key header is being sent
   - Ensure endpoint is not in exempt_endpoints list

### Debug Mode

Enable debug mode in `config/config.php`:
```php
'debug_mode' => true,
'log_level' => 'DEBUG'
```

This will log all API requests and authentication details to your PHP error log.

### Additional Resources

- `SSL_SETUP.md` - Complete SSL/HTTPS setup guide with Let's Encrypt
- `APACHE_TROUBLESHOOTING.md` - Detailed Apache configuration help
- `AUTHENTICATION.md` - Complete authentication guide
- `/docs` - Interactive Swagger UI documentation
- `/openapi` - OpenAPI 3.x specification

## License

This project is open source. Please check the license file for details.
