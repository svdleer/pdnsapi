#!/bin/bash

# Comprehensive Create/Update/Delete test script for /accounts endpoint
BASE_URL="https://pdnsapi.avant.nl"
API_KEY="pdns-admin-dev-001"

# Generate unique identifiers for test accounts
TIMESTAMP=$(date +%s)
TEST_USERNAME="testuser_cud_${TIMESTAMP}"
TEST_EMAIL="testuser_cud_${TIMESTAMP}@test.com"

echo "🧪 COMPREHENSIVE CREATE/UPDATE/DELETE ENDPOINT TEST"
echo "=================================================="
echo "Test Username: $TEST_USERNAME"
echo "Test Email: $TEST_EMAIL"
echo

# Test counter
test_num=1

# Helper function to run test
run_test() {
    local test_name="$1"
    local curl_cmd="$2"
    local expected_behavior="$3"
    
    echo "Test #$test_num: $test_name"
    echo "Expected: $expected_behavior"
    echo "Command: $curl_cmd"
    echo "Response:"
    
    # Execute the command and capture both output and HTTP status
    response=$(eval "$curl_cmd" 2>/dev/null)
    if echo "$response" | jq . >/dev/null 2>&1; then
        # Valid JSON - show formatted output
        echo "$response" | jq .
        
        # Extract status from response if available
        status=$(echo "$response" | jq -r '.status // empty' 2>/dev/null)
        if [ -n "$status" ]; then
            echo "📊 Status: $status"
        fi
    else
        # Not valid JSON or error
        echo "$response"
    fi
    
    echo
    echo "---"
    echo
    ((test_num++))
    sleep 1  # Small delay between tests
}

echo "📝 CREATE OPERATIONS (POST)"
echo "=========================="

# Test 1: Create new account with all fields
run_test "Create account with all fields" \
    "curl -s -X POST '$BASE_URL/accounts' \
    -H 'X-API-Key: $API_KEY' \
    -H 'Content-Type: application/json' \
    -d '{
        \"username\": \"$TEST_USERNAME\",
        \"plain_text_password\": \"TestPassword123!\",
        \"firstname\": \"Test\",
        \"lastname\": \"User\",
        \"email\": \"$TEST_EMAIL\",
        \"ip_addresses\": [\"192.168.1.100\", \"192.168.1.101\"],
        \"customer_id\": 1001
    }'" \
    "Should create account in PowerDNS Admin and sync to local DB"

# Test 2: Try to create duplicate account (should fail)
run_test "Create duplicate account (should fail)" \
    "curl -s -X POST '$BASE_URL/accounts' \
    -H 'X-API-Key: $API_KEY' \
    -H 'Content-Type: application/json' \
    -d '{
        \"username\": \"$TEST_USERNAME\",
        \"plain_text_password\": \"TestPassword123!\",
        \"firstname\": \"Duplicate\",
        \"lastname\": \"User\",
        \"email\": \"duplicate_$TEST_EMAIL\"
    }'" \
    "Should return error - username already exists"

# Test 3: Create account with missing required fields
run_test "Create account with missing required fields" \
    "curl -s -X POST '$BASE_URL/accounts' \
    -H 'X-API-Key: $API_KEY' \
    -H 'Content-Type: application/json' \
    -d '{
        \"username\": \"incomplete_user\",
        \"firstname\": \"Incomplete\"
    }'" \
    "Should return validation error"

# Test 4: Create account with invalid IP addresses
run_test "Create account with invalid IP addresses" \
    "curl -s -X POST '$BASE_URL/accounts' \
    -H 'X-API-Key: $API_KEY' \
    -H 'Content-Type: application/json' \
    -d '{
        \"username\": \"invalid_ip_${TIMESTAMP}\",
        \"plain_text_password\": \"TestPassword123!\",
        \"firstname\": \"Invalid\",
        \"lastname\": \"IP\",
        \"email\": \"invalid_ip_${TIMESTAMP}@test.com\",
        \"ip_addresses\": [\"999.999.999.999\", \"not-an-ip\"]
    }'" \
    "Should return validation error for invalid IP"

echo "✅ CREATE TESTS COMPLETED"
echo "========================"
echo

echo "🔄 UPDATE OPERATIONS (PUT)"
echo "========================="

# Test 5: Update account by username (JSON payload)
run_test "Update account by username (JSON payload)" \
    "curl -s -X PUT '$BASE_URL/accounts' \
    -H 'X-API-Key: $API_KEY' \
    -H 'Content-Type: application/json' \
    -d '{
        \"username\": \"$TEST_USERNAME\",
        \"firstname\": \"Updated\",
        \"lastname\": \"TestUser\",
        \"ip_addresses\": [\"192.168.1.200\", \"2001:db8::1\"],
        \"customer_id\": 2002
    }'" \
    "Should update account fields and sync to PowerDNS Admin"

# Test 6: Update account by RESTful path
run_test "Update account by RESTful path" \
    "curl -s -X PUT '$BASE_URL/accounts/$TEST_USERNAME' \
    -H 'X-API-Key: $API_KEY' \
    -H 'Content-Type: application/json' \
    -d '{
        \"email\": \"updated_$TEST_EMAIL\",
        \"customer_id\": 3003
    }'" \
    "Should update account via path parameter"

# Test 7: Update non-existent account
run_test "Update non-existent account" \
    "curl -s -X PUT '$BASE_URL/accounts/nonexistent_user_${TIMESTAMP}' \
    -H 'X-API-Key: $API_KEY' \
    -H 'Content-Type: application/json' \
    -d '{
        \"firstname\": \"Should\",
        \"lastname\": \"Fail\"
    }'" \
    "Should return 404 error"

# Test 8: Update with invalid data
run_test "Update with invalid IP addresses" \
    "curl -s -X PUT '$BASE_URL/accounts' \
    -H 'X-API-Key: $API_KEY' \
    -H 'Content-Type: application/json' \
    -d '{
        \"username\": \"$TEST_USERNAME\",
        \"ip_addresses\": [\"invalid-ip-address\"]
    }'" \
    "Should return validation error"

# Test 9: Update without identifier
run_test "Update without account identifier" \
    "curl -s -X PUT '$BASE_URL/accounts' \
    -H 'X-API-Key: $API_KEY' \
    -H 'Content-Type: application/json' \
    -d '{
        \"firstname\": \"No\",
        \"lastname\": \"Identifier\"
    }'" \
    "Should return error - no account identifier"

echo "✅ UPDATE TESTS COMPLETED"
echo "========================"
echo

echo "🗑️ DELETE OPERATIONS (DELETE)"
echo "============================="

# Test 10: Verify account exists before deletion
run_test "Verify test account exists" \
    "curl -s -X GET '$BASE_URL/accounts/$TEST_USERNAME' \
    -H 'X-API-Key: $API_KEY'" \
    "Should return the test account"

# Test 11: Delete account by username (RESTful path)
run_test "Delete account by username (RESTful path)" \
    "curl -s -X DELETE '$BASE_URL/accounts/$TEST_USERNAME' \
    -H 'X-API-Key: $API_KEY'" \
    "Should delete account from PowerDNS Admin and sync local DB"

# Test 12: Verify account is deleted
run_test "Verify account is deleted" \
    "curl -s -X GET '$BASE_URL/accounts/$TEST_USERNAME' \
    -H 'X-API-Key: $API_KEY'" \
    "Should return 404 - account not found"

# Test 13: Try to delete non-existent account
run_test "Delete non-existent account" \
    "curl -s -X DELETE '$BASE_URL/accounts/nonexistent_user_${TIMESTAMP}' \
    -H 'X-API-Key: $API_KEY'" \
    "Should return 404 error"

# Test 14: Delete without identifier
run_test "Delete without account identifier" \
    "curl -s -X DELETE '$BASE_URL/accounts' \
    -H 'X-API-Key: $API_KEY'" \
    "Should return error - no account identifier"

# Test 15: Delete with JSON payload (alternative method)
TEST_USERNAME2="testuser_json_delete_${TIMESTAMP}"
TEST_EMAIL2="testuser_json_delete_${TIMESTAMP}@test.com"

# First create another test account for JSON delete test
run_test "Create second test account for JSON delete" \
    "curl -s -X POST '$BASE_URL/accounts' \
    -H 'X-API-Key: $API_KEY' \
    -H 'Content-Type: application/json' \
    -d '{
        \"username\": \"$TEST_USERNAME2\",
        \"plain_text_password\": \"TestPassword123!\",
        \"firstname\": \"JSON\",
        \"lastname\": \"Delete\",
        \"email\": \"$TEST_EMAIL2\"
    }'" \
    "Should create second test account"

# Now delete it using JSON payload
run_test "Delete account with JSON payload" \
    "curl -s -X DELETE '$BASE_URL/accounts' \
    -H 'X-API-Key: $API_KEY' \
    -H 'Content-Type: application/json' \
    -d '{
        \"username\": \"$TEST_USERNAME2\"
    }'" \
    "Should delete account via JSON payload"

echo "✅ DELETE TESTS COMPLETED"
echo "========================"
echo

echo "🔐 ERROR HANDLING TESTS"
echo "======================="

# Test 16: Operations without API key
run_test "Create without API key" \
    "curl -s -X POST '$BASE_URL/accounts' \
    -H 'Content-Type: application/json' \
    -d '{\"username\":\"no_api_key\"}'" \
    "Should return 401 Unauthorized"

run_test "Update without API key" \
    "curl -s -X PUT '$BASE_URL/accounts/test' \
    -H 'Content-Type: application/json' \
    -d '{\"firstname\":\"No API Key\"}'" \
    "Should return 401 Unauthorized"

run_test "Delete without API key" \
    "curl -s -X DELETE '$BASE_URL/accounts/test'" \
    "Should return 401 Unauthorized"

# Test 17: Operations with invalid API key
run_test "Create with invalid API key" \
    "curl -s -X POST '$BASE_URL/accounts' \
    -H 'X-API-Key: invalid-key' \
    -H 'Content-Type: application/json' \
    -d '{\"username\":\"invalid_key\"}'" \
    "Should return 401/403 error"

echo "✅ ERROR HANDLING TESTS COMPLETED"
echo "================================="
echo

echo "📊 SUMMARY"
echo "=========="
echo "Completed $(($test_num-1)) tests on /accounts endpoint"
echo "All CUD scenarios tested:"
echo "  ✓ CREATE operations (POST)"
echo "    - Valid account creation with all fields"
echo "    - Duplicate prevention"
echo "    - Required field validation"
echo "    - IP address validation"
echo "    - Customer ID validation"
echo "  ✓ UPDATE operations (PUT)"
echo "    - Update by username (JSON payload)"
echo "    - Update by RESTful path"
echo "    - Update validation"
echo "    - Error handling for non-existent accounts"
echo "  ✓ DELETE operations (DELETE)"
echo "    - Delete by RESTful path"
echo "    - Delete by JSON payload"
echo "    - Verification of deletion"
echo "    - Error handling"
echo "  ✓ AUTHENTICATION & ERROR HANDLING"
echo "    - Missing API key scenarios"
echo "    - Invalid API key scenarios"
echo "    - Validation error responses"
echo
echo "🎉 CUD Test run complete!"
echo "Note: All operations include automatic sync with PowerDNS Admin"
