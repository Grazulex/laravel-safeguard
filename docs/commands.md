# Commands Reference

Laravel Safeguard provides several artisan commands for running security checks and managing rules.

## `safeguard:check`

Run security checks on your Laravel application.

### Syntax
```bash
php artisan safeguard:check [options]
```

### Options

| Option | Description | Example |
|--------|-------------|---------|
| `--env=ENVIRONMENT` | Specify environment to check | `--env=production` |
| `--format=FORMAT` | Output format (cli, json) | `--format=json` |
| `--fail-on-error` | Exit with error code if rules fail | `--fail-on-error` |
| `--ci` | CI-friendly output (no colors) | `--ci` |
| `--env-rules` | Use environment-specific rules only | `--env-rules` |

### Examples

```bash
# Basic security check (runs all enabled rules)
php artisan safeguard:check

# Check production environment (still runs all enabled rules)
php artisan safeguard:check --env=production

# Use only environment-specific rules for production
php artisan safeguard:check --env=production --env-rules

# Get JSON output
php artisan safeguard:check --format=json

# CI/CD usage
php artisan safeguard:check --ci --fail-on-error

# Production check with failure on errors
php artisan safeguard:check --env=production --fail-on-error
```

### Exit Codes

- `0`: All checks passed
- `1`: One or more checks failed (only when using `--fail-on-error`)

## `safeguard:list`

Display all available security rules and their current status.

### Syntax
```bash
php artisan safeguard:list [options]
```

### Options

| Option | Description | Example |
|--------|-------------|---------|
| `--enabled` | Show only enabled rules | `--enabled` |
| `--disabled` | Show only disabled rules | `--disabled` |
| `--environment=ENVIRONMENT` | Show rules for specific environment | `--environment=production` |
| `--env=ENVIRONMENT` | Show rules for specific environment (alias) | `--env=production` |
| `--severity=SEVERITY` | Show rules with specific severity | `--severity=critical` |

### Examples

```bash
# List all rules
php artisan safeguard:list

# List only enabled rules
php artisan safeguard:list --enabled

# List rules for specific environment
php artisan safeguard:list --environment=production

# List rules by severity
php artisan safeguard:list --severity=critical
```

### Sample Output

```
┌──────────────────────────────────┬─────────┬─────────────┬─────────────────────────────────────────┐
│ Rule ID                          │ Status  │ Severity    │ Description                             │
├──────────────────────────────────┼─────────┼─────────────┼─────────────────────────────────────────┤
│ app-key-is-set                   │ ✅ On   │ critical    │ Verifies that Laravel application...   │
│ app-debug-false-in-production    │ ✅ On   │ critical    │ Ensures APP_DEBUG is false in...       │
│ csrf-enabled                     │ ✅ On   │ critical    │ Ensures CSRF protection is enabled     │
│ no-secrets-in-code               │ ✅ On   │ critical    │ Detects hardcoded secrets in...        │
│ database-connection-encrypted    │ ✅ On   │ critical    │ Verifies database connections...        │
│ password-policy-compliance       │ ✅ On   │ critical    │ Verifies password policy meets...      │
│ two-factor-auth-enabled          │ ❌ Off  │ warning     │ Validates two-factor auth config...     │
└──────────────────────────────────┴─────────┴─────────────┴─────────────────────────────────────────┘
```

## `safeguard:make-rule`

Generate a new custom security rule.

### Syntax
```bash
php artisan safeguard:make-rule {name}
```

### Arguments

| Argument | Description | Required |
|----------|-------------|----------|
| `name` | Name of the rule class | Yes |

### Examples

```bash
# Create a custom rule
php artisan safeguard:make-rule CustomSecurityRule

# Create a database security rule
php artisan safeguard:make-rule DatabaseSecurityRule

# Create an API security rule
php artisan safeguard:make-rule ApiSecurityRule
```

### Generated File

Creates a new rule class in `app/SafeguardRules/`:

```php
<?php

namespace App\SafeguardRules;

use Grazulex\LaravelSafeguard\Contracts\SafeguardRule;
use Grazulex\LaravelSafeguard\SafeguardResult;

class CustomSecurityRule implements SafeguardRule
{
    public function id(): string
    {
        return 'custom_security_rule';
    }

    public function description(): string
    {
        return 'Custom security validation';
    }

    public function check(): SafeguardResult
    {
        // Your custom logic here
        return SafeguardResult::pass('Custom check passed');
    }

    public function appliesToEnvironment(string $environment): bool
    {
        return true;
    }

    public function severity(): string
    {
        return 'error';
    }
}
```

## Global Options

All commands support these global Laravel artisan options:

| Option | Description |
|--------|-------------|
| `-h, --help` | Display help information |
| `-q, --quiet` | Do not output any message |
| `-V, --version` | Display application version |
| `-n, --no-interaction` | Do not ask any interactive question |
| `-v, --verbose` | Increase verbosity |

## Exit Codes

Laravel Safeguard commands use standard exit codes:

| Code | Meaning |
|------|---------|
| `0` | Success |
| `1` | General error |
| `2` | Invalid command usage |

## Environment Variables

You can control command behavior with environment variables:

```bash
# Default environment for checks
export SAFEGUARD_DEFAULT_ENV=production

# Default output format
export SAFEGUARD_DEFAULT_FORMAT=json

# Enable verbose output by default
export SAFEGUARD_VERBOSE=true
```

## Integration Examples

### Pre-deployment Script

```bash
#!/bin/bash
set -e

echo "Running pre-deployment security checks..."

# Check production configuration
php artisan safeguard:check --env=production --fail-on-error

if [ $? -eq 0 ]; then
    echo "✅ Security checks passed. Safe to deploy!"
else
    echo "❌ Security issues found. Deployment blocked!"
    exit 1
fi
```

### CI/CD Pipeline

```yaml
# GitHub Actions example
- name: Run security audit
  run: |
    php artisan safeguard:check --ci --fail-on-error --env=production
    
- name: Generate security report
  run: |
    php artisan safeguard:check --format=json > security-report.json
    
- name: Upload report
  uses: actions/upload-artifact@v3
  with:
    name: security-report
    path: security-report.json
```

### Automated Testing

```bash
# List all enabled rules
php artisan safeguard:list --enabled

# Run checks for all environments
for env in local staging production; do
    echo "Testing environment: $env"
    php artisan safeguard:check --env=$env
done
```

## Troubleshooting

### Command Not Found

If artisan commands are not available:

```bash
# Check if package is installed
composer show grazulex/laravel-safeguard

# Clear Laravel cache
php artisan cache:clear
php artisan config:clear

# Re-register service provider if needed
php artisan package:discover
```

### Permission Errors

```bash
# Ensure storage is writable
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/

# Check file ownership
chown -R www-data:www-data storage/
```

### Memory Limits

For large projects, you may need to increase memory limits:

```bash
# Increase memory limit
php -d memory_limit=512M artisan safeguard:check

# Or set in environment
export MEMORY_LIMIT=512M
php artisan safeguard:check
```

## Related Documentation

- [Configuration Guide](configuration.md) - Configure security rules
- [Custom Rules Guide](custom-rules.md) - Create custom security rules
- [CI/CD Integration](ci-cd-integration.md) - Automate security checks