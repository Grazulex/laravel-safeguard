# Configuration Guide

The Laravel Safeguard configuration file (`config/safeguard.php`) allows you to customize which security rules are enabled and how they behave.

## Basic Configuration

After publishing the config file, you'll find these main sections:

### Rules Configuration

Enable or disable specific security rules:

```php
'rules' => [
    // Environment & Configuration Rules
    'app-debug-false-in-production' => true,
    'app-key-is-set' => true,
    'env-has-all-required-keys' => true,
    'no-secrets-in-code' => true,

    // Security Rules
    'csrf-enabled' => true,
    'composer-package-security' => true,

    // File System Rules
    'env-file-permissions' => true,

    // Database Security Rules
    'database-connection-encrypted' => true,
    'database-credentials-not-default' => true,
    'database-backup-security' => true,
    'database-query-logging' => true,

    // Authentication Security Rules
    'password-policy-compliance' => true,
    'two-factor-auth-enabled' => true,
    'session-security-settings' => true,

    // Encryption Security Rules
    'encryption-key-rotation' => true,
    'sensitive-data-encryption' => true,
],
```

### Environment-Specific Rules

Configure different rules for different environments:

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
        'app-key-is-set',
        'csrf-enabled',
        'database-connection-encrypted',
    ],
    'local' => [
        'app-key-is-set',
        'env-has-all-required-keys',
    ],
],
```
    ],
    'local' => [
        'app-key-is-set',
        'env-has-all-required-keys',
    ],
],
```

### Scan Paths

Specify which directories to scan for security issues:

```php
'scan_paths' => [
    'app/',
    'config/',
    'routes/',
    'resources/views/',
    'database/seeders/',
],
```

### Secret Patterns

Define patterns to detect hardcoded secrets:

```php
'secret_patterns' => [
    '*_KEY',
    '*_SECRET',
    '*_TOKEN',
    '*_PASSWORD',
    'API_*',
    'AWS_*',
    'STRIPE_*',
    'PAYPAL_*',
    'TWILIO_*',
    'MAILGUN_*',
],
```

## Advanced Configuration

### Custom Rules Path

Specify where your custom security rules are located:

```php
'custom_rules_path' => app_path('SafeguardRules'),
'custom_rules_namespace' => 'App\\SafeguardRules',
```

### Required Environment Variables

Define which environment variables must be present:

```php
'required_env_vars' => [
    'APP_KEY',
    'APP_ENV',
    'APP_DEBUG',
    'DB_CONNECTION',
    'DB_HOST',
    'DB_PORT',
    'DB_DATABASE',
],
```

### Sensitive Files

List files that should not be web-accessible:

```php
'sensitive_files' => [
    '.env',
    '.env.example',
    'composer.json',
    'composer.lock',
    'package.json',
    'package-lock.json',
    'artisan',
    'phpunit.xml',
    'README.md',
],
```

## Configuration Examples

### Production-Only Setup

For a strict production-only security check:

```php
'rules' => [
    'app-debug-false-in-production' => true,
    'secure_cookies_in_production' => true,
    'https_enforced_in_production' => true,
    'env-file-permissions' => true,
    // Disable development-focused rules
    'env-has-all-required-keys' => false,
    'no-secrets-in-code' => false,
],

'environments' => [
    'production' => [
        'app-debug-false-in-production',
        'secure_cookies_in_production',
        'https_enforced_in_production',
        'env-file-permissions',
    ],
],
```

### Development-Friendly Setup

For development environments with relaxed rules:

```php
'rules' => [
    'app-key-is-set' => true,
    'env-has-all-required-keys' => true,
    'csrf-enabled' => true,
    // Disable strict production rules
    'app-debug-false-in-production' => false,
    'https_enforced_in_production' => false,
],
```

### CI/CD Optimized Setup

For continuous integration environments:

```php
'rules' => [
    'env-has-all-required-keys' => true,
    'no-secrets-in-code' => true,
    'app-key-is-set' => true,
    // Disable environment-specific rules
    'app-debug-false-in-production' => false,
    'secure_cookies_in_production' => false,
],

'scan_paths' => [
    'app/',
    'config/',
    'routes/',
    // Exclude test directories for faster scanning
],
```

## Environment Variables

You can override configuration values using environment variables:

```bash
# .env
SAFEGUARD_ENABLED=true
SAFEGUARD_FAIL_ON_ERROR=true
```

Then in your config file:

```php
'fail_on_error' => env('SAFEGUARD_FAIL_ON_ERROR', false),
```

## Best Practices

1. **Start Small**: Enable a few critical rules first, then gradually add more
2. **Environment-Specific**: Use different rule sets for different environments
3. **Custom Rules**: Create custom rules for your specific security requirements
4. **Regular Updates**: Review and update your configuration as your application evolves
5. **Documentation**: Document any custom configurations for your team

## Next Steps

- [Learn about available security rules](rules-reference.md)
- [Create custom rules](custom-rules.md)
- [Set up environment-specific configurations](environment-rules.md)