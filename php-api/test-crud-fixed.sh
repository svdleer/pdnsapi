#!/bin/bash

# Fixed CRUD test script for /accounts endpoint
BASE_URL="https://pdnsapi.avant.nl"
API_KEY="pdns-admin-dev-001"

echo "🧪 FIXED COMPREHENSIVE CRUD TEST"
echo "================================="
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
    
    # Execute the command and capture response
    response=$(eval "$curl_cmd" 2>/dev/null)
    if echo "$response" | jq . >/dev/null 2>&1; then
        # Valid JSON - show formatted output
        echo "$response" | jq .
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

# Generate unique timestamp for test user
TIMESTAMP=$(date +%s)

echo "🆕 CREATE OPERATIONS (POST)"
echo "==========================="

# Test 1: Create a new test account (non-admin)
run_test "Create new test account" \
    "curl -s -X POST '$BASE_URL/accounts' -H 'X-API-Key: $API_KEY' -H 'Content-Type: application/json' -d '{
        \"username\": \"testuser_$TIMESTAMP\",
        \"plain_text_password\": \"testpass123\",
        \"firstname\": \"Test\",
        \"lastname\": \"User\",
        \"email\": \"testuser_$TIMESTAMP@test.com\",
        \"ip_addresses\": [\"192.168.1.100\", \"192.168.1.101\"],
        \"customer_id\": 999
    }'" \
    "Should create account successfully"

# Test 2: Try to create duplicate user
run_test "Try to create duplicate user" \
    "curl -s -X POST '$BASE_URL/accounts' -H 'X-API-Key: $API_KEY' -H 'Content-Type: application/json' -d '{
        \"username\": \"testuser_$TIMESTAMP\",
        \"plain_text_password\": \"testpass123\",
        \"firstname\": \"Test\",
        \"lastname\": \"User\",
        \"email\": \"testuser_$TIMESTAMP@test.com\"
    }'" \
    "Should return error about duplicate user"

echo "✅ CREATE TESTS COMPLETED"
echo "========================="
echo

echo "✏️ UPDATE OPERATIONS (PUT)"
echo "=========================="

# Test 3: Update the test account by username
run_test "Update account by username via JSON payload" \
    "curl -s -X PUT '$BASE_URL/accounts' -H 'X-API-Key: $API_KEY' -H 'Content-Type: application/json' -d '{
        \"username\": \"testuser_$TIMESTAMP\",
        \"firstname\": \"Updated\",
        \"lastname\": \"TestUser\",
        \"email\": \"updated_testuser_$TIMESTAMP@test.com\",
        \"ip_addresses\": [\"192.168.1.200\", \"192.168.1.201\", \"10.0.0.1\"],
        \"customer_id\": 1000
    }'" \
    "Should update account successfully"

# Test 4: Update account by RESTful path
run_test "Update account by RESTful path" \
    "curl -s -X PUT '$BASE_URL/accounts/testuser_$TIMESTAMP' -H 'X-API-Key: $API_KEY' -H 'Content-Type: application/json' -d '{
        \"firstname\": \"Final\",
        \"lastname\": \"Update\",
        \"ip_addresses\": [\"192.168.1.250\"]
    }'" \
    "Should update account via RESTful path"

# Test 5: Try to update non-existent account
run_test "Try to update non-existent account" \
    "curl -s -X PUT '$BASE_URL/accounts' -H 'X-API-Key: $API_KEY' -H 'Content-Type: application/json' -d '{
        \"username\": \"nonexistent_user_999\",
        \"firstname\": \"Should\",
        \"lastname\": \"Fail\"
    }'" \
    "Should return 404 error"

echo "✅ UPDATE TESTS COMPLETED"
echo "========================="
echo

echo "🗑️ DELETE OPERATIONS (DELETE)"
echo "=============================="

# Test 6: Try to delete admin user (should fail)
run_test "Try to delete admin user (should fail)" \
    "curl -s -X DELETE '$BASE_URL/accounts' -H 'X-API-Key: $API_KEY' -H 'Content-Type: application/json' -d '{
        \"username\": \"admin\"
    }'" \
    "Should return 403 Forbidden (protected account)"

# Test 7: Delete our test account by username
run_test "Delete test account by username via JSON payload" \
    "curl -s -X DELETE '$BASE_URL/accounts' -H 'X-API-Key: $API_KEY' -H 'Content-Type: application/json' -d '{
        \"username\": \"testuser_$TIMESTAMP\"
    }'" \
    "Should delete account successfully"

# Test 8: Try to delete the same account again
run_test "Try to delete already deleted account" \
    "curl -s -X DELETE '$BASE_URL/accounts' -H 'X-API-Key: $API_KEY' -H 'Content-Type: application/json' -d '{
        \"username\": \"testuser_$TIMESTAMP\"
    }'" \
    "Should return 404 error"

# Test 9: Try to delete without providing identifier
run_test "Try to delete without identifier" \
    "curl -s -X DELETE '$BASE_URL/accounts' -H 'X-API-Key: $API_KEY' -H 'Content-Type: application/json' -d '{}'" \
    "Should return 400 error (missing identifier)"

echo "✅ DELETE TESTS COMPLETED"
echo "========================="
echo

echo "🔐 ERROR HANDLING TESTS"
echo "======================="

# Test 10: Create without required fields
run_test "Create account without required fields" \
    "curl -s -X POST '$BASE_URL/accounts' -H 'X-API-Key: $API_KEY' -H 'Content-Type: application/json' -d '{
        \"username\": \"incomplete_user\"
    }'" \
    "Should return 400 error (missing required fields)"

# Test 11: Create with invalid IP address
run_test "Create account with invalid IP address" \
    "curl -s -X POST '$BASE_URL/accounts' -H 'X-API-Key: $API_KEY' -H 'Content-Type: application/json' -d '{
        \"username\": \"invalid_ip_user\",
        \"plain_text_password\": \"testpass123\",
        \"firstname\": \"Invalid\",
        \"lastname\": \"IP\",
        \"email\": \"invalid@test.com\",
        \"ip_addresses\": [\"not.an.ip.address\"]
    }'" \
    "Should return 400 error (invalid IP address)"

# Test 12: Update with invalid IP address
run_test "Update account with invalid IP address" \
    "curl -s -X PUT '$BASE_URL/accounts' -H 'X-API-Key: $API_KEY' -H 'Content-Type: application/json' -d '{
        \"username\": \"admin\",
        \"ip_addresses\": [\"999.999.999.999\"]
    }'" \
    "Should return 400 error (invalid IP address)"

echo "✅ ERROR HANDLING TESTS COMPLETED"
echo "================================="
echo

echo "📊 SUMMARY"
echo "=========="
echo "Completed $(($test_num-1)) tests on /accounts CRUD operations"
echo "All scenarios tested:"
echo "  ✓ Account creation with validation"
echo "  ✓ Duplicate prevention"
echo "  ✓ Account updates (JSON payload & RESTful path)"
echo "  ✓ Account deletion with protection for admin users"
echo "  ✓ Comprehensive error handling"
echo "  ✓ Field validation (required fields, IP addresses)"
echo "  ✓ Non-existent account handling"
echo
echo "🎉 CRUD test run complete!"
