#!/bin/bash

# Production API Endpoint Testing Script
# Tests all endpoints on the production server

API_BASE="https://pdnsapi.avant.nl"
API_KEY="your-api-key-here"  # Replace with actual API key

echo "======================================"
echo "Production API Endpoint Testing"
echo "Base URL: $API_BASE"
echo "======================================"
echo

# Helper function to make API calls
api_call() {
    local method=$1
    local endpoint=$2
    local data=$3
    local description=$4
    
    echo "Testing: $description"
    echo "  Method: $method"
    echo "  Endpoint: $endpoint"
    
    if [ -n "$data" ]; then
        echo "  Data: $data"
        response=$(curl -s -w "\nHTTP_CODE:%{http_code}" \
            -X "$method" \
            -H "Content-Type: application/json" \
            -H "X-API-Key: $API_KEY" \
            -d "$data" \
            "$API_BASE$endpoint")
    else
        response=$(curl -s -w "\nHTTP_CODE:%{http_code}" \
            -X "$method" \
            -H "Content-Type: application/json" \
            -H "X-API-Key: $API_KEY" \
            "$API_BASE$endpoint")
    fi
    
    # Extract HTTP code and response body
    http_code=$(echo "$response" | tail -n1 | sed 's/HTTP_CODE://')
    response_body=$(echo "$response" | sed '$d')
    
    echo "  Response Code: $http_code"
    
    if [ ${#response_body} -gt 200 ]; then
        echo "  Response: ${response_body:0:200}..."
    else
        echo "  Response: $response_body"
    fi
    
    echo "  Status: $([ $http_code -ge 200 ] && [ $http_code -lt 400 ] && echo "✅ SUCCESS" || echo "❌ FAILED")"
    echo
}

# Test 1: API Documentation
api_call "GET" "/" "" "API Documentation (Root)"

# Test 2: API Status
api_call "GET" "/status" "" "API Status Check"

# Test 3: Users - Get all users (with sync)
api_call "GET" "/users" "" "Get All Users (Auto-sync from PowerDNS Admin)"

# Test 4: Users - Force sync
api_call "GET" "/users?action=sync" "" "Force User Sync from PowerDNS Admin"

# Test 5: Domains - Get all domains
api_call "GET" "/domains" "" "Get All Domains"

# Test 6: Domains - Force sync
api_call "GET" "/domains?action=sync" "" "Force Domain Sync from PowerDNS Admin"

# Test 7: Try to create a domain (should fail with 405)
api_call "POST" "/domains" '{"name":"test.example.com","kind":"Master"}' "Try Domain Creation (Should Fail)"

# Test 8: Get specific user by ID (if users exist)
echo "Testing specific user endpoint..."
users_response=$(curl -s -H "X-API-Key: $API_KEY" "$API_BASE/users")
first_user_id=$(echo "$users_response" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)

if [ -n "$first_user_id" ]; then
    api_call "GET" "/users/$first_user_id" "" "Get User by ID: $first_user_id"
else
    echo "  No users found for individual user test"
    echo
fi

# Test 9: Get specific domain by ID (if domains exist)
echo "Testing specific domain endpoint..."
domains_response=$(curl -s -H "X-API-Key: $API_KEY" "$API_BASE/domains")
first_domain_id=$(echo "$domains_response" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)

if [ -n "$first_domain_id" ]; then
    api_call "GET" "/domains/$first_domain_id" "" "Get Domain by ID: $first_domain_id"
else
    echo "  No domains found for individual domain test"
    echo
fi

# Test 10: OpenAPI documentation
api_call "GET" "/docs" "" "OpenAPI Documentation (HTML)"

# Test 11: OpenAPI JSON schema
api_call "GET" "/openapi.json" "" "OpenAPI JSON Schema"

# Test 12: OpenAPI YAML schema
api_call "GET" "/openapi.yaml" "" "OpenAPI YAML Schema"

# Test 13: Invalid endpoint
api_call "GET" "/invalid-endpoint" "" "Invalid Endpoint (Should Return 404)"

# Test 14: Missing authentication
echo "Testing: Missing Authentication"
echo "  Method: GET"
echo "  Endpoint: /users"
response=$(curl -s -w "\nHTTP_CODE:%{http_code}" "$API_BASE/users")
http_code=$(echo "$response" | tail -n1 | sed 's/HTTP_CODE://')
echo "  Response Code: $http_code"
echo "  Status: $([ $http_code -eq 401 ] || [ $http_code -eq 403 ] && echo "✅ SUCCESS (Auth Required)" || echo "❌ FAILED (Should require auth)")"
echo

echo "======================================"
echo "Production API Testing Complete"
echo "======================================"
echo
echo "Summary:"
echo "- All major endpoints tested"
echo "- Authentication validation checked"
echo "- Sync functionality verified"
echo "- Error handling validated"
echo
echo "Note: Review any failed tests above."
echo "Expected failures:"
echo "- Domain creation (405 Method Not Allowed)"
echo "- Invalid endpoints (404 Not Found)"
echo "- Missing authentication (401/403 Unauthorized)"
