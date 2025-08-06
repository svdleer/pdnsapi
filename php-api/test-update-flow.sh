#!/bin/bash

echo "=== Testing Account Update Flow ==="
echo ""

API_BASE="https://pdnsapi.avant.nl"
TEST_USERNAME="test_update_$(date +%s)"

echo "Step 1: Creating test account '$TEST_USERNAME'..."

# Create account
CREATE_RESPONSE=$(curl -s -X POST "$API_BASE/accounts" \
  -H "Content-Type: application/json" \
  -d "{
    \"username\": \"$TEST_USERNAME\",
    \"plain_text_password\": \"TestPass123!\",
    \"firstname\": \"Test\",
    \"lastname\": \"Update\",
    \"email\": \"$TEST_USERNAME@test.com\",
    \"role_id\": 2,
    \"ip_addresses\": [\"192.168.1.100\"],
    \"customer_id\": 123
  }")

echo "Create response: $CREATE_RESPONSE"

# Extract account ID
ACCOUNT_ID=$(echo $CREATE_RESPONSE | grep -o '"id":"[0-9]*"' | cut -d'"' -f4)

if [ -z "$ACCOUNT_ID" ]; then
    echo "✗ Failed to create account"
    exit 1
fi

echo "✓ Account created successfully (ID: $ACCOUNT_ID)"
echo ""

echo "Step 2: Testing update with PowerDNS Admin fields (firstname, lastname, email)..."

# Update PowerDNS Admin fields
UPDATE_RESPONSE_1=$(curl -s -X PUT "$API_BASE/accounts?id=$ACCOUNT_ID" \
  -H "Content-Type: application/json" \
  -d "{
    \"firstname\": \"Updated\",
    \"lastname\": \"Name\",
    \"email\": \"updated_$TEST_USERNAME@test.com\"
  }")

echo "Update PowerDNS Admin fields response: $UPDATE_RESPONSE_1"
echo ""

echo "Step 3: Testing update with local-only fields (ip_addresses, customer_id)..."

# Update local-only fields
UPDATE_RESPONSE_2=$(curl -s -X PUT "$API_BASE/accounts?id=$ACCOUNT_ID" \
  -H "Content-Type: application/json" \
  -d "{
    \"ip_addresses\": [\"192.168.1.101\", \"192.168.1.102\"],
    \"customer_id\": 456
  }")

echo "Update local fields response: $UPDATE_RESPONSE_2"
echo ""

echo "Step 4: Testing mixed update (both PowerDNS Admin and local fields)..."

# Update both types of fields
UPDATE_RESPONSE_3=$(curl -s -X PUT "$API_BASE/accounts?id=$ACCOUNT_ID" \
  -H "Content-Type: application/json" \
  -d "{
    \"firstname\": \"Final\",
    \"email\": \"final_$TEST_USERNAME@test.com\",
    \"ip_addresses\": [\"192.168.1.200\"],
    \"customer_id\": 789
  }")

echo "Update mixed fields response: $UPDATE_RESPONSE_3"
echo ""

echo "Step 5: Verifying final account state..."

# Get final account state
FINAL_RESPONSE=$(curl -s "$API_BASE/accounts?id=$ACCOUNT_ID")
echo "Final account state: $FINAL_RESPONSE"
echo ""

echo "Step 6: Cleaning up - deleting test account..."

# Delete test account
DELETE_RESPONSE=$(curl -s -X DELETE "$API_BASE/accounts?id=$ACCOUNT_ID")
echo "Delete response: $DELETE_RESPONSE"

echo ""
echo "=== Update Flow Test Completed ==="
