# Enhanced Output with Details

Laravel Safeguard now supports detailed output for security checks to help you better understand and fix security issues.

## New Command Options

### `--details`
Shows detailed information only for **failed checks**. This option provides:
- Current settings
- Security impacts
- Specific recommendations
- Configuration details

```bash
./vendor/bin/testbench safeguard:check --details
```

### `--show-all`
Shows detailed information for **all checks** (both passed and failed). This gives you a comprehensive view of your security configuration.

```bash
./vendor/bin/testbench safeguard:check --show-all
```

## Output Examples

### Basic Output (without details)
```
✅ APP_DEBUG is properly configured for this environment
❌ CSRF protection is disabled
🚨 Composer package security issues detected
```

### With Details (`--details` option)
```
✅ APP_DEBUG is properly configured for this environment

❌ CSRF protection is disabled
   ⚙️ Current Setting: disabled
   💡 Recommendation: Enable CSRF protection in your application configuration
   ⚠️ Security Impact: Without CSRF protection, your application is vulnerable to cross-site request forgery attacks

🚨 Composer package security issues detected
   📋 Issues Found:
     • {"type":"missing_composer_lock","severity":"critical","message":"composer.lock file not found"}
   📋 Recommendations:
     • Run composer install to generate composer.lock file
```

## Icons and Formatting

The detailed output uses intuitive icons to categorize information:

- 📌 **General Information**: Basic configuration details
- ⚙️ **Current Setting**: What's currently configured
- 💡 **Recommendation**: How to fix the issue
- ⚠️ **Security Impact**: Why this matters for security
- 📋 **Lists**: Multiple items (issues, recommendations, etc.)
- 📁 **File Path**: File or directory references

## Integration with Existing Options

The new detail options work alongside all existing options:

```bash
# Show details with JSON output
./vendor/bin/testbench safeguard:check --details --format=json

# Show details in CI mode
./vendor/bin/testbench safeguard:check --details --ci

# Show details for specific environment
./vendor/bin/testbench safeguard:check --details --env=production
```

## Use Cases

### Development
Use `--details` during development to quickly understand and fix security issues:
```bash
./vendor/bin/testbench safeguard:check --details
```

### Auditing
Use `--show-all` for comprehensive security audits:
```bash
./vendor/bin/testbench safeguard:check --show-all
```

### CI/CD Integration
Combine with `--fail-on-error` for automated testing:
```bash
./vendor/bin/testbench safeguard:check --details --fail-on-error
```
