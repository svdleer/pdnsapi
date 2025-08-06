#!/bin/bash

# Comprehensive Test Script for /accounts Endpoint
# Usage: ./test-accounts.sh [base_url]

# Set default base URL if not provided
BASE_URL="${1:-http://localhost/php-api}"

echo "========================================"
echo "  Accounts Endpoint Test Runner"
echo "========================================"
echo "Base URL: $BASE_URL"
echo "Started: $(date)"
echo "========================================"
echo ""

# Check if PHP is available
if ! command -v php &> /dev/null; then
    echo "❌ ERROR: PHP is not installed or not in PATH"
    exit 1
fi

# Check if curl is available
if ! command -v curl &> /dev/null; then
    echo "❌ ERROR: curl is not installed or not in PATH"
    exit 1
fi

# Test basic connectivity first
echo "🔍 Testing basic connectivity..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/accounts" || echo "000")

if [ "$HTTP_CODE" = "000" ]; then
    echo "❌ ERROR: Cannot connect to $BASE_URL/accounts"
    echo "   Please check if the server is running and accessible"
    exit 1
elif [ "$HTTP_CODE" -ge 500 ]; then
    echo "⚠️  WARNING: Server returned HTTP $HTTP_CODE - there may be server issues"
    echo "   Continuing with tests..."
elif [ "$HTTP_CODE" -ge 400 ]; then
    echo "⚠️  INFO: Server returned HTTP $HTTP_CODE - this may be expected (auth required, etc.)"
    echo "   Continuing with tests..."
else
    echo "✅ Basic connectivity OK (HTTP $HTTP_CODE)"
fi

echo ""

# Run the comprehensive PHP test script
echo "🚀 Starting comprehensive test suite..."
echo ""

if [ -f "test-accounts-endpoint.php" ]; then
    php test-accounts-endpoint.php "$BASE_URL"
    TEST_EXIT_CODE=$?
else
    echo "❌ ERROR: test-accounts-endpoint.php not found"
    echo "   Please ensure the test script is in the current directory"
    exit 1
fi

echo ""
echo "========================================"
echo "  Test Summary"
echo "========================================"

if [ $TEST_EXIT_CODE -eq 0 ]; then
    echo "✅ Test suite completed successfully"
else
    echo "❌ Test suite completed with errors (exit code: $TEST_EXIT_CODE)"
fi

echo "Finished: $(date)"
echo "========================================"

# Additional manual test commands
echo ""
echo "🔧 Manual Test Commands (JSON-only API):"
echo "========================================"
echo ""

echo "# Test GET all accounts"
echo "curl -X GET \"$BASE_URL/accounts\" -H \"Content-Type: application/json\" | jq"
echo ""

echo "# Test GET with sync"
echo "curl -X GET \"$BASE_URL/accounts\" \\"
echo "  -H \"Content-Type: application/json\" \\"
echo "  -d '{\"sync\": true}' | jq"
echo ""

echo "# Test POST create account"
echo "curl -X POST \"$BASE_URL/accounts\" \\"
echo "  -H \"Content-Type: application/json\" \\"
echo "  -d '{"
echo "    \"username\": \"testuser_$(date +%s)\","
echo "    \"plain_text_password\": \"TestPassword123!\","
echo "    \"firstname\": \"Test\","
echo "    \"lastname\": \"User\","
echo "    \"email\": \"test@example.com\","
echo "    \"ip_addresses\": [\"192.168.1.100\", \"10.0.0.50\"],"
echo "    \"customer_id\": 1001"
echo "  }' | jq"
echo ""

echo "# Test GET account by ID"
echo "curl -X GET \"$BASE_URL/accounts?id=1\" | jq"
echo ""

echo "# Test GET account by username"
echo "curl -X GET \"$BASE_URL/accounts?username=testuser\" | jq"
echo ""

echo "# Test PUT update account by ID"
echo "curl -X PUT \"$BASE_URL/accounts\" \\"
echo "  -H \"Content-Type: application/json\" \\"
echo "  -d '{"
echo "    \"id\": 1,"
echo "    \"firstname\": \"Updated\","
echo "    \"lastname\": \"Name\","
echo "    \"email\": \"updated@example.com\","
echo "    \"ip_addresses\": [\"192.168.1.200\"]"
echo "  }' | jq"
echo ""

echo "# Test PUT update account by username"
echo "curl -X PUT \"$BASE_URL/accounts\" \\"
echo "  -H \"Content-Type: application/json\" \\"
echo "  -d '{"
echo "    \"username\": \"testuser\","
echo "    \"email\": \"updated@example.com\","
echo "    \"customer_id\": 2001"
echo "  }' | jq"
echo ""

echo "# Test DELETE account by ID"
echo "curl -X DELETE \"$BASE_URL/accounts\" \\"
echo "  -H \"Content-Type: application/json\" \\"
echo "  -d '{\"id\": 1}' | jq"
echo ""

echo "# Test DELETE account by username"
echo "curl -X DELETE \"$BASE_URL/accounts\" \\"
echo "  -H \"Content-Type: application/json\" \\"
echo "  -d '{\"username\": \"testuser\"}' | jq"
echo ""

echo "# Test status endpoint"
echo "curl -X GET \"$BASE_URL/status\" -H \"Content-Type: application/json\" | jq"
echo ""

echo "========================================"
echo "For debugging, check PHP error logs and server logs"
echo "========================================"

exit $TEST_EXIT_CODE
