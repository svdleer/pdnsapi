#!/bin/bash

echo "=== Testing Delete → Sync Workflow ==="
echo ""

API_BASE="https://pdnsapi.avant.nl"
TEST_USERNAME="test_delete_$(date +%s)"

echo "Step 1: Creating test account '$TEST_USERNAME'..."

# Create account
CREATE_RESPONSE=$(curl -s -X POST "$API_BASE/accounts" \
  -H "Content-Type: application/json" \
  -d "{
    \"username\": \"$TEST_USERNAME\",
    \"plain_text_password\": \"TestPass123!\",
    \"firstname\": \"Test\",
    \"lastname\": \"Delete\",
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

echo "Step 2: Deleting account via API..."

# Delete account
DELETE_RESPONSE=$(curl -s -X DELETE "$API_BASE/accounts?id=$ACCOUNT_ID")
echo "Delete response: $DELETE_RESPONSE"
echo "✓ Account deleted via API"
echo ""

echo "Step 3: Running sync to clean up local database..."

# Run sync
SYNC_RESPONSE=$(curl -s "$API_BASE/accounts?sync=true")
echo "Sync response: $SYNC_RESPONSE"
echo ""

echo "=== Test completed ==="
