# IP Security Configuration

## Overview

The PowerDNS Admin API now includes **global IP validation** for all endpoints. This provides defense-in-depth security alongside API key authentication.

## Security Model: Dual Authentication

✅ **API Key** (HTTP Basic Auth) - Required for all endpoints
✅ **IP Allowlist** - Global allowlist applied to ALL endpoints  

Both requirements must be met for API access.

## Current Configuration

**IP Validation Status:** ✅ ENABLED

**Allowed IP Addresses:**
- `127.0.0.1` - localhost
- `::1` - localhost IPv6  
- `149.210.167.40` - server primary IP
- `149.210.166.5` - server secondary IP
- `2a01:7c8:aab3:5d8:149:210:166:5` - server IPv6
- `192.168.1.0/24` - local network example
- `10.0.0.0/8` - private network example

## Management

### List Current IPs
```bash
php manage-ips-clean.php list
```

### Test IP Access
```bash
php manage-ips-clean.php test 192.168.1.100
php manage-ips-clean.php test 2001:db8::1
```

### Add IP (Manual Process)
```bash
php manage-ips-clean.php add 203.0.113.10
# Follow instructions to manually edit config/config.php
```

## Features

✅ **IPv4 Support** - Single IPs and CIDR ranges  
✅ **IPv6 Support** - Full IPv6 and CIDR notation  
✅ **CIDR Validation** - Supports /24, /32, /64, etc.  
✅ **Enhanced Validation** - Reuses existing robust IP functions  
✅ **Security Logging** - All violations logged  
✅ **Global Application** - Applied to ALL API endpoints  

## Security Benefits

1. **Network-level Protection** - Blocks unauthorized networks
2. **Defense-in-Depth** - API key + IP validation  
3. **Attack Surface Reduction** - Limits potential attackers
4. **Audit Trail** - All access attempts logged
5. **Simple Management** - Single global allowlist

## Error Responses

- **403 Forbidden** - IP not in allowlist
- **401 Unauthorized** - Invalid API key (after IP check passes)

## Configuration File

All IP settings are in: `config/config.php`

```php
$config['security'] = [
    'ip_validation_enabled' => true,
    'allowed_ips' => [
        // Your IPs here
    ],
];
```

## Production Recommendations

1. **Remove example ranges** (192.168.1.0/24, 10.0.0.0/8) from production
2. **Add your admin IPs** to the allowlist  
3. **Monitor security logs** for blocked attempts
4. **Regular IP audits** - remove unused IPs
5. **Document IP changes** for team awareness

## Testing

Always test IP changes before deployment:

```bash
# Test before adding
php manage-ips-clean.php test YOUR_IP

# Add to config
vim config/config.php

# Test after adding  
php manage-ips-clean.php test YOUR_IP
```
