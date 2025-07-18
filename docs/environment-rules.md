# Environment-Specific Rules

Laravel Safeguard allows you to configure different security rules for different environments. This ensures that your security checks are appropriate for each stage of your deployment pipeline.

## Overview

Different environments have different security requirements:

- **Local/Development**: Relaxed rules for developer productivity
- **Staging**: Moderate security to catch issues before production
- **Production**: Strict security rules for live applications

## Configuration

Environment-specific rules are configured in `config/safeguard.php`:

```php
'environments' => [
    'local' => [
        'app-key-is-set',
        'env-has-all-required-keys',
    ],
    'staging' => [
        'app-debug-false-in-production',
        'app-key-is-set',
        'csrf-enabled',
        'database-connection-encrypted',
    ],
    'production' => [
        'app-debug-false-in-production',
        'app-key-is-set',
        'env-file-permissions',
        'database-connection-encrypted',
        'database-credentials-not-default',
        'password-policy-compliance',
        'encryption-key-rotation',
        'sensitive-data-encryption',
    ],
],
```

## Environment Profiles

### Local Development

**Purpose**: Enable development while maintaining basic security

**Recommended Rules:**
- `app-key-is-set` - Ensures Laravel is properly configured
- `env-has-all-required-keys` - Validates environment setup

**Disabled Rules:**
- `app-debug-false-in-production` - Debug mode is acceptable locally
- Most production-specific security rules

```php
'local' => [
    'app-key-is-set',
    'env-has-all-required-keys',
],
```

### Staging Environment

**Purpose**: Test production-like security without blocking development

**Recommended Rules:**
- Basic production rules
- Database security
- CSRF protection
- Critical configuration checks

**Flexibility:** Some rules may be warnings rather than failures

```php
'staging' => [
    'app-debug-false-in-production',
    'app-key-is-set',
    'csrf-enabled',
    'database-connection-encrypted',
    'no-secrets-in-code',
],
```

### Production Environment

**Purpose**: Maximum security for live applications

**Required Rules:**
- All critical security rules
- Database encryption
- Authentication security
- File permissions
- Encryption management

```php
'production' => [
    'app-debug-false-in-production',
    'app-key-is-set',
    'env-file-permissions',
    'database-connection-encrypted',
    'database-credentials-not-default',
    'database-backup-security',
    'password-policy-compliance',
    'two-factor-auth-enabled',
    'session-security-settings',
    'encryption-key-rotation',
    'sensitive-data-encryption',
    'no-secrets-in-code',
    'csrf-enabled',
    'composer-package-security',
],
```

## Running Environment-Specific Checks

### Command Line

```bash
# Check local environment
php artisan safeguard:check --env=local

# Check staging environment
php artisan safeguard:check --env=staging

# Check production with failure on errors
php artisan safeguard:check --env=production --fail-on-error
```

### CI/CD Integration

```yaml
# GitHub Actions example
strategy:
  matrix:
    environment: [staging, production]

steps:
- name: Run security checks
  run: |
    php artisan safeguard:check \
      --env=${{ matrix.environment }} \
      --format=json \
      --ci
```

## Rule Behavior by Environment

### Rule Application Logic

1. **Environment Check**: Each rule implements `appliesToEnvironment(string $environment): bool`
2. **Configuration Filter**: Only rules listed in environment config are run
3. **Global Rules**: Rules enabled globally in `rules` section must also be in environment list

### Example Rule Implementation

```php
public function appliesToEnvironment(string $environment): bool
{
    // This rule only applies to production and staging
    return in_array($environment, ['production', 'staging']);
}
```

## Best Practices

### 1. Gradual Security Tightening

Start with relaxed rules in development and gradually increase security requirements:

```
Local → Staging → Production
 ↓        ↓          ↓
Few     Moderate   Strict
Rules    Rules     Rules
```

### 2. Environment Inheritance

Consider making staging a subset of production rules:

```php
$productionRules = [
    'app-debug-false-in-production',
    'app-key-is-set',
    'database-connection-encrypted',
    'password-policy-compliance',
    // ... more rules
];

$stagingRules = array_slice($productionRules, 0, 5); // First 5 rules

'environments' => [
    'staging' => $stagingRules,
    'production' => $productionRules,
],
```

### 3. Custom Environment Support

You can create custom environments:

```php
'environments' => [
    'testing' => [
        'app-key-is-set',
        'env-has-all-required-keys',
    ],
    'demo' => [
        'app-debug-false-in-production',
        'csrf-enabled',
        'database-connection-encrypted',
    ],
],
```

Run with: `php artisan safeguard:check --env=testing`

### 4. Rule Severity by Environment

Consider different severities for the same rule:

```php
// In a custom rule
public function check(): SafeguardResult
{
    $currentEnv = app()->environment();
    
    if ($this->hasIssue()) {
        // Critical in production, warning elsewhere
        $severity = $currentEnv === 'production' ? 'critical' : 'warning';
        return SafeguardResult::fail($this->message(), $severity);
    }
    
    return SafeguardResult::pass('Check passed');
}
```

## Troubleshooting

### Rules Not Running

**Problem**: Rules are enabled globally but not running for specific environment

**Solution**: Ensure rule is listed in environment configuration:

```php
// ❌ Rule enabled globally but missing from environment
'rules' => [
    'my-custom-rule' => true,
],
'environments' => [
    'production' => [
        // 'my-custom-rule' missing here
    ],
],

// ✅ Rule properly configured for environment
'rules' => [
    'my-custom-rule' => true,
],
'environments' => [
    'production' => [
        'my-custom-rule', // Added here
    ],
],
```

### Environment Detection

**Problem**: Wrong environment detected

**Solution**: Check your Laravel environment configuration:

```bash
# Check current environment
php artisan env

# Set environment explicitly
APP_ENV=production php artisan safeguard:check
```

### Rule Conflicts

**Problem**: Rule behaves differently in different environments

**Solution**: Check rule's `appliesToEnvironment()` method:

```php
public function appliesToEnvironment(string $environment): bool
{
    // Make sure this logic matches your expectations
    return $environment === 'production';
}
```

## Advanced Configuration

### Dynamic Environment Rules

Load rules dynamically based on external factors:

```php
// In AppServiceProvider
public function boot()
{
    $safeguard = app(SafeguardManager::class);
    
    // Add extra rules for production during business hours
    if (app()->environment('production') && $this->isBusinessHours()) {
        $safeguard->registerRule(new ExtraSecurityRule());
    }
}
```

### Environment Variables

Control behavior via environment variables:

```bash
# Enable strict mode for staging
SAFEGUARD_STRICT_STAGING=true php artisan safeguard:check --env=staging
```

```php
// In configuration
'environments' => [
    'staging' => env('SAFEGUARD_STRICT_STAGING', false) 
        ? config('safeguard.environments.production')
        : [
            'app-key-is-set',
            'csrf-enabled',
        ],
],
```

## Related Documentation

- [Configuration Guide](configuration.md) - Complete configuration options
- [Rules Reference](rules-reference.md) - All available security rules
- [CI/CD Integration](ci-cd-integration.md) - Automate environment-specific checks
- [Custom Rules](custom-rules.md) - Create environment-aware rules