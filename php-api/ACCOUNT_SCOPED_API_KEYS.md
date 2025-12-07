# Account-Scoped API Keys

## Overview

The PowerDNS Admin PHP API now supports **account-scoped API keys** that provide granular access control. These keys restrict access to domains belonging to a specific account, with configurable permissions for read, write, create, and delete operations.

## Key Features

- **Account-Level Isolation**: Each API key is tied to a specific account and can only access domains owned by that account
- **Granular Permissions**: Control exactly what operations are allowed (read, write, create, delete)
- **Automatic Assignment**: Domains created with account-scoped keys are automatically assigned to the key's account
- **Security**: Keys are hashed in database, support expiration dates, and track last usage
- **Admin Control**: Only admin API keys can create and manage account-scoped keys

## Database Schema

The `api_keys` table stores account-scoped API keys:

```sql
CREATE TABLE api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_key VARCHAR(64) NOT NULL UNIQUE,
    key_hash VARCHAR(255) NOT NULL,
    account_id INT NULL,                  -- NULL = admin key, otherwise account ID
    description VARCHAR(500) DEFAULT '',
    permissions JSON DEFAULT NULL,         -- Permissions configuration
    enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    last_used_at TIMESTAMP NULL,
    created_by INT NULL
);
```

## Installation

1. **Create the database table**:
   ```bash
   mysql -u root -p pdns_api_db < database/add_api_keys_table.sql
   ```

2. **Verify the table was created**:
   ```bash
   mysql -u root -p pdns_api_db -e "DESCRIBE api_keys;"
   ```

## Permissions Structure

Permissions are stored as JSON with the following structure:

```json
{
  "domains": "rw",           // Read-write access to domains
  "create_domains": true,    // Can create new domains
  "delete_domains": false,   // Cannot delete domains
  "scope": "account"         // Scope limited to account's domains
}
```

### Permission Fields

- **`domains`**: Access level for domain operations
  - `"r"` - Read only
  - `"w"` - Write only (update)
  - `"rw"` - Read and write

- **`create_domains`**: Boolean - Allow creating new domains

- **`delete_domains`**: Boolean - Allow deleting domains

- **`scope`**: Always `"account"` for account-scoped keys

## API Endpoints

### Create Account-Scoped API Key

**Endpoint**: `POST /api/api-keys`  
**Authentication**: Admin API key required  
**Content-Type**: `application/json`

**Request Body**:
```json
{
  "account_id": 5,
  "description": "Customer portal access for Account 5",
  "permissions": {
    "domains": "rw",
    "create_domains": true,
    "delete_domains": false
  },
  "allow_delete": false,
  "expires_at": "2026-12-31 23:59:59",
  "enabled": true
}
```

**Response** (201 Created):
```json
{
  "message": "API key created successfully",
  "data": {
    "id": 1,
    "api_key": "pdns_a1b2c3d4e5f6...64chars",
    "account_id": 5,
    "account_username": "customer5",
    "description": "Customer portal access for Account 5",
    "permissions": {
      "domains": "rw",
      "create_domains": true,
      "delete_domains": false,
      "scope": "account"
    },
    "enabled": true,
    "expires_at": "2026-12-31 23:59:59",
    "warning": "Save this API key securely. It will not be displayed again."
  }
}
```

⚠️ **IMPORTANT**: The full `api_key` is only returned once during creation. Save it securely!

### List All API Keys

**Endpoint**: `GET /api/api-keys`  
**Authentication**: Admin API key required

**Optional Query Parameters**:
- `account_id` - Filter by account ID

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": 1,
      "key_preview": "pdns_a1b2c3d...",
      "account_id": 5,
      "description": "Customer portal access for Account 5",
      "permissions": {
        "domains": "rw",
        "create_domains": true,
        "delete_domains": false,
        "scope": "account"
      },
      "enabled": true,
      "created_at": "2025-12-07 10:30:00",
      "updated_at": "2025-12-07 10:30:00",
      "expires_at": "2026-12-31 23:59:59",
      "last_used_at": "2025-12-07 15:45:23",
      "created_by": null
    }
  ]
}
```

### Get Specific API Key

**Endpoint**: `GET /api/api-keys?id=1`  
**Authentication**: Admin API key required

**Response**: Same structure as single item in list response

### Update API Key

**Endpoint**: `PUT /api/api-keys?id=1`  
**Authentication**: Admin API key required  
**Content-Type**: `application/json`

**Request Body** (all fields optional):
```json
{
  "description": "Updated description",
  "permissions": {
    "domains": "rw",
    "create_domains": true,
    "delete_domains": true
  },
  "enabled": false,
  "expires_at": "2027-12-31 23:59:59"
}
```

**Response** (200 OK):
```json
{
  "message": "API key updated successfully"
}
```

### Delete API Key

**Endpoint**: `DELETE /api/api-keys?id=1`  
**Authentication**: Admin API key required

**Response** (200 OK):
```json
{
  "message": "API key deleted successfully"
}
```

## Using Account-Scoped API Keys

### Authentication

Use the account-scoped API key the same way as admin keys:

```bash
# Using X-API-Key header (recommended)
curl -X GET "https://pdnsapi.avant.nl/api/domains" \
  -H "X-API-Key: pdns_a1b2c3d4e5f6...64chars"

# Using Authorization header
curl -X GET "https://pdnsapi.avant.nl/api/domains" \
  -H "Authorization: Bearer pdns_a1b2c3d4e5f6...64chars"
```

### List Domains (Account-Scoped)

With an account-scoped key, you'll only see domains belonging to the associated account:

```bash
curl -X GET "https://pdnsapi.avant.nl/api/domains" \
  -H "X-API-Key: pdns_a1b2c3d4e5f6...64chars"
```

Response will only include domains where `account_id` matches the key's account.

### Create Domain (Account-Scoped)

Domains are automatically assigned to the key's account:

```bash
curl -X POST "https://pdnsapi.avant.nl/api/domains" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: pdns_a1b2c3d4e5f6...64chars" \
  -d '{
    "name": "example.com",
    "kind": "Native",
    "nameservers": ["ns1.example.com", "ns2.example.com"]
  }'
```

The `account_id` is automatically set to the key's account and cannot be overridden.

### Update Domain (Account-Scoped)

Can only update domains belonging to the key's account:

```bash
curl -X PUT "https://pdnsapi.avant.nl/api/domains?id=123" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: pdns_a1b2c3d4e5f6...64chars" \
  -d '{
    "kind": "Master",
    "dnssec": true
  }'
```

Returns 403 Forbidden if domain doesn't belong to the key's account.

### Delete Domain (Account-Scoped)

Only if `delete_domains` permission is `true`:

```bash
curl -X DELETE "https://pdnsapi.avant.nl/api/domains?id=123" \
  -H "X-API-Key: pdns_a1b2c3d4e5f6...64chars"
```

Returns 403 Forbidden if:
- Permission not granted
- Domain doesn't belong to the key's account

## Permission Checking

The system automatically checks permissions for all domain operations:

### Read Operations
- **GET /api/domains** - Requires `domains: "r"` or `domains: "rw"`
- Results automatically filtered by account_id

### Write Operations  
- **PUT /api/domains** - Requires `domains: "w"` or `domains: "rw"`
- Validates domain belongs to account

### Create Operations
- **POST /api/domains** - Requires `create_domains: true`
- Automatically assigns to key's account

### Delete Operations
- **DELETE /api/domains** - Requires `delete_domains: true`
- Validates domain belongs to account

## Error Responses

### 401 Unauthorized
```json
{
  "error": "Valid API Key required"
}
```

### 403 Forbidden (Permission Denied)
```json
{
  "error": "Insufficient permissions to create domains"
}
```

### 403 Forbidden (Domain Access)
```json
{
  "error": "Access denied: Domain does not belong to your account"
}
```

### 403 Forbidden (Admin Only)
```json
{
  "error": "Only admin API keys can manage API keys"
}
```

## Security Considerations

1. **API Key Storage**: 
   - Full keys are only shown once at creation
   - Keys are hashed (SHA-256) in the database
   - Use secure storage (password managers, secrets management)

2. **Key Rotation**:
   - Set expiration dates on keys
   - Delete keys when no longer needed
   - Monitor `last_used_at` for inactive keys

3. **Principle of Least Privilege**:
   - Only grant `delete_domains` if absolutely necessary
   - Use read-only keys (`domains: "r"`) when possible
   - Set appropriate expiration dates

4. **Monitoring**:
   - Track `last_used_at` timestamps
   - Review security logs for unauthorized access attempts
   - Regularly audit active keys

## Example Use Cases

### Customer Self-Service Portal

Create a key for customers to manage their own domains:

```json
{
  "account_id": 42,
  "description": "Customer portal for client XYZ",
  "permissions": {
    "domains": "rw",
    "create_domains": true,
    "delete_domains": false
  }
}
```

### Read-Only Monitoring

Create a key for monitoring/reporting tools:

```json
{
  "account_id": 10,
  "description": "Monitoring dashboard - read only",
  "permissions": {
    "domains": "r",
    "create_domains": false,
    "delete_domains": false
  }
}
```

### Automated Domain Provisioning

Create a key for automated systems with full access:

```json
{
  "account_id": 5,
  "description": "Automated provisioning system",
  "permissions": {
    "domains": "rw",
    "create_domains": true,
    "delete_domains": true
  },
  "expires_at": "2026-01-31 23:59:59"
}
```

## Best Practices

1. **Use Descriptive Names**: Make descriptions clear about purpose and owner
2. **Set Expiration Dates**: Don't create keys that never expire
3. **Monitor Usage**: Check `last_used_at` to identify unused keys
4. **Rotate Regularly**: Create new keys and delete old ones periodically
5. **Audit Access**: Regularly review which keys have which permissions
6. **Disable Not Delete**: Disable keys first, delete after confirming no issues
7. **Document Keys**: Keep internal documentation of which systems use which keys

## Troubleshooting

### Key Not Working

1. Check if key is enabled: `GET /api/api-keys?id=X`
2. Check if key has expired
3. Verify permissions are correctly set
4. Check `last_used_at` is updating (proves key is being received)

### Permission Denied Errors

1. Verify the operation matches granted permissions
2. Check domain belongs to the correct account
3. Confirm key hasn't expired

### Cannot Manage API Keys

Only admin API keys can access `/api/api-keys` endpoints. Use your main admin key from `.env` (AVANT_API_KEY).

## Migration from Old System

If you have existing integrations using admin keys, they will continue to work. Admin keys have full access and are not affected by this update.

To migrate to account-scoped keys:

1. Create account-scoped keys for each account
2. Update client applications to use new keys
3. Test thoroughly before removing admin key access
4. Monitor for any access issues

## Support

For issues or questions:
- Check the main API documentation at `/docs.html`
- Review security logs for authentication issues
- Contact system administrator for key management assistance
