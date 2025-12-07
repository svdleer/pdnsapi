# Deployment Steps for Account-Scoped API Keys

## On pdnsapi.avant.nl server:

1. **Pull the latest changes:**
   ```bash
   cd /path/to/pdnsapi
   git pull origin main
   ```

2. **Create the api_keys table:**
   ```bash
   mysql -u $(grep API_DB_USER .env | cut -d '=' -f2) \
         -p$(grep API_DB_PASS .env | cut -d '=' -f2) \
         $(grep API_DB_NAME .env | cut -d '=' -f2) \
         < database/add_api_keys_table.sql
   ```

3. **Verify the table was created:**
   ```bash
   mysql -u $(grep API_DB_USER .env | cut -d '=' -f2) \
         -p$(grep API_DB_PASS .env | cut -d '=' -f2) \
         $(grep API_DB_NAME .env | cut -d '=' -f2) \
         -e "DESCRIBE api_keys;"
   ```

4. **Test the new endpoint:**
   ```bash
   # Get admin key from .env
   ADMIN_KEY=$(grep AVANT_API_KEY .env | cut -d '=' -f2)
   
   # Create a test API key for account 5
   curl -X POST "https://pdnsapi.avant.nl/api/api-keys" \
     -H "Content-Type: application/json" \
     -H "X-API-Key: $ADMIN_KEY" \
     -d '{
       "account_id": 5,
       "description": "Test account-scoped key",
       "permissions": {
         "domains": "rw",
         "create_domains": true,
         "delete_domains": false
       }
     }'
   ```

5. **Test using the account-scoped key:**
   ```bash
   # Use the api_key returned from step 4
   curl -X GET "https://pdnsapi.avant.nl/api/domains" \
     -H "X-API-Key: pdns_YOUR_GENERATED_KEY_HERE"
   ```

## Quick Test Commands:

```bash
# List all API keys
curl -s "https://pdnsapi.avant.nl/api/api-keys" \
  -H "X-API-Key: $ADMIN_KEY" | jq .

# List domains (will be filtered by account if using account-scoped key)
curl -s "https://pdnsapi.avant.nl/api/domains" \
  -H "X-API-Key: YOUR_KEY_HERE" | jq .
```

## Documentation:

See ACCOUNT_SCOPED_API_KEYS.md for complete documentation.
