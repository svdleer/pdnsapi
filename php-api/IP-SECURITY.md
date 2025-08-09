# IP Security Configuration

## Overview

The PowerDNS Admin API now includes **database-driven global IP validation** for all endpoints. This provides defense-in-depth security alongside API key authentication.

## Security Model: Dual Authentication

✅ **API Key** (HTTP Basic Auth) - Required for all endpoints
✅ **IP Allowlist** - Global allowlist stored in MySQL database and applied to ALL endpoints  

Both requirements must be met for API access.

## Current Configuration

**IP Validation Status:** ✅ ENABLED  
**Storage:** MySQL Database (`ip_allowlist` table)  
**Caching:** In-memory caching for performance  

**Current Allowed IPs:**
- `127.0.0.1` - localhost IPv4
- `::1` - localhost IPv6  
- `149.210.167.40` - server primary IP
- `149.210.166.5` - server secondary IP
- `2a01:7c8:aab3:5d8:149:210:166:5` - server IPv6

## Management

### List Current IPs
```bash
php manage-ips-clean.php list
```

### Add IP
```bash
php manage-ips-clean.php add 203.0.113.10 "Office IP"
php manage-ips-clean.php add 203.0.113.0/24 "Office network"
php manage-ips-clean.php add 2001:db8::/32 "IPv6 network"
```

### Remove IP
```bash
php manage-ips-clean.php remove 203.0.113.10
```

### Enable/Disable IP
```bash
php manage-ips-clean.php disable 203.0.113.10  # Temporarily disable
php manage-ips-clean.php enable 203.0.113.10   # Re-enable
```

### Test IP Access
```bash
php manage-ips-clean.php test 192.168.1.100
php manage-ips-clean.php test 2001:db8::1
```

## Features

✅ **IPv4 Support** - Single IPs and CIDR ranges  
✅ **IPv6 Support** - Full IPv6 and CIDR notation  
✅ **CIDR Validation** - Supports /24, /32, /64, etc.  
✅ **Enhanced Validation** - Robust IP and mask validation  
✅ **Security Logging** - All violations logged  
✅ **Global Application** - Applied to ALL API endpoints  
✅ **Database Storage** - Persistent, manageable via CLI  
✅ **Enable/Disable** - Temporarily disable IPs without deletion  
✅ **Descriptions** - Add notes for each IP entry  
✅ **Performance Caching** - In-memory caching for speed  

## Security Benefits

1. **Network-level Protection** - Blocks unauthorized networks
2. **Defense-in-Depth** - API key + IP validation  
3. **Attack Surface Reduction** - Limits potential attackers
4. **Audit Trail** - All access attempts logged
5. **Dynamic Management** - Add/remove IPs without restarts
6. **Database Integrity** - Persistent, backed up with database

## Error Responses

- **403 Forbidden** - IP not in allowlist
- **401 Unauthorized** - Invalid API key (after IP check passes)

## Database Structure

The IP allowlist is stored in the `ip_allowlist` table:

```sql
CREATE TABLE ip_allowlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    description VARCHAR(255) DEFAULT '',
    enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## Production Recommendations

1. **Remove test IPs** after development is complete
2. **Add your admin IPs** to the allowlist using the CLI tool  
3. **Monitor security logs** for blocked attempts
4. **Regular IP audits** - disable or remove unused IPs
5. **Document IP changes** for team awareness
6. **Database backups** - Include ip_allowlist table in backups

## Testing

Test IP changes easily with the built-in test command:

```bash
# Test if an IP would be allowed
php manage-ips-clean.php test YOUR_IP

# Add IP with description
php manage-ips-clean.php add YOUR_IP "My admin workstation"

# Test again to confirm
php manage-ips-clean.php test YOUR_IP

# List all IPs to verify
php manage-ips-clean.php list
```

## Migration from Config File

If upgrading from the config file-based system:

1. **Backup** your current config/config.php file
2. **Run database migration** - The schema includes INSERT statements for default IPs
3. **Add your custom IPs** using `php manage-ips-clean.php add`
4. **Test access** to ensure your IPs work
5. **Remove old config** - The system now ignores the config file IP list
