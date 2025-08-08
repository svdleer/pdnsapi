# PowerDNS Admin PHP API - Curl Examples

Complete curl examples for all API endpoints using the Admin API Key authentication.

## Authentication

All API endpoints (except documentation) require the Admin API Key in the X-API-Key header:

```bash
# API Key (replace with your actual key)
API_KEY="46b3d78c557cd66a047a38897914d203ab5c359719161e836ecce5508e57b1a9"

# Base URL
BASE_URL="https://pdnsapi.avant.nl"
```

### Swagger UI Authentication

When using Swagger UI to test endpoints:

1. Visit `https://pdnsapi.avant.nl/docs`
2. Click the **"Authorize"** button (🔒) at the top right
3. Enter your API key: `46b3d78c557cd66a047a38897914d203ab5c359719161e836ecce5508e57b1a9`
4. Click **"Authorize"** to save credentials
5. All endpoint tests will now include proper authentication

**Note:** Swagger UI enforces authentication - you cannot test endpoints without providing the API key first.

## 1. Documentation Endpoints (No Auth Required)

### Get API Documentation
```bash
curl -X GET "${BASE_URL}/" \
  -H "Accept: application/json"
```

### Get OpenAPI Specification
```bash
curl -X GET "${BASE_URL}/openapi.json" \
  -H "Accept: application/json"
```

## 2. Status Endpoint

### Check API Status
```bash
curl -X GET "${BASE_URL}/status" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}"
```

## 3. Accounts Management

### List All Accounts
```bash
curl -X GET "${BASE_URL}/accounts" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}"
```

### List Accounts with Sync from PowerDNS Admin
```bash
curl -X GET "${BASE_URL}/accounts?sync=true" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}"
```

### Filter Account by ID
```bash
curl -X GET "${BASE_URL}/accounts?id=14" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}"
```

### Filter Account by Username
```bash
curl -X GET "${BASE_URL}/accounts?username=admin" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}"
```

### Validate Account (AND Filter) - ID + Username Must Match
```bash
curl -X GET "${BASE_URL}/accounts?id=14&username=admin" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}"
```

### Filter Account by Email
```bash
curl -X GET "${BASE_URL}/accounts?email=admin@example.com" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}"
```

### Create New Account
```bash
curl -X POST "${BASE_URL}/accounts" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}" \
  -d '{
    "username": "newuser",
    "email": "newuser@example.com", 
    "password": "SecurePass123!",
    "firstname": "John",
    "lastname": "Doe",
    "role_name": "User",
    "ips": ["192.168.1.100", "203.0.113.50"]
  }'
```

### Update Account
```bash
curl -X PUT "${BASE_URL}/accounts" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}" \
  -d '{
    "id": 15,
    "email": "updated@example.com",
    "firstname": "Jane",
    "lastname": "Smith",
    "ips": ["192.168.1.101", "203.0.113.51"]
  }'
```

### Delete Account
```bash
curl -X DELETE "${BASE_URL}/accounts" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}" \
  -d '{
    "id": 15
  }'
```

## 4. Domains Management

### List All Domains
```bash
curl -X GET "${BASE_URL}/domains" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}"
```

### List Domains with Sync from PowerDNS Admin
```bash
curl -X GET "${BASE_URL}/domains?sync=true" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}"
```

### Filter Domain by ID
```bash
curl -X GET "${BASE_URL}/domains?id=42" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}"
```

### Filter Domain by Name
```bash
curl -X GET "${BASE_URL}/domains?name=example.com" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}"
```

### Filter Domain by Type
```bash
curl -X GET "${BASE_URL}/domains?type=Master" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}"
```

### Validate Domain (AND Filter) - ID + Name Must Match
```bash
curl -X GET "${BASE_URL}/domains?id=42&name=example.com" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}"
```

### Create New Domain
```bash
curl -X POST "${BASE_URL}/domains" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}" \
  -d '{
    "name": "newdomain.com",
    "kind": "Master", 
    "nameservers": ["ns1.example.com", "ns2.example.com"],
    "account_id": 14
  }'
```

### Update Domain
```bash
curl -X PUT "${BASE_URL}/domains" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}" \
  -d '{
    "id": 42,
    "account": "admin"
  }'
```

### Delete Domain (Dangerous Operation - Requires Confirmation)
```bash
curl -X DELETE "${BASE_URL}/domains" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}" \
  -d '{
    "id": 42,
    "confirm_delete": true
  }'
```

### Get Specific Domain by ID
```bash
curl -X GET "${BASE_URL}/domains/42" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}"
```

### Update Specific Domain
```bash
curl -X PUT "${BASE_URL}/domains/42" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}" \
  -d '{
    "account": "admin"
  }'
```

### Delete Specific Domain (Dangerous Operation)
```bash
curl -X DELETE "${BASE_URL}/domains/42" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}" \
  -d '{
    "confirm_delete": true
  }'
```

## 5. Domain Templates

### List All Templates
```bash
curl -X GET "${BASE_URL}/templates" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}"
```

### Filter Template by ID
```bash
curl -X GET "${BASE_URL}/templates?id=1" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}"
```

### Filter Template by Name
```bash
curl -X GET "${BASE_URL}/templates?name=basic-dns" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}"
```

### Create New Template
```bash
curl -X POST "${BASE_URL}/templates" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}" \
  -d '{
    "name": "web-server-template",
    "description": "Standard web server DNS setup",
    "records": [
      {
        "name": "@",
        "type": "A", 
        "content": "192.0.2.100",
        "ttl": 3600
      },
      {
        "name": "www",
        "type": "A",
        "content": "192.0.2.100", 
        "ttl": 3600
      },
      {
        "name": "@",
        "type": "MX",
        "content": "10 mail.{domain}",
        "ttl": 3600
      }
    ]
  }'
```

### Get Specific Template
```bash
curl -X GET "${BASE_URL}/templates/1" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}"
```

### Update Template
```bash
curl -X PUT "${BASE_URL}/templates/1" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}" \
  -d '{
    "name": "updated-web-template",
    "description": "Updated web server template",
    "records": [
      {
        "name": "@",
        "type": "A",
        "content": "192.0.2.101", 
        "ttl": 1800
      }
    ]
  }'
```

### Delete Template
```bash
curl -X DELETE "${BASE_URL}/templates/1" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}"
```

### Create Domain from Template
```bash
curl -X POST "${BASE_URL}/templates/1/create-domain" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}" \
  -d '{
    "domain_name": "newsite.com",
    "account_id": 14,
    "variables": {
      "domain": "newsite.com",
      "server_ip": "192.0.2.100",
      "mail_server": "mail.newsite.com"
    }
  }'
```

## 6. Domain-Account Associations

### Link Domain to Account
```bash
curl -X POST "${BASE_URL}/domain-account" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}" \
  -d '{
    "domain_id": 42,
    "account_id": 14
  }'
```

### Link Domain to Account (Using Names)
```bash
curl -X POST "${BASE_URL}/domain-account" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}" \
  -d '{
    "domain_name": "example.com", 
    "account_username": "admin"
  }'
```

### Update Domain-Account Association
```bash
curl -X PUT "${BASE_URL}/domain-account" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}" \
  -d '{
    "domain_id": 42,
    "account_id": 15
  }'
```

### Remove Domain-Account Association
```bash
curl -X DELETE "${BASE_URL}/domain-account" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}" \
  -d '{
    "domain_id": 42,
    "account_id": 14
  }'
```

## Common Response Examples

### Successful Response
```json
{
  "success": true,
  "data": [...],
  "message": "Operation completed successfully"
}
```

### Error Response
```json
{
  "success": false,
  "error": "Authentication required",
  "message": "API key is required for this endpoint"
}
```

### Validation Error
```json
{
  "success": false, 
  "error": "Validation failed",
  "message": "Required field 'name' is missing",
  "details": {
    "field": "name",
    "code": "REQUIRED_FIELD_MISSING"
  }
}
```

## Security Notes

1. **API Key Security**: The API key provides full administrative access. Keep it secure and never log it.

2. **IP Allowlist**: Your IP must be in the global allowlist (`config/config.php`) to access any endpoint.

3. **HTTPS Only**: All requests must use HTTPS. HTTP requests are redirected.

4. **Dangerous Operations**: Domain deletion requires `confirm_delete: true` in the request body.

5. **Rate Limiting**: Consider implementing rate limiting for production use.

## Tips for Testing

### Set Environment Variables
```bash
export API_KEY="46b3d78c557cd66a047a38897914d203ab5c359719161e836ecce5508e57b1a9"
export BASE_URL="https://pdnsapi.avant.nl"
```

### Pretty Print JSON Responses
```bash
curl -X GET "${BASE_URL}/accounts" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}" \
  | jq .
```

### Save Response to File
```bash
curl -X GET "${BASE_URL}/domains" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}" \
  -o domains_response.json
```

### Test with Verbose Output
```bash
curl -v -X GET "${BASE_URL}/status" \
  -H "Accept: application/json" \
  -H "X-API-Key: ${API_KEY}"
```
