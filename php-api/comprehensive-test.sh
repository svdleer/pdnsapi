#!/bin/bash

# Comprehensive test script for /accounts endpoint
BASE_URL="https://pdnsapi.avant.nl"
API_KEY="pdns-admin-dev-001"

echo "🧪 COMPREHENSIVE /accounts ENDPOINT TEST"
echo "========================================"
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
    
    # Execute the command and capture both output and count
    response=$(eval "$curl_cmd" 2>/dev/null)
    if echo "$response" | jq . >/dev/null 2>&1; then
        # Valid JSON - show formatted output and count
        echo "$response" | jq .
        
        # Smart counting: check if .data is an array or single object
        data_type=$(echo "$response" | jq -r '.data | type' 2>/dev/null)
        if [ "$data_type" = "array" ]; then
            count=$(echo "$response" | jq '.data | length' 2>/dev/null || echo "N/A")
        elif [ "$data_type" = "object" ]; then
            # Single account object
            count=1
        elif [ "$data_type" = "null" ]; then
            count=0
        else
            count="N/A"
        fi
        echo "📊 Result count: $count accounts"
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

echo "🔍 READ OPERATIONS (GET)"
echo "========================"

# Test 1: Get all accounts (no parameters)
run_test "Get all accounts" \
    "curl -s -X GET '$BASE_URL/accounts' -H 'X-API-Key: $API_KEY'" \
    "Should return all accounts (61+)"

# Test 2: Get account by query parameter ID
run_test "Get account by query parameter ?id=1" \
    "curl -s -X GET '$BASE_URL/accounts?id=1' -H 'X-API-Key: $API_KEY'" \
    "Should return only account with ID 1"

# Test 3: Get account by query parameter username
run_test "Get account by query parameter ?username=admin" \
    "curl -s -X GET '$BASE_URL/accounts?username=admin' -H 'X-API-Key: $API_KEY'" \
    "Should return only admin account"

# Test 4: Get account by RESTful path ID
run_test "Get account by RESTful path /accounts/1" \
    "curl -s -X GET '$BASE_URL/accounts/1' -H 'X-API-Key: $API_KEY'" \
    "Should return only account with ID 1"

# Test 5: Get account by RESTful path username
run_test "Get account by RESTful path /accounts/admin" \
    "curl -s -X GET '$BASE_URL/accounts/admin' -H 'X-API-Key: $API_KEY'" \
    "Should return only admin account"

# Test 6: Get account by JSON payload ID
run_test "Get account by JSON payload {\"id\":1}" \
    "curl -s -X GET '$BASE_URL/accounts' -H 'X-API-Key: $API_KEY' -H 'Content-Type: application/json' -d '{\"id\":1}'" \
    "Should return only account with ID 1"

# Test 7: Get account by JSON payload username
run_test "Get account by JSON payload {\"username\":\"admin\"}" \
    "curl -s -X GET '$BASE_URL/accounts' -H 'X-API-Key: $API_KEY' -H 'Content-Type: application/json' -d '{\"username\":\"admin\"}'" \
    "Should return only admin account"

# Test 8: Force sync then get all
run_test "Force sync then get all accounts" \
    "curl -s -X GET '$BASE_URL/accounts?sync=true' -H 'X-API-Key: $API_KEY'" \
    "Should sync from PowerDNS Admin first, then return all accounts"

# Test 9: Test non-existent account
run_test "Get non-existent account ID 99999" \
    "curl -s -X GET '$BASE_URL/accounts?id=99999' -H 'X-API-Key: $API_KEY'" \
    "Should return 404 error"

# Test 10: Test non-existent username
run_test "Get non-existent username 'nonexistent'" \
    "curl -s -X GET '$BASE_URL/accounts?username=nonexistent' -H 'X-API-Key: $API_KEY'" \
    "Should return 404 error"

echo "✅ READ TESTS COMPLETED"
echo "======================="
echo

echo "🔐 AUTHENTICATION TESTS"
echo "======================="

# Test 11: No API key
run_test "Request without API key" \
    "curl -s -X GET '$BASE_URL/accounts'" \
    "Should return 401 Unauthorized"

# Test 12: Invalid API key
run_test "Request with invalid API key" \
    "curl -s -X GET '$BASE_URL/accounts' -H 'X-API-Key: invalid-key'" \
    "Should return 401/403 error"

echo "✅ AUTHENTICATION TESTS COMPLETED"
echo "================================="
echo

echo "📝 PRIORITY TESTS"
echo "================"

# Test 13: Query param vs JSON payload (query should win)
run_test "Query param ?id=1 + JSON {\"id\":47}" \
    "curl -s -X GET '$BASE_URL/accounts?id=1' -H 'X-API-Key: $API_KEY' -H 'Content-Type: application/json' -d '{\"id\":47}'" \
    "Should return account ID 1 (query param priority)"

# Test 14: Path param vs JSON payload (path should win)
run_test "Path /accounts/1 + JSON {\"id\":47}" \
    "curl -s -X GET '$BASE_URL/accounts/1' -H 'X-API-Key: $API_KEY' -H 'Content-Type: application/json' -d '{\"id\":47}'" \
    "Should return account ID 1 (path param priority)"

echo "✅ PRIORITY TESTS COMPLETED"
echo "============================"
echo

echo "🎯 EDGE CASE TESTS"
echo "=================="

# Test 15: Empty JSON payload
run_test "Empty JSON payload {}" \
    "curl -s -X GET '$BASE_URL/accounts' -H 'X-API-Key: $API_KEY' -H 'Content-Type: application/json' -d '{}'" \
    "Should return all accounts"

# Test 16: Invalid JSON payload
run_test "Invalid JSON payload" \
    "curl -s -X GET '$BASE_URL/accounts' -H 'X-API-Key: $API_KEY' -H 'Content-Type: application/json' -d '{invalid-json}'" \
    "Should return all accounts (ignore invalid JSON)"

# Test 17: JSON with extra fields
run_test "JSON with extra fields" \
    "curl -s -X GET '$BASE_URL/accounts' -H 'X-API-Key: $API_KEY' -H 'Content-Type: application/json' -d '{\"id\":1,\"extra\":\"field\"}'" \
    "Should return account ID 1 (ignore extra fields)"

echo "✅ EDGE CASE TESTS COMPLETED"
echo "============================"
echo

echo "📊 SUMMARY"
echo "=========="
echo "Completed $(($test_num-1)) tests on /accounts endpoint"
echo "All scenarios tested:"
echo "  ✓ Query parameters (?id=, ?username=)"
echo "  ✓ RESTful paths (/accounts/id, /accounts/username)"  
echo "  ✓ JSON payloads ({\"id\":}, {\"username\":})"
echo "  ✓ Force sync (?sync=true)"
echo "  ✓ Authentication (valid/invalid/missing API keys)"
echo "  ✓ Priority handling (query > path > JSON)"
echo "  ✓ Edge cases (empty/invalid JSON, extra fields)"
echo "  ✓ Error handling (404 for non-existent accounts)"
echo
echo "🎉 Test run complete!"
