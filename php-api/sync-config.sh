#!/bin/bash
# Production Config Sync Script
# This script helps sync config changes to production

echo "PDNSAdmin API - Production Config Sync"
echo "======================================"

# Check if we're in the right directory
if [ ! -f "config/config.php.example" ]; then
    echo "Error: Please run this script from the php-api directory"
    exit 1
fi

# Backup existing config
if [ -f "config/config.php" ]; then
    echo "Backing up existing config.php..."
    cp config/config.php config/config.php.backup.$(date +%Y%m%d_%H%M%S)
fi

# Copy example to config
echo "Creating new config.php from example..."
cp config/config.php.example config/config.php

echo ""
echo "✅ Config file updated successfully!"
echo ""
echo "📝 Next steps:"
echo "1. Edit config/config.php with your actual values:"
echo "   - Update 'base_url' with your PDNSAdmin URL"
echo "   - Update 'api_key' with your base64 encoded credentials"
echo ""
echo "2. Test the API:"
echo "   curl https://your-domain.com/php-test.php"
echo "   curl https://your-domain.com/status?action=test_connection"
echo ""
echo "Example base64 encoding:"
echo "echo -n 'username:password' | base64"
