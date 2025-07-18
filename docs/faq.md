# FAQ - Frequently Asked Questions

## General Questions

### What is Laravel Safeguard?

Laravel Safeguard is a configurable security audit package for Laravel applications. It helps identify security issues, misconfigurations, and potential vulnerabilities before they reach production.

### Why do I need Laravel Safeguard?

Common production issues that Laravel Safeguard helps prevent:
- ❌ Missing critical environment variables (APP_KEY, database credentials)
- 🔓 Hardcoded secrets in code instead of environment variables
- 🚨 Inconsistencies between `.env.example` and `.env`
- ⚠️ Security misconfigurations (APP_DEBUG=true in production)
- 🔒 Insecure defaults that should be changed before going live

### How is it different from other security tools?

Laravel Safeguard focuses specifically on **configuration and environment security** rather than code vulnerability scanning. It's like having a security checklist that runs automatically.

## Installation & Setup

### Can I use Laravel Safeguard in production?

Yes, but we recommend installing it as a development dependency (`--dev`) since it's primarily used for auditing and CI/CD processes:

```bash
composer require --dev grazulex/laravel-safeguard
```

### Do I need to publish the configuration?

Publishing the configuration is optional but recommended for customization:

```bash
php artisan vendor:publish --tag=safeguard-config
```

### What Laravel versions are supported?

Laravel Safeguard requires:
- PHP 8.3 or higher
- Laravel 12.19 or higher

### Can I use it with older Laravel versions?

The current version is designed for Laravel 12.x. For older Laravel versions, you may need to adapt the configuration or use an older version of the package.

## Configuration

### How do I enable/disable specific rules?

Edit `config/safeguard.php`:

```php
'rules' => [
    'app_key_is_set' => true,           // ✅ Enabled
    'https_enforced_in_production' => false, // ❌ Disabled
],
```

### Can I have different rules for different environments?

Yes! Use environment-specific configuration:

```php
'environments' => [
    'production' => [
        'env_debug_false_in_production',
        'secure_cookies_in_production',
        'https_enforced_in_production',
    ],
    'local' => [
        'app_key_is_set',
        'storage_writable',
    ],
],
```

### How do I add custom secret patterns?

Customize the `secret_patterns` array in your configuration:

```php
'secret_patterns' => [
    '*_KEY',
    '*_SECRET',
    '*_TOKEN',
    'MY_CUSTOM_*',
    'COMPANY_API_*',
],
```

### Can I exclude certain files or directories from scanning?

Currently, you can specify which paths to scan. To exclude paths, simply don't include them in `scan_paths`:

```php
'scan_paths' => [
    'app/',
    'config/',
    'routes/',
    // 'storage/' - excluded
    // 'vendor/' - excluded
],
```

## Usage

### How do I run security checks?

Basic usage:
```bash
php artisan safeguard:check
```

For specific environments:
```bash
php artisan safeguard:check --env=production
```

For detailed information about failures:
```bash
php artisan safeguard:check --details
```

For comprehensive audit with all details:
```bash
php artisan safeguard:check --show-all
```

### What's the difference between --details and --show-all?

- `--details`: Shows additional information only for **failed** checks (recommendations, current settings, security impact)
- `--show-all`: Shows additional information for **all** checks (both passed and failed)

Use `--details` when troubleshooting specific issues, and `--show-all` for comprehensive security audits.

### How do I get more information about failures?

Laravel Safeguard provides detailed information to help you fix issues:

```bash
# Basic check (minimal output)
php artisan safeguard:check

# Detailed failure analysis
php artisan safeguard:check --details

# Complete audit trail
php artisan safeguard:check --show-all
```

The detailed output includes:
- Current settings
- Actionable recommendations  
- Security impact explanations
- File paths where relevant

### What do the different icons mean?

- ✅ **Green checkmark**: Rule passed
- ❌ **Red X**: Rule failed (needs attention)
- ⚠️ **Yellow warning**: Rule has warnings (review recommended)
- 🚨 **Red alert**: Critical security issue

### How do I get JSON output?

Use the `--format=json` option:

```bash
php artisan safeguard:check --format=json
```

For comprehensive JSON with all details:
```bash
php artisan safeguard:check --format=json --show-all
```

This is useful for CI/CD integration and programmatic usage.

### Can I fail CI/CD pipelines on security issues?

Yes, use the `--fail-on-error` option:

```bash
php artisan safeguard:check --fail-on-error
```

For detailed CI output:
```bash
php artisan safeguard:check --ci --details --fail-on-error
```

This will exit with code 1 if any rules fail, causing CI/CD pipelines to fail.

## Custom Rules

### How do I create custom rules?

Generate a new rule:

```bash
php artisan safeguard:make-rule MyCustomRule
```

With specific severity level:

```bash
# Create rule with warning severity
php artisan safeguard:make-rule DatabaseSecurityRule --severity=warning

# Create rule with error severity  
php artisan safeguard:make-rule CriticalSecurityRule --severity=error
```

Available severity levels: `info` (default), `warning`, `error`

Then implement your logic in the generated file.

### Where should I put custom rules?

By default, custom rules go in `app/SafeguardRules/`. You can customize this in the configuration:

```php
'custom_rules_path' => app_path('SafeguardRules'),
'custom_rules_namespace' => 'App\\SafeguardRules',
```

### How do I enable custom rules?

Add them to your configuration:

```php
'rules' => [
    // Built-in rules
    'app_key_is_set' => true,
    
    // Your custom rules
    'my_custom_rule' => true,
],
```

### Can custom rules access Laravel services?

Yes! Custom rules run within the Laravel application context, so you can use:

```php
// In your custom rule
config('app.debug')           // Configuration
app()->environment()          // Environment
resolve(SomeService::class)   // Service container
DB::table('users')->count()   // Database
```

## Troubleshooting

### "Command not found" error

If artisan commands aren't available:

1. Check package installation:
   ```bash
   composer show grazulex/laravel-safeguard
   ```

2. Clear Laravel caches:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

3. Re-discover packages:
   ```bash
   php artisan package:discover
   ```

### Rules not running as expected

1. Check rule configuration:
   ```bash
   php artisan safeguard:list
   ```

2. Test individual rules:
   ```bash
   php artisan safeguard:test-rule app_key_is_set
   ```

3. Verify environment-specific settings

### Performance issues with large projects

1. Limit scan paths:
   ```php
   'scan_paths' => [
       'app/',
       'config/',
       // Remove large directories like vendor/, node_modules/
   ],
   ```

2. Increase memory limit:
   ```bash
   php -d memory_limit=512M artisan safeguard:check
   ```

### False positives in secret detection

1. Adjust secret patterns to be more specific
2. Use environment variables instead of comments with secret-like text
3. Consider creating a custom rule with more sophisticated logic

## CI/CD Integration

### Which CI/CD platforms are supported?

Laravel Safeguard works with any platform that can run PHP and artisan commands:
- GitHub Actions ✅
- GitLab CI ✅
- Jenkins ✅
- Azure Pipelines ✅
- Bitbucket Pipelines ✅
- CircleCI ✅
- Travis CI ✅

### How do I prevent deployment on security failures?

Use `--fail-on-error` in your deployment script:

```bash
# In your CI/CD pipeline
php artisan safeguard:check --env=production --fail-on-error

# This will exit with code 1 if issues are found
if [ $? -ne 0 ]; then
    echo "Security issues found. Blocking deployment."
    exit 1
fi
```

### Can I get notifications when security issues are found?

Yes, you can integrate with notification systems:

1. **Slack/Discord**: Parse JSON output and send webhooks
2. **Email**: Use CI/CD platform email notifications
3. **GitHub/GitLab**: Automatic PR comments with results
4. **PagerDuty/OpsGenie**: Integration via custom scripts

## Performance

### How fast is Laravel Safeguard?

Performance depends on:
- Number of enabled rules
- Size of codebase being scanned
- Number of files in scan paths

Typical performance:
- Small project (< 1000 files): 1-5 seconds
- Medium project (1000-5000 files): 5-15 seconds
- Large project (> 5000 files): 15-60 seconds

### How can I improve performance?

1. **Limit scan paths**:
   ```php
   'scan_paths' => [
       'app/',           // Include
       'config/',        // Include
       // 'vendor/',     // Exclude
       // 'node_modules/', // Exclude
   ],
   ```

2. **Disable expensive rules** in development
3. **Use caching** in CI/CD pipelines
4. **Run in parallel** for multiple environments

### Can I cache results?

Results aren't cached by default, but you can implement caching in CI/CD:

```bash
# GitHub Actions example
- name: Cache security results
  uses: actions/cache@v3
  with:
    path: security-cache/
    key: ${{ runner.os }}-security-${{ hashFiles('config/safeguard.php') }}
```

## Best Practices

### When should I run security checks?

**Recommended schedule:**
- ✅ Every commit (via CI/CD)
- ✅ Before deployment to staging/production
- ✅ Weekly automated audits
- ✅ After configuration changes

### What's the recommended rule configuration?

**Start with critical rules:**
```php
'rules' => [
    'app_key_is_set' => true,
    'env_debug_false_in_production' => true,
    'csrf_enabled' => true,
    'no_secrets_in_code' => true,
],
```

**Gradually add more rules** as your team becomes comfortable.

### How do I handle false positives?

1. **Adjust configuration** to be more specific
2. **Create custom rules** with better logic
3. **Use environment-specific rules** to avoid development friction
4. **Document exceptions** for your team

### Should I fail CI/CD on all rule failures?

**Recommended approach:**
- ✅ **Critical rules**: Always fail CI/CD
- ⚠️ **Warning rules**: Report but don't fail
- 📊 **Generate reports** for review

```bash
# Production: Fail on any issues
php artisan safeguard:check --env=production --fail-on-error

# Development: Report but don't fail
php artisan safeguard:check --env=local
```

## Getting Help

### Where can I get support?

- **Documentation**: Check the [docs](README.md) directory
- **GitHub Issues**: Report bugs or request features
- **GitHub Discussions**: Ask questions and share ideas
- **Stack Overflow**: Tag questions with `laravel-safeguard`

### How do I report a bug?

Please include:
1. Laravel Safeguard version
2. Laravel version
3. PHP version
4. Steps to reproduce
5. Expected vs actual behavior
6. Relevant configuration
7. Error messages (if any)

### How do I request a new rule?

1. Check if it can be implemented as a custom rule
2. If it's broadly useful, open a GitHub issue with:
   - Rule description
   - Security purpose
   - Example usage
   - Proposed implementation

### Can I contribute to the project?

Yes! Contributions are welcome:
- **Bug fixes**: Submit pull requests
- **New rules**: Propose and implement new security rules
- **Documentation**: Improve docs and examples
- **Testing**: Add test cases
- **Examples**: Share useful configurations and custom rules