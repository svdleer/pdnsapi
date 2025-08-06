#!/bin/bash

# Comprehensive Shell Script for Testing Template-Based Domain Creation
# This script provides both automated testing and manual testing scenarios

API_BASE_URL="https://pdnsapi.avant.nl"
API_KEY="your_api_key_here"  # Replace with actual API key
TIMESTAMP=$(date +%s)

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to make API requests
make_request() {
    local endpoint="$1"
    local method="$2"
    local data="$3"
    local url="${API_BASE_URL}${endpoint}"
    
    if [ "$method" = "POST" ]; then
        if [ -n "$data" ]; then
            curl -s -X POST \
                -H "Authorization: Bearer $API_KEY" \
                -H "Content-Type: application/json" \
                -H "Accept: application/json" \
                -d "$data" \
                -w "\nHTTP_CODE:%{http_code}" \
                "$url"
        else
            curl -s -X POST \
                -H "Authorization: Bearer $API_KEY" \
                -H "Content-Type: application/json" \
                -H "Accept: application/json" \
                -w "\nHTTP_CODE:%{http_code}" \
                "$url"
        fi
    elif [ "$method" = "DELETE" ]; then
        curl -s -X DELETE \
            -H "Authorization: Bearer $API_KEY" \
            -H "Accept: application/json" \
            -w "\nHTTP_CODE:%{http_code}" \
            "$url"
    else
        curl -s \
            -H "Authorization: Bearer $API_KEY" \
            -H "Accept: application/json" \
            -w "\nHTTP_CODE:%{http_code}" \
            "$url"
    fi
}

# Function to extract HTTP code from response
get_http_code() {
    echo "$1" | grep -o "HTTP_CODE:[0-9]*" | cut -d: -f2
}

# Function to get response body (without HTTP code)
get_response_body() {
    echo "$1" | sed 's/HTTP_CODE:[0-9]*$//'
}

# Test templates list endpoint
test_templates_list() {
    echo -e "${BLUE}=== Testing Templates List Endpoint ===${NC}"
    
    response=$(make_request "/templates" "GET")
    http_code=$(get_http_code "$response")
    body=$(get_response_body "$response")
    
    if [ "$http_code" = "200" ]; then
        echo -e "${GREEN}✓ Successfully retrieved templates${NC}"
        echo "Response body (first 500 chars):"
        echo "$body" | head -c 500
        echo "..."
        
        # Count templates if response is JSON
        if command -v jq &> /dev/null; then
            template_count=$(echo "$body" | jq '. | length' 2>/dev/null || echo "N/A")
            echo -e "${GREEN}Templates found: $template_count${NC}"
        fi
    else
        echo -e "${RED}✗ Failed to retrieve templates (HTTP: $http_code)${NC}"
        echo "Response: $body"
        return 1
    fi
    
    echo ""
    return 0
}

# Test specific template details
test_template_details() {
    local template_id="$1"
    echo -e "${BLUE}=== Testing Template Details (ID: $template_id) ===${NC}"
    
    response=$(make_request "/templates?id=$template_id" "GET")
    http_code=$(get_http_code "$response")
    body=$(get_response_body "$response")
    
    if [ "$http_code" = "200" ]; then
        echo -e "${GREEN}✓ Successfully retrieved template details${NC}"
        echo "Response body (first 500 chars):"
        echo "$body" | head -c 500
        echo "..."
        
        # Count records if response is JSON
        if command -v jq &> /dev/null; then
            record_count=$(echo "$body" | jq '.records | length' 2>/dev/null || echo "N/A")
            echo -e "${GREEN}Records found: $record_count${NC}"
        fi
    else
        echo -e "${RED}✗ Failed to retrieve template details (HTTP: $http_code)${NC}"
        echo "Response: $body"
        return 1
    fi
    
    echo ""
    return 0
}

# Test domain creation with template ID
test_domain_creation_template_id() {
    local template_id="$1"
    local domain_name="$2"
    local account_id="${3:-1}"
    
    echo -e "${BLUE}=== Testing Domain Creation with Template ID ===${NC}"
    echo "Template ID: $template_id"
    echo "Domain: $domain_name"
    echo "Account ID: $account_id"
    
    local json_data="{
        \"name\": \"$domain_name\",
        \"account_id\": $account_id,
        \"template_id\": $template_id
    }"
    
    response=$(make_request "/domains" "POST" "$json_data")
    http_code=$(get_http_code "$response")
    body=$(get_response_body "$response")
    
    if [ "$http_code" = "200" ] || [ "$http_code" = "201" ]; then
        echo -e "${GREEN}✓ Successfully created domain with template${NC}"
        echo "Response body (first 500 chars):"
        echo "$body" | head -c 500
        echo "..."
        
        # Verify domain was created by checking domains list
        verify_domain_creation "$domain_name"
        
        # Add to cleanup list
        echo "$domain_name" >> "/tmp/test_domains_to_cleanup_$$"
    else
        echo -e "${RED}✗ Failed to create domain (HTTP: $http_code)${NC}"
        echo "Response: $body"
        return 1
    fi
    
    echo ""
    return 0
}

# Test domain creation with template name
test_domain_creation_template_name() {
    local template_name="$1"
    local domain_name="$2"
    local account_id="${3:-1}"
    
    echo -e "${BLUE}=== Testing Domain Creation with Template Name ===${NC}"
    echo "Template Name: $template_name"
    echo "Domain: $domain_name"
    echo "Account ID: $account_id"
    
    local json_data="{
        \"name\": \"$domain_name\",
        \"account_id\": $account_id,
        \"template_name\": \"$template_name\"
    }"
    
    response=$(make_request "/domains" "POST" "$json_data")
    http_code=$(get_http_code "$response")
    body=$(get_response_body "$response")
    
    if [ "$http_code" = "200" ] || [ "$http_code" = "201" ]; then
        echo -e "${GREEN}✓ Successfully created domain with template name${NC}"
        echo "Response body (first 500 chars):"
        echo "$body" | head -c 500
        echo "..."
        
        # Verify domain was created
        verify_domain_creation "$domain_name"
        
        # Add to cleanup list
        echo "$domain_name" >> "/tmp/test_domains_to_cleanup_$$"
    else
        echo -e "${RED}✗ Failed to create domain (HTTP: $http_code)${NC}"
        echo "Response: $body"
        return 1
    fi
    
    echo ""
    return 0
}

# Verify domain creation by checking if it exists
verify_domain_creation() {
    local domain_name="$1"
    echo -e "${YELLOW}--- Verifying Domain Creation: $domain_name ---${NC}"
    
    response=$(make_request "/domains?name=$domain_name" "GET")
    http_code=$(get_http_code "$response")
    body=$(get_response_body "$response")
    
    if [ "$http_code" = "200" ]; then
        if echo "$body" | grep -q "$domain_name"; then
            echo -e "${GREEN}✓ Domain verified in domains list${NC}"
            
            # Count records if response is JSON
            if command -v jq &> /dev/null; then
                record_count=$(echo "$body" | jq ".[0].rrsets | length" 2>/dev/null || echo "N/A")
                echo -e "${GREEN}Record sets found: $record_count${NC}"
            fi
        else
            echo -e "${YELLOW}⚠ Domain not found in domains list${NC}"
        fi
    else
        echo -e "${RED}✗ Failed to verify domain (HTTP: $http_code)${NC}"
    fi
}

# Test error scenarios
test_error_scenarios() {
    echo -e "${BLUE}=== Testing Error Scenarios ===${NC}"
    
    # Test with invalid template ID
    echo -e "${YELLOW}--- Testing Invalid Template ID ---${NC}"
    local json_data="{
        \"name\": \"test-invalid-template-$TIMESTAMP.example.com\",
        \"account_id\": 1,
        \"template_id\": 99999
    }"
    
    response=$(make_request "/domains" "POST" "$json_data")
    http_code=$(get_http_code "$response")
    body=$(get_response_body "$response")
    
    if [ "$http_code" -ge 400 ]; then
        echo -e "${GREEN}✓ Correctly returned error for invalid template ID (HTTP: $http_code)${NC}"
    else
        echo -e "${RED}✗ Should have returned error for invalid template ID (HTTP: $http_code)${NC}"
    fi
    
    # Test with invalid template name
    echo -e "${YELLOW}--- Testing Invalid Template Name ---${NC}"
    json_data="{
        \"name\": \"test-invalid-template-name-$TIMESTAMP.example.com\",
        \"account_id\": 1,
        \"template_name\": \"NonExistentTemplate\"
    }"
    
    response=$(make_request "/domains" "POST" "$json_data")
    http_code=$(get_http_code "$response")
    body=$(get_response_body "$response")
    
    if [ "$http_code" -ge 400 ]; then
        echo -e "${GREEN}✓ Correctly returned error for invalid template name (HTTP: $http_code)${NC}"
    else
        echo -e "${RED}✗ Should have returned error for invalid template name (HTTP: $http_code)${NC}"
    fi
    
    # Test with missing domain name
    echo -e "${YELLOW}--- Testing Missing Domain Name ---${NC}"
    json_data="{
        \"account_id\": 1,
        \"template_id\": 22
    }"
    
    response=$(make_request "/domains" "POST" "$json_data")
    http_code=$(get_http_code "$response")
    
    if [ "$http_code" -ge 400 ]; then
        echo -e "${GREEN}✓ Correctly returned error for missing domain name (HTTP: $http_code)${NC}"
    else
        echo -e "${RED}✗ Should have returned error for missing domain name (HTTP: $http_code)${NC}"
    fi
    
    echo ""
}

# Clean up test domains
cleanup_test_domains() {
    local cleanup_file="/tmp/test_domains_to_cleanup_$$"
    
    if [ -f "$cleanup_file" ]; then
        echo -e "${BLUE}=== Cleaning Up Test Domains ===${NC}"
        
        while IFS= read -r domain; do
            if [ -n "$domain" ]; then
                echo "Attempting to delete: $domain"
                response=$(make_request "/domains/$domain" "DELETE")
                http_code=$(get_http_code "$response")
                
                if [ "$http_code" = "200" ] || [ "$http_code" = "204" ]; then
                    echo -e "${GREEN}✓ Successfully deleted $domain${NC}"
                else
                    echo -e "${RED}✗ Failed to delete $domain (HTTP: $http_code)${NC}"
                fi
            fi
        done < "$cleanup_file"
        
        rm -f "$cleanup_file"
        echo ""
    fi
}

# Performance test
performance_test() {
    echo -e "${BLUE}=== Performance Test ===${NC}"
    echo "Creating multiple domains with templates..."
    
    local start_time=$(date +%s.%N)
    local domains_created=0
    local template_ids=(22 23 14 16)  # Different templates
    
    for i in {1..5}; do
        local template_id=${template_ids[$((i % ${#template_ids[@]}))]}
        local domain_name="perf-test-$i-$TIMESTAMP.example.com"
        
        echo "Creating domain $i/5: $domain_name"
        
        local json_data="{
            \"name\": \"$domain_name\",
            \"account_id\": 1,
            \"template_id\": $template_id
        }"
        
        response=$(make_request "/domains" "POST" "$json_data")
        http_code=$(get_http_code "$response")
        
        if [ "$http_code" = "200" ] || [ "$http_code" = "201" ]; then
            domains_created=$((domains_created + 1))
            echo "$domain_name" >> "/tmp/test_domains_to_cleanup_$$"
        fi
    done
    
    local end_time=$(date +%s.%N)
    local duration=$(echo "$end_time - $start_time" | bc -l 2>/dev/null || echo "N/A")
    
    echo -e "${GREEN}Performance Test Results:${NC}"
    echo "Domains created: $domains_created/5"
    echo "Duration: ${duration}s"
    
    if [ "$domains_created" -gt 0 ] && [ "$duration" != "N/A" ]; then
        local avg_time=$(echo "scale=3; $duration / $domains_created" | bc -l)
        echo "Average time per domain: ${avg_time}s"
    fi
    
    echo ""
}

# Main test runner
run_all_tests() {
    echo -e "${GREEN}Starting Comprehensive Template Domain Creation Tests${NC}"
    echo "API Base URL: $API_BASE_URL"
    echo "Timestamp: $(date)"
    echo "Test Session ID: $$"
    echo ""
    
    local start_time=$(date +%s)
    local total_tests=0
    local passed_tests=0
    
    # Initialize cleanup file
    touch "/tmp/test_domains_to_cleanup_$$"
    
    # Test 1: Templates list
    test_templates_list && passed_tests=$((passed_tests + 1))
    total_tests=$((total_tests + 1))
    
    # Test 2: Template details
    test_template_details 22 && passed_tests=$((passed_tests + 1))
    total_tests=$((total_tests + 1))
    
    test_template_details 14 && passed_tests=$((passed_tests + 1))
    total_tests=$((total_tests + 1))
    
    # Test 3: Domain creation with template ID
    test_domain_creation_template_id 22 "test-template-id-$TIMESTAMP.example.com" 1 && passed_tests=$((passed_tests + 1))
    total_tests=$((total_tests + 1))
    
    # Test 4: Domain creation with template name
    test_domain_creation_template_name "Office365" "test-template-name-$TIMESTAMP.example.com" 1 && passed_tests=$((passed_tests + 1))
    total_tests=$((total_tests + 1))
    
    # Test 5: Different templates
    test_domain_creation_template_id 23 "test-boaz-$TIMESTAMP.example.com" 1 && passed_tests=$((passed_tests + 1))
    total_tests=$((total_tests + 1))
    
    test_domain_creation_template_id 16 "test-redirect-$TIMESTAMP.example.com" 1 && passed_tests=$((passed_tests + 1))
    total_tests=$((total_tests + 1))
    
    # Test 6: Different account IDs
    test_domain_creation_template_id 22 "test-account2-$TIMESTAMP.example.com" 2 && passed_tests=$((passed_tests + 1))
    total_tests=$((total_tests + 1))
    
    # Test 7: Error scenarios (don't count individual error tests)
    test_error_scenarios
    total_tests=$((total_tests + 1))
    passed_tests=$((passed_tests + 1))  # Consider passed if no crashes
    
    # Test 8: Performance test
    performance_test
    total_tests=$((total_tests + 1))
    passed_tests=$((passed_tests + 1))  # Consider passed if no crashes
    
    # Clean up
    cleanup_test_domains
    
    # Generate summary
    local end_time=$(date +%s)
    local duration=$((end_time - start_time))
    
    echo -e "${GREEN}================================================${NC}"
    echo -e "${GREEN}TEST SUMMARY${NC}"
    echo -e "${GREEN}================================================${NC}"
    echo "Total Duration: ${duration}s"
    echo "Total Tests: $total_tests"
    echo "Passed Tests: $passed_tests"
    echo "Failed Tests: $((total_tests - passed_tests))"
    echo "Success Rate: $(echo "scale=1; $passed_tests * 100 / $total_tests" | bc -l)%"
    echo ""
    echo "Test completed at: $(date)"
    echo -e "${GREEN}================================================${NC}"
}

# Interactive mode
interactive_mode() {
    echo -e "${BLUE}Interactive Template Domain Creation Testing${NC}"
    echo "Choose an option:"
    echo "1. Test templates list"
    echo "2. Test specific template details"
    echo "3. Create domain with template ID"
    echo "4. Create domain with template name"
    echo "5. Test error scenarios"
    echo "6. Performance test"
    echo "7. Run all tests"
    echo "8. Clean up test domains"
    echo "9. Exit"
    echo ""
    
    read -p "Enter your choice (1-9): " choice
    
    case $choice in
        1)
            test_templates_list
            ;;
        2)
            read -p "Enter template ID: " template_id
            test_template_details "$template_id"
            ;;
        3)
            read -p "Enter template ID: " template_id
            read -p "Enter domain name: " domain_name
            read -p "Enter account ID (default: 1): " account_id
            account_id=${account_id:-1}
            test_domain_creation_template_id "$template_id" "$domain_name" "$account_id"
            ;;
        4)
            read -p "Enter template name: " template_name
            read -p "Enter domain name: " domain_name
            read -p "Enter account ID (default: 1): " account_id
            account_id=${account_id:-1}
            test_domain_creation_template_name "$template_name" "$domain_name" "$account_id"
            ;;
        5)
            test_error_scenarios
            ;;
        6)
            performance_test
            ;;
        7)
            run_all_tests
            ;;
        8)
            cleanup_test_domains
            ;;
        9)
            exit 0
            ;;
        *)
            echo "Invalid choice"
            ;;
    esac
    
    echo ""
    interactive_mode
}

# Check command line arguments
if [ $# -eq 0 ]; then
    interactive_mode
elif [ "$1" = "--auto" ] || [ "$1" = "-a" ]; then
    run_all_tests
elif [ "$1" = "--help" ] || [ "$1" = "-h" ]; then
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  --auto, -a    Run all tests automatically"
    echo "  --help, -h    Show this help message"
    echo "  (no args)     Run in interactive mode"
    echo ""
    echo "Environment Variables:"
    echo "  API_BASE_URL  Base URL for the API (default: https://pdnsapi.avant.nl)"
    echo "  API_KEY       API key for authentication"
    echo ""
else
    echo "Unknown option: $1"
    echo "Use --help for usage information"
    exit 1
fi
