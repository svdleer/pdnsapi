#!/bin/bash

# Simple deployment script for PowerDNS Admin PHP API
# This script should be run on the remote server (pdnsapi.avant.nl)

echo "=== PowerDNS Admin PHP API Deployment Script ==="
echo "Starting deployment at $(date)"

# Get the current directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
API_DIR="$SCRIPT_DIR"

echo "API Directory: $API_DIR"

# Check if we're in a git repository
if [ ! -d ".git" ]; then
    echo "ERROR: Not in a git repository. Please run this script from the API root directory."
    exit 1
fi

# Stash any local changes (if any)
echo "Stashing any local changes..."
git stash

# Pull the latest changes from main branch
echo "Pulling latest changes from origin/main..."
git pull origin main

if [ $? -ne 0 ]; then
    echo "ERROR: Failed to pull latest changes from git"
    exit 1
fi

# Check if Apache/web server needs to be restarted (optional)
# Uncomment the following lines if you want to restart Apache
# echo "Restarting Apache web server..."
# sudo systemctl restart apache2

echo "=== Deployment completed successfully at $(date) ==="
echo ""
echo "Available endpoints after deployment:"
echo "  - GET  /api/accounts          (List all accounts)"
echo "  - POST /api/accounts          (Create new account)"  
echo "  - GET  /api/accounts?id=X     (Get account by ID)"
echo "  - GET  /api/accounts?name=X   (Get account by name)"
echo "  - GET  /api/accounts?sync=true (Sync from PowerDNS Admin DB)"
echo "  - PUT  /api/accounts?id=X     (Update account)"
echo "  - DELETE /api/accounts?id=X   (Delete account)"
echo ""
echo "Test the deployment:"
echo "  curl -X GET https://pdnsapi.avant.nl/api/accounts"
echo ""
