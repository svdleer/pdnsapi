# PowerDNS Admin PHP API Deployment Guide

## Overview
This guide helps you deploy the latest version of the PowerDNS Admin PHP API with the updated `/accounts` endpoints.

## Current Status
- **Local Development**: Code updated with `/accounts` endpoints ✅
- **Remote Server**: Still running old code with `/users` endpoints ❌
- **Database**: May need migration from `users` to `accounts` table

## Deployment Steps

### Step 1: Connect to Remote Server
```bash
# SSH to your remote server (pdnsapi.avant.nl)
ssh user@pdnsapi.avant.nl
```

### Step 2: Navigate to API Directory
```bash
# Go to your API directory (adjust path as needed)
cd /path/to/your/api/directory
# or
cd /var/www/pdnsapi
# or wherever your PHP API files are located
```

### Step 3: Run Deployment Script
```bash
# Run the deployment script
./deploy.sh
```

### Step 4: Run Database Migration
```bash
# Run the database migration to handle table changes
php migrate.php
```

### Step 5: Test the Deployment
```bash
# Test the accounts endpoint
curl -X GET https://pdnsapi.avant.nl/api/accounts

# Test account creation
curl -X POST https://pdnsapi.avant.nl/api/accounts \
  -H "Content-Type: application/json" \
  -d '{"name": "testuser", "description": "Test User", "contact": "Test Contact", "mail": "test@example.com"}'
```

## Manual Deployment (Alternative)

If the deployment script doesn't work, you can deploy manually:

```bash
# 1. Stash local changes
git stash

# 2. Pull latest changes
git pull origin main

# 3. Run migration
php migrate.php

# 4. Restart web server (if needed)
sudo systemctl restart apache2
```

## Troubleshooting

### Issue: "Endpoint not found" with available_endpoints showing /users
**Solution**: The remote server hasn't been updated. Run the deployment steps above.

### Issue: Database connection errors
**Solution**: Check your database configuration in `config/database.php`

### Issue: Permission denied on deployment script
**Solution**: Make the script executable: `chmod +x deploy.sh`

## API Endpoints After Deployment

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/accounts` | List all accounts |
| POST | `/api/accounts` | Create new account |
| GET | `/api/accounts?id=X` | Get account by ID |
| GET | `/api/accounts?name=X` | Get account by name |
| GET | `/api/accounts?sync=true` | Sync from PowerDNS Admin DB |
| PUT | `/api/accounts?id=X` | Update account |
| DELETE | `/api/accounts?id=X` | Delete account |

## Verification

After deployment, verify that:
1. `/api/accounts` returns data (not 404)
2. Error messages show available endpoints include `/accounts` (not `/users`)
3. Database has `accounts` table with proper data
4. Your application can successfully create/read accounts

## Rollback (if needed)

If something goes wrong, you can rollback:
```bash
# Go back to previous commit
git log --oneline  # Find the commit hash before accounts changes
git checkout <previous-commit-hash>

# Or restore from stash
git stash pop
```
