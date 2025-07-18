# Quick Start Guide

Get up and running with Laravel Safeguard in just a few minutes.

## Installation

```bash
composer require --dev grazulex/laravel-safeguard
php artisan vendor:publish --tag=safeguard-config
```

## Your First Security Check

Run a basic security audit:

```bash
php artisan safeguard:check
```

**Example Output:**
```
🔐 Laravel Safeguard Security Check
═══════════════════════════════════════

Environment: local

✅ APP_KEY is set
✅ Storage directories are writable
✅ CSRF protection enabled
⚠️  APP_DEBUG is enabled (acceptable in local environment)

═══════════════════════════════════════
🎯 All checks passed! (4 checks)
```

## Understanding the Results

- ✅ **Green checkmark**: Rule passed
- ❌ **Red X**: Rule failed (needs attention)
- ⚠️ **Yellow warning**: Rule has warnings (review recommended)
- 🚨 **Red alert**: Critical security issue

## Common First Steps

### 1. Check Available Rules

See what security rules are available:

```bash
php artisan safeguard:list
```

### 2. Environment-Specific Checks

Run checks for production environment:

```bash
php artisan safeguard:check --env=production
```

### 3. Get Detailed Information

For more information about failures:

```bash
php artisan safeguard:check --details
```

See detailed information for all checks:

```bash
php artisan safeguard:check --show-all
```

### 4. JSON Output for Automation

Get machine-readable output:

```bash
php artisan safeguard:check --format=json
```

## Essential Configuration

Edit `config/safeguard.php` to customize your security rules:

```php
return [
    'rules' => [
        // Essential security rules (recommended to keep enabled)
        'app-key-is-set' => true,
        'app-debug-false-in-production' => true,
        'csrf-enabled' => true,
        
        // Optional rules (enable based on your needs)
        'no-secrets-in-code' => true,
        'env-file-permissions' => true,
    ],
];
```

## Real-World Example

Here's what a typical security check might reveal:

```bash
php artisan safeguard:check --env=production --details
```

```
🔐 Laravel Safeguard Security Check
═══════════════════════════════════════

Environment: production

✅ APP_KEY is set
❌ APP_DEBUG is true in production
   ⚙️ Current Setting: true
   💡 Recommendation: Set APP_DEBUG=false in production environment
   ⚠️ Security Impact: Debug mode exposes sensitive application information

✅ CSRF protection enabled
❌ Secret found in config/services.php (STRIPE_SECRET)
   📁 File Path: config/services.php
   📋 Detected Secrets:
     • STRIPE_SECRET on line 15
   💡 Recommendation: Move secret to environment variable

✅ Storage directories are writable
⚠️  HTTPS not enforced (rule disabled)

═══════════════════════════════════════
🎯 2 issues found, 3 checks passed
```

### Fixing the Issues

1. **APP_DEBUG issue**: Set `APP_DEBUG=false` in your production `.env`
2. **Hardcoded secret**: Move `STRIPE_SECRET` to environment variables

## Integration Examples

### GitHub Actions

Add to `.github/workflows/security.yml`:

```yaml
name: Security Audit
on: [push, pull_request]

jobs:
  security:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.3
      - name: Install dependencies
        run: composer install
      - name: Run security checks
        run: php artisan safeguard:check --ci --fail-on-error
```

### Pre-deployment Script

Create a deployment checklist script:

```bash
#!/bin/bash
echo "Running pre-deployment security checks..."
php artisan safeguard:check --env=production --fail-on-error

if [ $? -eq 0 ]; then
    echo "✅ Security checks passed! Safe to deploy."
else
    echo "❌ Security issues found! Please fix before deploying."
    exit 1
fi
```

## What's Next?

Now that you have Laravel Safeguard running, explore these features:

- **[Custom Rules](custom-rules.md)**: Create security rules specific to your application
- **[CI/CD Integration](ci-cd-integration.md)**: Automate security checks in your pipeline
- **[Configuration Guide](configuration.md)**: Deep dive into configuration options
- **[Rules Reference](rules-reference.md)**: Learn about all available security rules

## Common Issues

### "Command not found"

If the command doesn't work, ensure the package is properly installed:

```bash
composer show grazulex/laravel-safeguard
php artisan list | grep safeguard
```

### "Config file not found"

Make sure you've published the configuration:

```bash
php artisan vendor:publish --tag=safeguard-config --force
```

### No rules enabled

Check your `config/safeguard.php` file has rules set to `true`:

```php
'rules' => [
    'app-key-is-set' => true, // ← Make sure this is true
    // ...
],
```

## Help & Support

- **[FAQ](faq.md)**: Common questions and answers
- **[Troubleshooting](troubleshooting.md)**: Solutions to common problems
- **[GitHub Issues](https://github.com/Grazulex/laravel-safeguard/issues)**: Report bugs or request features