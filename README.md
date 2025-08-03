# PDNSAdmin PHP API

A comprehensive PHP API wrapper for PowerDNS Admin with local database storage and extended functionality.

## Features

- 🏢 **Account Management**: Full CRUD operations with local IP address storage
- 🌐 **Domain Management**: Create and update domains with PDNSAdmin synchronization
- 🔗 **Domain-Account Links**: Associate domains with accounts automatically
- 📡 **Data Synchronization**: Sync domains and accounts from PDNSAdmin API
- 🗄️ **Local Database**: Enhanced storage with IP addresses and custom fields
- 📚 **OpenAPI 3.x Documentation**: Complete Swagger/OpenAPI specification
- 🔒 **Safety Features**: Domain deletion disabled, comprehensive validation

## Quick Start

### 1. Setup Database

Import the database schema:
```sql
mysql -u your_user -p your_database < php-api/database/schema.sql
```

### 2. Configuration

Copy and configure the database settings:
```bash
cp php-api/config/database.php.example php-api/config/database.php
cp php-api/config/config.php.example php-api/config/config.php
```

Update `php-api/config/database.php` with your database credentials:
```php
$config = [
    'host' => 'localhost',
    'dbname' => 'your_database',
    'username' => 'your_user',
    'password' => 'your_password'
];
```

Update `php-api/config/config.php` with your PDNSAdmin API details:
```php
$pdns_config = [
    'api_url' => 'http://your-pdnsadmin.com/api/v1',
    'api_key' => 'your-api-key'
];
```

### 3. Generate API Keys

Generate secure API keys for authentication:
```bash
cd php-api
php generate-api-keys.php
```

Update the `api_keys` array in `config/config.php`:
```php
'api_keys' => [
    'your-generated-64-char-key' => 'Production Key',
    'another-key-for-development' => 'Development Key'
],
```

### 4. Test the API

Test the connection with API key:
```bash
curl -H "X-API-Key: your-api-key-here" http://your-server/php-api/status?action=test_connection
```

Sync initial data:
```bash
curl -H "X-API-Key: your-api-key-here" http://your-server/php-api/domains?sync=true
```

## Authentication

The API uses API key authentication for security. See [AUTHENTICATION.md](php-api/AUTHENTICATION.md) for detailed setup instructions.

### Quick Authentication Setup
1. Generate API keys: `php php-api/generate-api-keys.php`
2. Add keys to `config/config.php`
3. Use in requests: `X-API-Key: your-key` or `Authorization: Bearer your-key`

### Authentication Methods
- **X-API-Key Header**: `X-API-Key: your-api-key` (Recommended)
- **Bearer Token**: `Authorization: Bearer your-api-key`
- **Query Parameter**: `?api_key=your-api-key` (Development only)

## API Endpoints

### Accounts
- `GET /accounts` - List all accounts with IP addresses
- `GET /accounts?id={id}` - Get specific account
- `GET /accounts?name={name}` - Get account by name
- `POST /accounts` - Create account with IP addresses
- `PUT /accounts?id={id}` - Update account including IPs
- `DELETE /accounts?id={id}` - Delete account

### Domains
- `GET /domains` - List all domains
- `GET /domains?id={id}` - Get specific domain
- `GET /domains?account_id={id}` - Get domains by account
- `GET /domains?sync=true` - Sync domains from PDNSAdmin
- `POST /domains` - Create domain (auto-assigns to account)
- `PUT /domains?id={id}` - Update domain (syncs account)
- `POST /domains?action=add_to_account` - Add domain to account

### Status & Health
- `GET /status` - API status and health check
- `GET /status?action=test_connection` - Test PDNSAdmin connection
- `GET /status?action=sync_all` - Sync all data from PDNSAdmin
- `GET /status?action=health` - Detailed health check

### Documentation
- `GET /openapi` - OpenAPI 3.0 specification (JSON)
- `GET /openapi.yaml` - OpenAPI 3.0 specification (YAML)
- `GET /docs` - Interactive Swagger UI documentation

## Key Features & Design Decisions

### IP Address Management
- **Local Storage Only**: IP addresses (IPv4/IPv6) are stored locally and not sent to PDNSAdmin
- **Flexible Format**: Supports single IPs, ranges, and CIDR notation
- **Validation**: Automatic validation for IPv4 and IPv6 addresses

### Domain Safety
- **No Deletion**: Domain deletion is disabled for safety
- **Account Sync**: Domain updates automatically sync account information with PDNSAdmin
- **Automatic Assignment**: New domains are automatically assigned to accounts

### Data Synchronization
- **Bidirectional**: Changes flow to PDNSAdmin when needed
- **Selective**: IP addresses remain local, other data syncs as appropriate
- **Manual Sync**: Explicit sync endpoints for initial setup and maintenance

## Development

### Project Structure
```
php-api/
├── api/
│   ├── accounts.php          # Account CRUD endpoints
│   ├── domains.php           # Domain management endpoints
│   └── status.php            # Status and health endpoints
├── classes/
│   └── PDNSAdminClient.php   # PDNSAdmin API client
├── config/
│   ├── config.php            # API configuration
│   └── database.php          # Database configuration
├── database/
│   └── schema.sql            # Database schema
├── models/
│   ├── Account.php           # Account model with IP support
│   └── Domain.php            # Domain model
├── docs.html                 # Documentation page
├── openapi.yaml              # OpenAPI specification (YAML)
├── openapi.json              # OpenAPI specification (JSON)
└── index.php                 # Main router
```

### Testing with Swagger UI

Visit `/docs` for interactive API testing and documentation.

### Example Usage

#### Create Account with IP Addresses
```bash
curl -X POST http://your-server/php-api/accounts \
  -H "X-API-Key: your-api-key-here" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "example-account",
    "description": "Example account",
    "ip_addresses": ["192.168.1.10", "10.0.0.0/24", "2001:db8::1"]
  }'
```

#### Create Domain and Assign to Account
```bash
curl -X POST http://your-server/php-api/domains \
  -H "X-API-Key: your-api-key-here" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "example.com",
    "account_id": 1,
    "kind": "Native"
  }'
```

## Requirements

- PHP 7.4 or higher
- MySQL/MariaDB
- PDO extension
- cURL extension
- PowerDNS Admin instance with API access

## License

MIT License - see LICENSE file for details.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## Support

For issues and questions, please use the GitHub issue tracker.
