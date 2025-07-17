# Security Rules Reference

Laravel Safeguard includes comprehensive security rules organized by category. Each rule can be enabled/disabled in your configuration.

## Environment & Configuration Rules

### `env_debug_false_in_production`
**Purpose**: Ensures `APP_DEBUG` is disabled in production environments  
**Severity**: Critical  
**Environments**: production, staging  

```php
// ✅ Good
APP_DEBUG=false

// ❌ Bad in production
APP_DEBUG=true
```

**Configuration:**
```php
'env_debug_false_in_production' => true,
```

### `app_key_is_set`
**Purpose**: Verifies that Laravel application key is generated  
**Severity**: Critical  
**Environments**: all  

```bash
# Generate if missing
php artisan key:generate
```

**Configuration:**
```php
'app_key_is_set' => true,
```

### `env_has_all_required_keys`
**Purpose**: Validates all required environment variables are present  
**Severity**: Error  
**Environments**: all  

Checks that all variables defined in `required_env_vars` config exist in your `.env` file.

**Configuration:**
```php
'env_has_all_required_keys' => true,

// Define required variables
'required_env_vars' => [
    'APP_KEY',
    'APP_ENV',
    'DB_CONNECTION',
    'DB_HOST',
    'DB_DATABASE',
],
```

### `no_secrets_in_code`
**Purpose**: Detects hardcoded secrets in your codebase  
**Severity**: Critical  
**Environments**: all  

Scans for patterns like:
- `$apiKey = 'sk_live_...'`
- `'password' => 'hardcoded123'`
- `AWS_ACCESS_KEY = 'AKIA...'`

**Configuration:**
```php
'no_secrets_in_code' => true,

// Customize patterns to detect
'secret_patterns' => [
    '*_KEY',
    '*_SECRET',
    '*_TOKEN',
    '*_PASSWORD',
    'API_*',
],
```

## Security Rules

### `csrf_enabled`
**Purpose**: Ensures CSRF protection is enabled  
**Severity**: Critical  
**Environments**: all  

Checks that `VerifyCsrfToken` middleware is active.

**Configuration:**
```php
'csrf_enabled' => true,
```

### `secure_cookies_in_production`
**Purpose**: Validates secure cookie settings for production  
**Severity**: Error  
**Environments**: production, staging  

Ensures cookies are marked as secure and HTTP-only in production.

**Configuration:**
```php
'secure_cookies_in_production' => true,
```

### `https_enforced_in_production`
**Purpose**: Verifies HTTPS enforcement in production  
**Severity**: Warning  
**Environments**: production  

Checks for HTTPS redirects and secure headers.

**Configuration:**
```php
'https_enforced_in_production' => false, // Optional rule
```

## File System Rules

### `storage_writable`
**Purpose**: Checks if storage directories are writable  
**Severity**: Error  
**Environments**: all  

Validates write permissions on:
- `storage/app/`
- `storage/logs/`
- `storage/framework/`
- `bootstrap/cache/`

**Configuration:**
```php
'storage_writable' => true,
```

### `env_file_permissions`
**Purpose**: Ensures `.env` file has proper permissions  
**Severity**: Critical  
**Environments**: production, staging  

Checks that `.env` file is not world-readable (not 644 or 666).

**Configuration:**
```php
'env_file_permissions' => true,
```

### `sensitive_files_hidden`
**Purpose**: Verifies sensitive files are not web-accessible  
**Severity**: Critical  
**Environments**: production, staging  

Checks that files like `.env`, `composer.json`, etc., return 404 when accessed via HTTP.

**Configuration:**
```php
'sensitive_files_hidden' => true,

// Customize files to check
'sensitive_files' => [
    '.env',
    '.env.example',
    'composer.json',
    'composer.lock',
    'artisan',
],
```

## Rule Severity Levels

### Critical 🚨
Issues that pose immediate security risks and must be fixed before production deployment.

### Error ❌
Important security issues that should be addressed but may not prevent deployment.

### Warning ⚠️
Recommendations for improved security posture.

### Info ℹ️
Informational messages about security configuration.

## Environment-Specific Rules

Rules can be configured to run only in specific environments:

```php
'environments' => [
    'production' => [
        'env_debug_false_in_production',
        'secure_cookies_in_production',
        'https_enforced_in_production',
        'env_file_permissions',
        'sensitive_files_hidden',
    ],
    'staging' => [
        'env_debug_false_in_production',
        'app_key_is_set',
        'csrf_enabled',
    ],
    'local' => [
        'app_key_is_set',
        'storage_writable',
    ],
],
```

## Testing Specific Rules

Test individual rules in isolation:

```bash
# Test a specific rule
php artisan safeguard:test-rule env_debug_false_in_production

# Test multiple rules
php artisan safeguard:test-rule app_key_is_set csrf_enabled
```

## Custom Rule Categories

When creating custom rules, consider these categories:

### Authentication & Authorization
- Password policies
- Session management
- User permissions

### Data Protection
- Encryption settings
- Database security
- File uploads

### External Services
- API security
- Third-party integrations
- OAuth configurations

### Infrastructure
- Server configuration
- Network security
- Monitoring setup

## Best Practices

1. **Start with Critical Rules**: Enable all critical rules first
2. **Environment-Specific**: Use different rule sets per environment
3. **Regular Reviews**: Periodically review and update your rule configuration
4. **Custom Rules**: Create rules specific to your application's security requirements
5. **Documentation**: Document any custom or disabled rules for your team

## Related Documentation

- [Custom Rules Guide](custom-rules.md) - Create your own security rules
- [Environment Rules](environment-rules.md) - Configure per-environment rule sets
- [Configuration Reference](configuration-reference.md) - Complete config options