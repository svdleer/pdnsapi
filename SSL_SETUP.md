# SSL/HTTPS Setup Guide for PDNSAdmin PHP API

This guide provides comprehensive instructions for setting up SSL/HTTPS for your PDNSAdmin PHP API with automatic HTTP to HTTPS redirection.

## Overview

The configuration includes:
- HTTP to HTTPS redirection (port 80 → 443)
- Modern SSL/TLS configuration
- Enhanced security headers
- Let's Encrypt support
- HSTS and security policies

## Prerequisites

- Apache with SSL module enabled
- Domain name pointing to your server
- Root or sudo access to the server

## 1. Enable Apache SSL Module

```bash
# Enable required Apache modules
sudo a2enmod ssl
sudo a2enmod rewrite
sudo a2enmod headers
sudo systemctl restart apache2
```

## 2. SSL Certificate Options

### Option A: Let's Encrypt (Recommended - Free)

Install Certbot:
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install certbot python3-certbot-apache

# CentOS/RHEL
sudo yum install certbot python3-certbot-apache
```

Get SSL certificate:
```bash
# Get certificate for your domain
sudo certbot --apache -d pdnsapi.avant.nl

# Or for multiple domains
sudo certbot --apache -d pdnsapi.avant.nl -d www.pdnsapi.avant.nl
```

### Option B: Commercial SSL Certificate

If using a commercial certificate, place files in:
```bash
# Certificate files location
/etc/ssl/certs/pdnsapi.avant.nl.crt          # Main certificate
/etc/ssl/private/pdnsapi.avant.nl.key        # Private key
/etc/ssl/certs/pdnsapi.avant.nl-chain.crt    # Certificate chain
```

### Option C: Self-Signed Certificate (Development Only)

```bash
# Create self-signed certificate
sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/ssl/private/pdnsapi.avant.nl.key \
    -out /etc/ssl/certs/pdnsapi.avant.nl.crt \
    -subj "/C=NL/ST=State/L=City/O=Organization/CN=pdnsapi.avant.nl"

# Set proper permissions
sudo chmod 600 /etc/ssl/private/pdnsapi.avant.nl.key
sudo chmod 644 /etc/ssl/certs/pdnsapi.avant.nl.crt
```

## 3. Apache Virtual Host Configuration

Copy the SSL configuration:
```bash
# Copy the SSL-enabled configuration
sudo cp /opt/web/pdnsadpi.avant.nl/php-api/apache.conf.example /etc/apache2/sites-available/pdnsapi-ssl.conf

# Edit the configuration to match your setup
sudo nano /etc/apache2/sites-available/pdnsapi-ssl.conf
```

Update certificate paths in the configuration if needed:
```apache
# Update these paths to match your certificate location
SSLCertificateFile /etc/ssl/certs/pdnsapi.avant.nl.crt
SSLCertificateKeyFile /etc/ssl/private/pdnsapi.avant.nl.key
SSLCertificateChainFile /etc/ssl/certs/pdnsapi.avant.nl-chain.crt
```

## 4. Enable the SSL Site

```bash
# Disable default sites
sudo a2dissite 000-default
sudo a2dissite default-ssl

# Enable your SSL site
sudo a2ensite pdnsapi-ssl.conf

# Test Apache configuration
sudo apache2ctl configtest

# Restart Apache
sudo systemctl restart apache2
```

## 5. Verify SSL Configuration

### Test HTTP to HTTPS Redirect
```bash
curl -I http://pdnsapi.avant.nl/
# Should return: HTTP/1.1 301 Moved Permanently
# Location: https://pdnsapi.avant.nl/
```

### Test HTTPS Access
```bash
curl -I https://pdnsapi.avant.nl/php-api/
# Should return: HTTP/1.1 200 OK
```

### Test API with SSL
```bash
curl -H "X-API-Key: your-api-key" https://pdnsapi.avant.nl/php-api/status
```

## 6. SSL Security Configuration

### Modern SSL/TLS Configuration

The provided configuration includes:
- TLS 1.2 and 1.3 only (disables SSLv2, SSLv3, TLS 1.0, 1.1)
- Modern cipher suites
- Perfect Forward Secrecy
- OCSP Stapling (optional)

### Security Headers

The configuration adds these security headers:
- **HSTS**: Forces HTTPS for 1 year
- **X-Frame-Options**: Prevents clickjacking
- **X-XSS-Protection**: XSS protection
- **X-Content-Type-Options**: MIME type sniffing protection
- **Content-Security-Policy**: Controls resource loading
- **Referrer-Policy**: Controls referrer information

## 7. Let's Encrypt Auto-Renewal

Set up automatic certificate renewal:
```bash
# Test renewal
sudo certbot renew --dry-run

# Add to crontab for automatic renewal
sudo crontab -e

# Add this line to run twice daily
0 12 * * * /usr/bin/certbot renew --quiet
```

## 8. Firewall Configuration

Ensure your firewall allows HTTPS traffic:
```bash
# UFW (Ubuntu)
sudo ufw allow 'Apache Full'
sudo ufw allow 443/tcp

# iptables
sudo iptables -A INPUT -p tcp --dport 443 -j ACCEPT

# firewalld (CentOS/RHEL)
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

## 9. Testing SSL Configuration

### Online SSL Testers
- [SSL Labs SSL Test](https://www.ssllabs.com/ssltest/)
- [SSL Checker](https://www.sslchecker.com/)

### Command Line Testing
```bash
# Test SSL certificate
openssl s_client -connect pdnsapi.avant.nl:443 -servername pdnsapi.avant.nl

# Test specific TLS version
openssl s_client -connect pdnsapi.avant.nl:443 -tls1_2

# Check certificate expiration
openssl s_client -connect pdnsapi.avant.nl:443 2>/dev/null | openssl x509 -noout -dates
```

## 10. Troubleshooting

### Common Issues

1. **"SSLStaplingCache cannot occur within VirtualHost section"**
   ```bash
   # Move SSLStaplingCache to main Apache config
   echo 'SSLStaplingCache "shmcb:${APACHE_RUN_DIR}/ssl_stapling(32768)"' | sudo tee /etc/apache2/conf-available/ssl-stapling.conf
   sudo a2enconf ssl-stapling
   sudo systemctl reload apache2
   ```

2. **Certificate Not Found**
   ```bash
   # Check certificate files exist
   ls -la /etc/ssl/certs/pdnsapi.avant.nl*
   ls -la /etc/ssl/private/pdnsapi.avant.nl*
   ```

3. **Permission Denied**
   ```bash
   # Fix certificate permissions
   sudo chmod 644 /etc/ssl/certs/pdnsapi.avant.nl.crt
   sudo chmod 600 /etc/ssl/private/pdnsapi.avant.nl.key
   sudo chown root:root /etc/ssl/certs/pdnsapi.avant.nl.crt
   sudo chown root:ssl-cert /etc/ssl/private/pdnsapi.avant.nl.key
   ```

4. **Mixed Content Warnings**
   - Ensure all API calls use HTTPS
   - Update any hardcoded HTTP URLs in your application

5. **Certificate Chain Issues**
   ```bash
   # Test certificate chain
   openssl verify -CAfile /etc/ssl/certs/ca-certificates.crt /etc/ssl/certs/pdnsapi.avant.nl.crt
   ```

### Apache Error Log
```bash
# Check SSL-specific errors
sudo tail -f /var/log/apache2/pdns-api-ssl-error.log
sudo tail -f /var/log/apache2/error.log | grep ssl
```

## 11. Production Checklist

- [ ] SSL certificate from trusted CA (not self-signed)
- [ ] HTTP to HTTPS redirect working
- [ ] Security headers properly configured
- [ ] HSTS enabled
- [ ] Auto-renewal configured (for Let's Encrypt)
- [ ] Firewall allows HTTPS traffic
- [ ] API endpoints accessible over HTTPS
- [ ] SSL Labs test shows A+ rating

## 12. Performance Optimization

### Enable HTTP/2
```apache
# Add to your SSL virtual host
Protocols h2 http/1.1
```

### SSL Session Caching
```apache
# Add to Apache main config
SSLSessionCache shmcb:/var/cache/apache2/ssl_scache(512000)
SSLSessionCacheTimeout 300
```

### OCSP Stapling Configuration

**Important**: The OCSP Stapling cache must be configured globally, not within VirtualHost sections.

Add to main Apache configuration (usually `/etc/apache2/apache2.conf` or `/etc/apache2/conf-available/ssl-stapling.conf`):
```apache
# Global OCSP Stapling configuration
SSLStaplingCache "shmcb:${APACHE_RUN_DIR}/ssl_stapling(32768)"
```

Then in your VirtualHost:
```apache
# Enable OCSP Stapling (cache configured globally)
SSLUseStapling On
```

To enable the configuration:
```bash
# If using a separate config file
sudo a2enconf ssl-stapling
sudo systemctl reload apache2
```

## 13. Security Recommendations

1. **Regular Updates**: Keep certificates up to date
2. **Monitor Expiration**: Set up alerts for certificate expiration
3. **Security Scans**: Regular SSL configuration testing
4. **Backup Certificates**: Securely backup private keys
5. **Access Logs**: Monitor HTTPS access patterns

## Example Configuration Summary

Your final configuration provides:
- Automatic HTTP→HTTPS redirect on port 80
- Secure HTTPS API on port 443
- Modern TLS 1.2/1.3 encryption
- Enhanced security headers
- Perfect Forward Secrecy
- HSTS protection
- API key authentication over encrypted connections

This ensures your PDNSAdmin PHP API is accessible securely with enterprise-grade SSL/TLS protection.
