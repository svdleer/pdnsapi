# PowerDNS Admin PHP API Wrapper

A production-ready PHP API wrapper for PowerDNS Admin with local database enhancements and template system.

## Overview

This API provides a comprehensive RESTful interface to PowerDNS Admin with a hybrid architecture:

- **PowerDNS Admin API is authoritative** for core resources (users, domains, API keys)
- **Local database provides** performance caching, templates, and extended metadata
- **Hybrid approach** ensures reliability while adding advanced features

## Features

### Core API Functions
- **User Management** - Full CRUD operations synced with PowerDNS Admin
- **Domain Management** - Create, list, and delete domains  
- **API Key Management** - Complete API key lifecycle management
- **Template System** - Local domain templates for rapid deployment

### Enhanced Features
- **IP Address Storage** - Associate IP addresses with accounts (local only)
- **Domain Templates** - Reusable domain configurations for quick deployment
- **Automatic Synchronization** - Real-time sync with PowerDNS Admin
- **Performance Caching** - Local database for fast queries and extended metadata

## Quick Start

### 1. Deploy PHP API
```bash
cd php-api
./deploy.sh
```

### 2. Configure Database
```bash
php setup.php
php migrate.php
```

### 3. Configure Environment
Copy and configure the settings:
```bash
cp php-api/config/config.php.example php-api/config/config.php
```

Set your PowerDNS Admin credentials and database settings in `config.php`.

### 4. Generate API Keys
```bash
cd php-api
php generate-api-keys.php
```

## API Documentation

- **Interactive Docs**: `/docs.html` (Swagger UI)  
- **OpenAPI Spec**: `/openapi.json`
- **Status Check**: `/status`

## API Endpoints

### Users/Accounts
- `GET /accounts` - List all users (synced from PowerDNS Admin)
- `POST /accounts` - Create new user account
- `PUT /accounts` - Update user account  
- `DELETE /accounts` - Delete user account

### Domains
- `GET /domains` - List all domains
- `POST /domains` - Create new domain
- `DELETE /domains/{id}` - Delete domain
- `POST /domains` - Create domain from template

### Templates (Local)
- `GET /templates` - List available templates
- `POST /templates` - Create new template
- `POST /domains` - Create domain from template (`{"name": "example.com", "template_id": 1}`)

### API Keys
- `GET /apikeys` - List API keys
- `POST /apikeys` - Create API key
- `PUT /apikeys/{id}` - Update API key
- `DELETE /apikeys/{id}` - Delete API key

## Architecture

### PowerDNS Admin API (Authoritative)
The following operations are handled directly by PowerDNS Admin:
- User management (`/pdnsadmin/users`)
- Domain management (`/pdnsadmin/zones`)
- API key management (`/pdnsadmin/apikeys`)

### Local Database (Extensions)
The local database provides:
- Domain templates for rapid deployment
- IP address associations for user accounts
- Performance caching of PowerDNS Admin data
- Extended metadata and business logic

## Authentication

The API uses **Basic Authentication** with base64 encoded credentials:

```bash
# Example API call
curl -X GET https://your-domain/api/v1/accounts \
  -H "Authorization: Basic <base64-credentials>"
```

## Usage Examples

### Create User Account
```bash
curl -X POST https://your-domain/api/v1/accounts \
  -H "Authorization: Basic <credentials>" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "johndoe",
    "email": "john@example.com",
    "password": "secure_password",
    "ip_addresses": ["192.168.1.100", "10.0.0.0/24"]
  }'
```

### Create Domain from Template
```bash
curl -X POST https://your-domain/api/v1/domains \
  -H "Authorization: Basic <credentials>" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "example.com", 
    "template_id": 1
  }'
```

### List All Users
```bash
curl -X GET https://your-domain/api/v1/accounts \
  -H "Authorization: Basic <credentials>"
```

## Production Features

✅ **Security**: HTTPS enforcement, security headers, input validation  
✅ **Performance**: Database connection pooling, caching, optimized queries  
✅ **Reliability**: Error handling, logging, health checks  
✅ **Documentation**: Complete OpenAPI 3.0 specification with Swagger UI  
✅ **Monitoring**: Status endpoints and detailed error reporting  

## Requirements

- PHP 7.4+ with PDO and cURL extensions
- MySQL/MariaDB database
- PowerDNS Admin instance with API access
- Web server with HTTPS support

## Deployment

The API is production-ready and includes:
- Apache/Nginx configuration examples
- SSL/TLS setup instructions  
- Environment configuration templates
- Database migration scripts
- Health check endpoints

See `php-api/DEPLOYMENT.md` for detailed deployment instructions.

## Support

- **API Documentation**: Visit `/docs.html` for interactive API explorer
- **Status Check**: Use `/status` endpoint to verify API health
- **Deployment Guide**: Review `php-api/DEPLOYMENT.md` for setup instructions
