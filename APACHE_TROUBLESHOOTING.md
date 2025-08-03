# Apache Configuration Troubleshooting

This guide helps resolve common Apache configuration issues with the PDNSAdmin PHP API.

## Common Error: "AH01630: client denied by server configuration"

This error occurs when Apache doesn't have proper permissions configured for your web directory.

### Error Example
```
[authz_core:error] [pid 4061409] [client 80.56.129.17:50983] AH01630: client denied by server configuration: /opt/web/pdnsapi.avant.nl
```

### Solutions

#### 1. Check Directory Permissions

Ensure the web directory has proper filesystem permissions:

```bash
# Set ownership to web server user
sudo chown -R www-data:www-data /opt/web/pdnsapi.avant.nl

# Set proper permissions
sudo chmod -R 755 /opt/web/pdnsapi.avant.nl
sudo chmod -R 644 /opt/web/pdnsapi.avant.nl/php-api/*.php
```

#### 2. Update Apache Virtual Host Configuration

Create or update your Apache virtual host configuration:

```apache
<VirtualHost *:80>
    ServerName pdnsapi.avant.nl
    DocumentRoot /opt/web/pdnsapi.avant.nl/php-api
    DirectoryIndex index.php

    # Root directory access
    <Directory /opt/web/pdnsapi.avant.nl>
        Options -Indexes +FollowSymLinks
        AllowOverride None
        Require all granted
    </Directory>

    # API directory access
    <Directory /opt/web/pdnsapi.avant.nl/php-api>
        Options -Indexes +FollowSymLinks +ExecCGI
        AllowOverride All
        Require all granted
        
        # Enable PHP processing
        <FilesMatch "\.php$">
            SetHandler application/x-httpd-php
        </FilesMatch>
    </Directory>

    # Security: Deny access to sensitive directories
    <Directory /opt/web/pdnsapi.avant.nl/php-api/config>
        Require all denied
    </Directory>

    <Directory /opt/web/pdnsapi.avant.nl/php-api/database>
        Require all denied
    </Directory>

    # Enable .htaccess files
    <Directory /opt/web/pdnsapi.avant.nl/php-api>
        AllowOverride All
    </Directory>

    # Logging
    ErrorLog ${APACHE_LOG_DIR}/pdns-api-error.log
    CustomLog ${APACHE_LOG_DIR}/pdns-api-access.log combined
</VirtualHost>
```

#### 3. Enable Required Apache Modules

```bash
sudo a2enmod rewrite
sudo a2enmod php7.4  # or your PHP version
sudo a2enmod headers
sudo systemctl restart apache2
```

#### 4. Check Apache Main Configuration

Ensure the main Apache configuration allows access to your directory. Add to `/etc/apache2/apache2.conf` if needed:

```apache
<Directory /opt/web/>
    Options Indexes FollowSymLinks
    AllowOverride None
    Require all granted
</Directory>
```

#### 5. SELinux Issues (RHEL/CentOS)

If you're using RHEL/CentOS with SELinux enabled:

```bash
# Check SELinux status
sestatus

# Set proper SELinux context
sudo setsebool -P httpd_can_network_connect 1
sudo chcon -R -t httpd_exec_t /opt/web/pdnsapi.avant.nl/php-api/
sudo restorecon -R /opt/web/pdnsapi.avant.nl/
```

### Quick Fix Commands

For your specific path `/opt/web/pdnsapi.avant.nl`:

```bash
# 1. Set proper ownership
sudo chown -R www-data:www-data /opt/web/pdnsapi.avant.nl

# 2. Set proper permissions
sudo chmod -R 755 /opt/web/pdnsapi.avant.nl
sudo find /opt/web/pdnsapi.avant.nl -name "*.php" -exec chmod 644 {} \;

# 3. Create a minimal virtual host
sudo tee /etc/apache2/sites-available/pdnsapi.conf << EOF
<VirtualHost *:80>
    ServerName pdnsapi.avant.nl
    DocumentRoot /opt/web/pdnsapi.avant.nl/php-api
    
    <Directory /opt/web/pdnsapi.avant.nl>
        Options FollowSymLinks
        AllowOverride None
        Require all granted
    </Directory>
    
    <Directory /opt/web/pdnsapi.avant.nl/php-api>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
EOF

# 4. Enable the site
sudo a2ensite pdnsapi.conf
sudo systemctl reload apache2
```

### Testing Access

Test if the issue is resolved:

```bash
# Test local access
curl -I http://localhost/php-api/

# Test with API key
curl -H "X-API-Key: your-api-key" http://pdnsapi.avant.nl/php-api/status

# Check Apache error logs
sudo tail -f /var/log/apache2/error.log
```

### Alternative: Using .htaccess

If you can't modify the virtual host, ensure your `.htaccess` file includes:

```apache
# Enable directory access
Options +FollowSymLinks
RewriteEngine On

# API routing
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Security headers
<IfModule mod_headers.c>
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set X-Content-Type-Options "nosniff"
</IfModule>
```

### Debug Steps

1. **Check Apache error logs:**
   ```bash
   sudo tail -f /var/log/apache2/error.log
   ```

2. **Test PHP processing:**
   ```bash
   echo "<?php phpinfo(); ?>" | sudo tee /opt/web/pdnsapi.avant.nl/php-api/test.php
   curl http://pdnsapi.avant.nl/php-api/test.php
   ```

3. **Verify file permissions:**
   ```bash
   ls -la /opt/web/pdnsapi.avant.nl/php-api/
   ```

4. **Check Apache configuration syntax:**
   ```bash
   sudo apache2ctl configtest
   ```

### HTTPS Configuration

For production, add SSL configuration:

```apache
<VirtualHost *:443>
    ServerName pdnsapi.avant.nl
    DocumentRoot /opt/web/pdnsapi.avant.nl/php-api
    
    SSLEngine on
    SSLCertificateFile /path/to/your/certificate.crt
    SSLCertificateKeyFile /path/to/your/private.key
    
    # Include all the directory configurations from above
</VirtualHost>
```

This should resolve the "client denied by server configuration" error you're experiencing.
