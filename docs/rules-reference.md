# Security Rules Reference

Laravel Safeguard includes comprehensive security rules organized by category. Each rule can be enabled/disabled in your configuration.

# Security Rules Reference

Laravel Safeguard includes comprehensive security rules organized by category. Each rule can be enabled/disabled in your configuration.

## Environment & Configuration Rules

### `app-debug-false-in-production`
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
'app-debug-false-in-production' => true,
```

### `app-key-is-set`
**Purpose**: Verifies that Laravel application key is generated  
**Severity**: Critical  
**Environments**: all  

```bash
# Generate if missing
php artisan key:generate
```

**Configuration:**
```php
'app-key-is-set' => true,
```

### `env-has-all-required-keys`
**Purpose**: Validates all required environment variables are present  
**Severity**: Error  
**Environments**: all  

Checks that all variables defined in `required_env_vars` config exist in your `.env` file.

**Configuration:**
```php
'env-has-all-required-keys' => true,

// Define required variables
'required_env_vars' => [
    'APP_KEY',
    'APP_ENV',
    'DB_CONNECTION',
    'DB_HOST',
    'DB_DATABASE',
],
```

### `no-secrets-in-code`
**Purpose**: Detects hardcoded secrets in your codebase  
**Severity**: Critical  
**Environments**: all  

Scans for patterns like:
- `$apiKey = 'sk_live_...'`
- `'password' => 'hardcoded123'`
- `AWS_ACCESS_KEY = 'AKIA...'`

**Configuration:**
```php
'no-secrets-in-code' => true,

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

### `csrf-enabled`
**Purpose**: Ensures CSRF protection is enabled  
**Severity**: Critical  
**Environments**: all  

Checks that `VerifyCsrfToken` middleware is active.

**Configuration:**
```php
'csrf-enabled' => true,
```

### `composer-package-security`
**Purpose**: Validates composer packages for known security vulnerabilities  
**Severity**: Error  
**Environments**: all  

Checks for packages with known security issues and recommends updates.

**Configuration:**
```php
'composer-package-security' => true,
```

## File System Rules

### `env-file-permissions`
**Purpose**: Ensures `.env` file has proper permissions  
**Severity**: Critical  
**Environments**: production, staging  

Checks that `.env` file is not world-readable (not 644 or 666).

**Configuration:**
```php
'env-file-permissions' => true,
```

## Database Security Rules

### `database-connection-encrypted`
**Purpose**: Verifies that database connections use SSL/TLS encryption  
**Severity**: Critical  
**Environments**: production, staging  

Ensures database connections are encrypted in transit.

**Configuration:**
```php
'database-connection-encrypted' => true,
```

### `database-credentials-not-default`
**Purpose**: Checks for default or weak database credentials  
**Severity**: Critical  
**Environments**: all  

Validates that database passwords are not empty, default, or common weak passwords.

**Configuration:**
```php
'database-credentials-not-default' => true,
```

### `database-backup-security`
**Purpose**: Validates database backup security configuration  
**Severity**: Error  
**Environments**: production  

Checks backup encryption, access controls, and retention policies.

**Configuration:**
```php
'database-backup-security' => true,
```

### `database-query-logging`
**Purpose**: Ensures database query logging is properly configured  
**Severity**: Warning  
**Environments**: all  

Validates query logging settings for security monitoring.

**Configuration:**
```php
'database-query-logging' => true,
```

## Authentication Security Rules

### `password-policy-compliance`
**Purpose**: Verifies that password policy configuration meets security standards  
**Severity**: Critical  
**Environments**: all  

Checks password requirements including length, complexity, and validation rules.

**Configuration:**
```php
'password-policy-compliance' => true,
```

### `two-factor-auth-enabled`
**Purpose**: Validates two-factor authentication configuration  
**Severity**: Warning  
**Environments**: production  

Ensures 2FA is properly configured for enhanced security.

**Configuration:**
```php
'two-factor-auth-enabled' => true,
```

### `session-security-settings`
**Purpose**: Validates session security configuration  
**Severity**: Error  
**Environments**: production, staging  

Checks session lifetime, security flags, and storage configuration.

**Configuration:**
```php
'session-security-settings' => true,
```

## Encryption Security Rules

### `encryption-key-rotation`
**Purpose**: Validates encryption key management and rotation policies  
**Severity**: Warning  
**Environments**: production  

Checks for proper key rotation practices and recommendations.

**Configuration:**
```php
'encryption-key-rotation' => true,
```

### `sensitive-data-encryption`
**Purpose**: Ensures sensitive data is properly encrypted  
**Severity**: Critical  
**Environments**: all  

Validates encryption of sensitive database fields and stored data.

**Configuration:**
```php
'sensitive-data-encryption' => true,
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
        'app-debug-false-in-production',
        'app-key-is-set',
        'env-file-permissions',
        'database-connection-encrypted',
        'database-credentials-not-default',
        'password-policy-compliance',
        'encryption-key-rotation',
    ],
    'staging' => [
        'app-debug-false-in-production',
        'csrf-enabled',
        'database-connection-encrypted',
    ],
    'local' => [
        'app-key-is-set',
        'env-has-all-required-keys',
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