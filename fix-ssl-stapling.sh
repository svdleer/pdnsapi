#!/bin/bash
# Apache SSL OCSP Stapling Fix Script
# 
# This script fixes the "SSLStaplingCache cannot occur within VirtualHost section" error
# by moving the SSLStaplingCache directive to the global Apache configuration.

echo "PDNSAdmin PHP API - Apache SSL OCSP Stapling Fix"
echo "================================================="

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "Error: This script must be run as root (use sudo)"
    exit 1
fi

# Create OCSP Stapling configuration file
echo "Creating global OCSP Stapling configuration..."
cat > /etc/apache2/conf-available/ssl-stapling.conf << 'EOF'
# OCSP Stapling Configuration
# This must be configured globally, not within VirtualHost sections

# OCSP Stapling Cache
SSLStaplingCache "shmcb:${APACHE_RUN_DIR}/ssl_stapling(32768)"

# Optional: OCSP Stapling responder timeout
SSLStaplingResponderTimeout 5

# Optional: OCSP Stapling error cache timeout
SSLStaplingErrorCacheTimeout 600
EOF

# Enable the configuration
echo "Enabling OCSP Stapling configuration..."
a2enconf ssl-stapling

# Test Apache configuration
echo "Testing Apache configuration..."
if apache2ctl configtest; then
    echo "✓ Apache configuration test passed"
    
    # Reload Apache
    echo "Reloading Apache..."
    systemctl reload apache2
    
    if [ $? -eq 0 ]; then
        echo "✓ Apache reloaded successfully"
        echo ""
        echo "OCSP Stapling fix applied successfully!"
        echo ""
        echo "Your VirtualHost configuration should now only contain:"
        echo "  SSLUseStapling On"
        echo ""
        echo "The SSLStaplingCache directive is now configured globally."
    else
        echo "✗ Error reloading Apache"
        exit 1
    fi
else
    echo "✗ Apache configuration test failed"
    echo "Please check your Apache configuration files"
    exit 1
fi

echo ""
echo "Next steps:"
echo "1. Update your VirtualHost configuration to remove SSLStaplingCache"
echo "2. Keep only 'SSLUseStapling On' in your VirtualHost"
echo "3. Test your SSL configuration: curl -I https://your-domain/"
