#!/bin/bash

echo "=== Testing /domains API Endpoints ==="
echo ""

echo "Running PHP test script..."
php test-domains-api.php

echo ""
echo "=== Alternative cURL Tests ==="
echo ""

# Test 1: GET all domains
echo "1. GET /domains (all domains)"
curl -X GET "http://localhost/php-api/api/domains.php" \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     -w "\nHTTP Status: %{http_code}\n" \
     -s
echo ""

# Test 2: GET domains with account filter  
echo "2. GET /domains with account filter"
curl -X GET "http://localhost/php-api/api/domains.php?account_id=1" \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     -w "\nHTTP Status: %{http_code}\n" \
     -s
echo ""

# Test 3: POST sync domains
echo "3. POST /domains (sync)"
curl -X POST "http://localhost/php-api/api/domains.php" \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     -d '{"action":"sync","force":false}' \
     -w "\nHTTP Status: %{http_code}\n" \
     -s
echo ""

# Test 4: Test PowerDNS Admin API directly
echo "4. Direct PowerDNS Admin API test"
echo "NOTE: Update the API key below with your actual key"
curl -X GET "https://dnsadmin.avant.nl/api/v1/servers/1/zones" \
     -H "X-API-Key: your-api-key-here" \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     -w "\nHTTP Status: %{http_code}\n" \
     -s
echo ""

echo "Test completed!"
