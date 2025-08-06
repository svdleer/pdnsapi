#!/bin/bash

# Manual API Test Script
# Run these commands to test the API functionality

echo "=== PDNSAdmin PHP API Manual Tests ==="
echo "Testing RESTful path parameters and functionality"
echo ""

BASE_URL="https://pdnsapi.avant.nl"

echo "1. Test GET all accounts:"
echo "curl -X GET '$BASE_URL/accounts'"
echo ""

echo "2. Test GET account by ID (RESTful):"
echo "curl -X GET '$BASE_URL/accounts/94'"
echo ""

echo "3. Test GET account by username (RESTful):"
echo "curl -X GET '$BASE_URL/accounts/someusername'"
echo ""

echo "4. Test manual sync (verbose):"
echo "curl -X GET '$BASE_URL/accounts?sync=true'"
echo ""

echo "5. Test create account:"
echo "curl -X POST '$BASE_URL/accounts' \\"
echo "  -H 'Content-Type: application/json' \\"
echo "  -d '{"
echo "    \"username\": \"testuser$(date +%s)\","
echo "    \"plain_text_password\": \"testpass123\","
echo "    \"firstname\": \"Test\","
echo "    \"lastname\": \"User\","
echo "    \"email\": \"test@example.com\","
echo "    \"ip_addresses\": [\"192.168.1.100\", \"2001:db8::1\"],"
echo "    \"customer_id\": 999"
echo "  }'"
echo ""

echo "6. Test update account (replace {id} with actual ID):"
echo "curl -X PUT '$BASE_URL/accounts/{id}' \\"
echo "  -H 'Content-Type: application/json' \\"
echo "  -d '{"
echo "    \"firstname\": \"Updated\","
echo "    \"ip_addresses\": [\"192.168.1.200\"]"
echo "  }'"
echo ""

echo "7. Test delete account (replace {id} with actual ID):"
echo "curl -X DELETE '$BASE_URL/accounts/{id}'"
echo ""

echo "=== Expected Behavior ==="
echo "✅ All endpoints should support RESTful path parameters"
echo "✅ Delete should only affect PowerDNS Admin, local DB synced automatically"
echo "✅ Create/Update/Delete should auto-sync silently"
echo "✅ Manual sync should show verbose output"
echo "✅ Account creation should return proper ID (not 0)"
echo ""

echo "Run the commands above to test the API functionality!"
