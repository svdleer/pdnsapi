#!/bin/bash

# Quick Template Validation Script
# Tests the templates endpoint and basic functionality

API_BASE_URL="https://pdnsapi.avant.nl"
API_KEY="your_api_key_here"  # Replace with actual API key

echo "🔍 Quick Template Validation Test"
echo "================================="
echo "API Base URL: $API_BASE_URL"
echo "Timestamp: $(date)"
echo ""

# Test 1: Templates list
echo "1️⃣ Testing Templates List Endpoint..."
response=$(curl -s \
    -H "Authorization: Bearer $API_KEY" \
    -H "Accept: application/json" \
    -w "\nHTTP_CODE:%{http_code}" \
    "$API_BASE_URL/templates")

http_code=$(echo "$response" | grep -o "HTTP_CODE:[0-9]*" | cut -d: -f2)
body=$(echo "$response" | sed 's/HTTP_CODE:[0-9]*$//')

if [ "$http_code" = "200" ]; then
    echo "✅ Templates endpoint accessible (HTTP $http_code)"
    echo "Response length: $(echo "$body" | wc -c) characters"
    
    # Try to count templates if JSON
    if command -v jq &> /dev/null; then
        template_count=$(echo "$body" | jq '. | length' 2>/dev/null || echo "N/A")
        echo "Templates found: $template_count"
        
        # Show first template
        first_template=$(echo "$body" | jq '.[0]' 2>/dev/null || echo "N/A")
        if [ "$first_template" != "N/A" ] && [ "$first_template" != "null" ]; then
            echo "First template:"
            echo "$first_template"
        fi
    else
        echo "First 200 characters of response:"
        echo "$body" | head -c 200
        echo "..."
    fi
else
    echo "❌ Templates endpoint failed (HTTP $http_code)"
    echo "Response: $body"
fi

echo ""

# Test 2: Specific template details
echo "2️⃣ Testing Template Details (ID: 22)..."
response=$(curl -s \
    -H "Authorization: Bearer $API_KEY" \
    -H "Accept: application/json" \
    -w "\nHTTP_CODE:%{http_code}" \
    "$API_BASE_URL/templates?id=22")

http_code=$(echo "$response" | grep -o "HTTP_CODE:[0-9]*" | cut -d: -f2)
body=$(echo "$response" | sed 's/HTTP_CODE:[0-9]*$//')

if [ "$http_code" = "200" ]; then
    echo "✅ Template details accessible (HTTP $http_code)"
    
    if command -v jq &> /dev/null; then
        record_count=$(echo "$body" | jq '.records | length' 2>/dev/null || echo "N/A")
        echo "Records in template: $record_count"
        
        # Show record types
        record_types=$(echo "$body" | jq -r '.records[].type' 2>/dev/null | sort | uniq | tr '\n' ', ' | sed 's/,$//')
        if [ -n "$record_types" ]; then
            echo "Record types: $record_types"
        fi
    else
        echo "Response length: $(echo "$body" | wc -c) characters"
        echo "First 300 characters:"
        echo "$body" | head -c 300
        echo "..."
    fi
else
    echo "❌ Template details failed (HTTP $http_code)"
    echo "Response: $body"
fi

echo ""

# Test 3: Quick domain creation test
echo "3️⃣ Testing Domain Creation with Template..."
timestamp=$(date +%s)
test_domain="quick-test-$timestamp.example.com"

json_data="{
    \"name\": \"$test_domain\",
    \"account_id\": 1,
    \"template_id\": 22
}"

response=$(curl -s -X POST \
    -H "Authorization: Bearer $API_KEY" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "$json_data" \
    -w "\nHTTP_CODE:%{http_code}" \
    "$API_BASE_URL/domains")

http_code=$(echo "$response" | grep -o "HTTP_CODE:[0-9]*" | cut -d: -f2)
body=$(echo "$response" | sed 's/HTTP_CODE:[0-9]*$//')

if [ "$http_code" = "200" ] || [ "$http_code" = "201" ]; then
    echo "✅ Domain creation successful (HTTP $http_code)"
    echo "Created domain: $test_domain"
    
    # Verify domain exists
    echo "Verifying domain creation..."
    verify_response=$(curl -s \
        -H "Authorization: Bearer $API_KEY" \
        -H "Accept: application/json" \
        "$API_BASE_URL/domains?name=$test_domain")
    
    if echo "$verify_response" | grep -q "$test_domain"; then
        echo "✅ Domain verified in domains list"
        
        # Count records if possible
        if command -v jq &> /dev/null; then
            record_sets=$(echo "$verify_response" | jq '.[0].rrsets | length' 2>/dev/null || echo "N/A")
            echo "Record sets found: $record_sets"
        fi
    else
        echo "⚠️ Domain not found in verification check"
    fi
    
    # Cleanup
    echo "Cleaning up test domain..."
    cleanup_response=$(curl -s -X DELETE \
        -H "Authorization: Bearer $API_KEY" \
        -H "Accept: application/json" \
        -w "\nHTTP_CODE:%{http_code}" \
        "$API_BASE_URL/domains/$test_domain")
    
    cleanup_code=$(echo "$cleanup_response" | grep -o "HTTP_CODE:[0-9]*" | cut -d: -f2)
    if [ "$cleanup_code" = "200" ] || [ "$cleanup_code" = "204" ]; then
        echo "✅ Test domain cleaned up successfully"
    else
        echo "⚠️ Cleanup may have failed (HTTP $cleanup_code)"
    fi
else
    echo "❌ Domain creation failed (HTTP $http_code)"
    echo "Response: $body"
fi

echo ""
echo "🎯 Quick Validation Complete"
echo "============================="
echo "Check the results above for any issues."
echo "If all tests show ✅, the template functionality is working correctly."
