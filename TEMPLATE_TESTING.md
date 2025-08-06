# Template Domain Creation Testing Suite

This directory contains extensive testing tools for the template-based domain creation functionality in the PowerDNS Admin API wrapper.

## 🧪 Test Scripts Overview

### 1. `test-template-domain-creation.php`
**Comprehensive PHP Test Suite**

- **Purpose**: Full-featured testing of template-based domain creation
- **Features**:
  - Tests all available templates (currently 11 templates)
  - Template listing and details verification
  - Domain creation with template ID and template name
  - Multi-account testing
  - Error scenario validation
  - Automatic cleanup
  - Performance metrics
  - Detailed reporting

- **Usage**:
  ```bash
  php test-template-domain-creation.php
  ```

### 2. `test-template-domains.sh`
**Interactive Shell Script Suite**

- **Purpose**: Interactive and automated testing with shell script flexibility
- **Features**:
  - Interactive mode for selective testing
  - Automated mode for full test suite
  - Performance testing
  - Color-coded output
  - JSON parsing with jq (optional)
  - Cleanup functionality

- **Usage**:
  ```bash
  # Interactive mode
  ./test-template-domains.sh
  
  # Automated mode
  ./test-template-domains.sh --auto
  
  # Help
  ./test-template-domains.sh --help
  ```

### 3. `test-template-advanced.php`
**Advanced Scenario Testing**

- **Purpose**: Focused testing of specific scenarios and edge cases
- **Features**:
  - Predefined test scenarios for different templates
  - Record type validation
  - Subdomain testing
  - Multiple account testing
  - Template content analysis
  - Error scenario validation

- **Usage**:
  ```bash
  php test-template-advanced.php
  ```

### 4. `quick-template-test.sh`
**Quick Validation Script**

- **Purpose**: Fast validation of core template functionality
- **Features**:
  - Templates list endpoint test
  - Template details verification
  - Single domain creation test
  - Automatic cleanup
  - Minimal dependencies

- **Usage**:
  ```bash
  ./quick-template-test.sh
  ```

## 🔧 Configuration

### API Configuration
All scripts require configuration of:

```php
$api_base_url = 'https://pdnsapi.avant.nl';
$api_key = 'your_api_key_here';  // Replace with actual API key
```

```bash
API_BASE_URL="https://pdnsapi.avant.nl"
API_KEY="your_api_key_here"  # Replace with actual API key
```

### Dependencies
- **PHP Scripts**: PHP 7.4+, curl extension
- **Shell Scripts**: bash, curl, optional: jq for JSON parsing

## 📋 Available Templates

The testing suite works with the following templates from the PowerDNS Admin database:

| ID | Name | Description | Use Case |
|----|------|-------------|----------|
| 22 | Aron | Site op Aron mail op Aron | Full service hosting |
| 23 | Boaz | Site op Boaz mail op Boaz | Full service hosting |
| 24 | Digistad | Site op Digistad mail op Digistad | Full service hosting |
| 26 | gert | gert | Custom template |
| 21 | Hubo | Site op Hubo mail op Hubo | Full service hosting |
| 28 | Iris | site op iris mail op iris | Full service hosting |
| 14 | Office365 | Template voor Office 365 | Microsoft Office 365 integration |
| 16 | Redirect | Redirect Domain | Domain redirection |
| 30 | Redirect-via-CRM | Deze template gebruiken voor redirects die via CRM gaan | CRM-based redirection |
| 17 | Regonly | Regonly Domein | Registration-only domains |
| 29 | Testserver | N/A | Testing purposes |

## 🧩 Test Scenarios

### Success Scenarios
1. **Standard Template Usage**
   - Create domains with Office365 template
   - Create domains with Redirect template
   - Create domains with full-service templates (Aron, Boaz, etc.)

2. **Parameter Variations**
   - Use template_id parameter
   - Use template_name parameter
   - Different account_id values
   - Subdomain creation

3. **Record Verification**
   - Verify all expected record types are created
   - Validate record content and formatting
   - Check FQDN handling

### Error Scenarios
1. **Invalid Parameters**
   - Non-existent template_id
   - Invalid template_name
   - Missing required parameters
   - Invalid account_id

2. **Edge Cases**
   - Both template_id and template_name provided
   - Empty domain names
   - Special characters in domain names

## 🔍 Test Coverage

### Endpoints Tested
- `GET /templates` - List all available templates
- `GET /templates?id=X` - Get specific template details
- `POST /domains` - Create domain with template
- `GET /domains` - Verify domain creation
- `DELETE /domains/{name}` - Cleanup test domains

### Template Features Tested
- Template listing and enumeration
- Template record conversion to rrsets
- Domain creation with template data
- Record type validation (A, MX, NS, SOA, TXT, CNAME)
- FQDN formatting and validation
- Account-based filtering

## 📊 Test Output

### Success Indicators
- ✅ Green checkmarks for passed tests
- Detailed record verification
- Performance metrics
- Cleanup confirmation

### Failure Indicators
- ❌ Red X marks for failed tests
- HTTP status codes
- Error messages and responses
- Stack traces for debugging

### Summary Reports
- Total tests run
- Pass/fail counts
- Success percentage
- Duration metrics
- Created/cleaned domains list

## 🚀 Running the Tests

### Quick Start
```bash
# Make scripts executable
chmod +x *.sh

# Run quick validation
./quick-template-test.sh

# Run comprehensive PHP suite
php test-template-domain-creation.php

# Run interactive shell suite
./test-template-domains.sh
```

### Automated Testing
```bash
# Run all automated tests
./test-template-domains.sh --auto
php test-template-advanced.php
```

### Development Testing
```bash
# Test specific scenarios during development
php test-template-advanced.php

# Quick validation after changes
./quick-template-test.sh
```

## 🔒 Security Notes

- API keys should be configured in environment variables or secure config files
- Test domains use `.example.com` to avoid conflicts
- Automatic cleanup prevents test data accumulation
- All test domains are prefixed with timestamps for uniqueness

## 🐛 Troubleshooting

### Common Issues
1. **HTTP 401 Unauthorized**: Check API key configuration
2. **HTTP 404 Not Found**: Verify API endpoint URLs
3. **Template not found**: Ensure template exists in PowerDNS Admin database
4. **Domain creation fails**: Check account permissions and template validity
5. **Cleanup fails**: Manual cleanup may be required

### Debug Mode
Add debug output to scripts by:
- Setting `CURLOPT_VERBOSE` in PHP scripts
- Adding `-v` flag to curl commands in shell scripts
- Enabling error reporting with `error_reporting(E_ALL)` in PHP

## 📈 Performance Expectations

### Typical Response Times
- Template listing: 100-500ms
- Template details: 50-200ms
- Domain creation: 500-2000ms (depending on template size)
- Domain verification: 200-800ms
- Cleanup operations: 200-500ms per domain

### Scalability Testing
The advanced test suite includes performance testing with:
- Multiple concurrent domain creations
- Template iteration testing
- Batch operations validation

## 🔄 Continuous Integration

These test scripts can be integrated into CI/CD pipelines:

```yaml
# Example CI configuration
test_templates:
  script:
    - php test-template-domain-creation.php
    - ./test-template-domains.sh --auto
  artifacts:
    reports:
      junit: template-test-results.xml
```

## 📝 Test Data Management

- Test domains are automatically prefixed with timestamps
- All created domains are tracked for cleanup
- Templates are read-only and not modified by tests
- Database connections are properly closed
- Cleanup runs even on test failures

---

**Last Updated**: $(date)
**Version**: 1.0.0
**Maintainer**: PowerDNS API Development Team
