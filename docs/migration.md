# Migration Guide

This guide helps you migrate between versions of Laravel Safeguard and upgrade your security configurations.

## Version Compatibility

| Laravel Safeguard | Laravel | PHP | Status |
|-------------------|---------|-----|--------|
| 2.x               | 12.x    | 8.3+ | Current |
| 1.x               | 11.x    | 8.2+ | Legacy |

## Migrating to Version 2.x

### Major Changes

#### 1. Rule ID Format Change

**Version 1.x:** Snake case rule IDs
```php
'env_debug_false_in_production' => true,
'app_key_is_set' => true,
```

**Version 2.x:** Kebab case rule IDs
```php
'app-debug-false-in-production' => true,
'app-key-is-set' => true,
```

#### 2. New Rule Categories

Version 2.x introduces new rule categories:

**Database Security Rules:**
- `database-connection-encrypted`
- `database-credentials-not-default`
- `database-backup-security`
- `database-query-logging`

**Authentication Security Rules:**
- `password-policy-compliance`
- `two-factor-auth-enabled`
- `session-security-settings`

**Encryption Security Rules:**
- `encryption-key-rotation`
- `sensitive-data-encryption`

#### 3. Enhanced Commands

**New command options in 2.x:**
```bash
# New filtering options
php artisan safeguard:list --severity=critical
php artisan safeguard:list --environment=production

# Removed command
# php artisan safeguard:test-rule (no longer available)
```

### Migration Steps

#### Step 1: Update Configuration

Replace your existing `config/safeguard.php` with the new format:

```bash
# Backup your current config
cp config/safeguard.php config/safeguard.php.backup

# Republish the config
php artisan vendor:publish --tag=safeguard-config --force
```

**Manual Migration:**

Old format:
```php
'rules' => [
    'env_debug_false_in_production' => true,
    'app_key_is_set' => true,
    'csrf_enabled' => true,
    'no_secrets_in_code' => true,
],
```

New format:
```php
'rules' => [
    'app-debug-false-in-production' => true,
    'app-key-is-set' => true,
    'csrf-enabled' => true,
    'no-secrets-in-code' => true,
],
```

#### Step 2: Update Environment Configurations

Old format:
```php
'environments' => [
    'production' => [
        'env_debug_false_in_production',
        'app_key_is_set',
    ],
],
```

New format:
```php
'environments' => [
    'production' => [
        'app-debug-false-in-production',
        'app-key-is-set',
    ],
],
```

#### Step 3: Update Custom Rules

If you have custom rules, update their ID methods:

Old format:
```php
public function id(): string
{
    return 'custom_security_rule';
}
```

New format:
```php
public function id(): string
{
    return 'custom-security-rule';
}
```

#### Step 4: Update CI/CD Scripts

Update any scripts that reference old rule names:

Old script:
```bash
php artisan safeguard:test-rule env_debug_false_in_production
```

New script:
```bash
# Test-rule command removed, use check instead
php artisan safeguard:check --env=production
```

#### Step 5: Enable New Rules

Add new security rules to your configuration:

```php
'rules' => [
    // Existing rules...
    
    // New database security rules
    'database-connection-encrypted' => true,
    'database-credentials-not-default' => true,
    'database-backup-security' => true,
    'database-query-logging' => true,

    // New authentication rules
    'password-policy-compliance' => true,
    'two-factor-auth-enabled' => true,
    'session-security-settings' => true,

    // New encryption rules
    'encryption-key-rotation' => true,
    'sensitive-data-encryption' => true,

    // New security rules
    'composer-package-security' => true,
],
```

### Automated Migration Script

Create a migration script to automate the process:

```php
<?php
// migrate-safeguard.php

$configPath = 'config/safeguard.php';

if (!file_exists($configPath)) {
    echo "Configuration file not found.\n";
    exit(1);
}

$content = file_get_contents($configPath);

// Rule ID migrations
$migrations = [
    'env_debug_false_in_production' => 'app-debug-false-in-production',
    'app_key_is_set' => 'app-key-is-set',
    'csrf_enabled' => 'csrf-enabled',
    'no_secrets_in_code' => 'no-secrets-in-code',
    'env_has_all_required_keys' => 'env-has-all-required-keys',
    'env_file_permissions' => 'env-file-permissions',
    'composer_package_security' => 'composer-package-security',
];

foreach ($migrations as $old => $new) {
    $content = str_replace("'{$old}'", "'{$new}'", $content);
    $content = str_replace("\"{$old}\"", "\"{$new}\"", $content);
}

// Backup original
copy($configPath, $configPath . '.backup');

// Write updated content
file_put_contents($configPath, $content);

echo "Migration completed. Backup saved as {$configPath}.backup\n";
```

Run the migration:
```bash
php migrate-safeguard.php
```

## Breaking Changes

### Removed Features

#### 1. `safeguard:test-rule` Command

**Old usage:**
```bash
php artisan safeguard:test-rule app_key_is_set
```

**New alternative:**
```bash
# Use the main check command for testing
php artisan safeguard:check --env=local
```

#### 2. Legacy Rule Names

All snake_case rule names have been replaced with kebab-case equivalents.

### Changed Behavior

#### 1. Default Configuration

Version 2.x includes more security rules enabled by default. Review your configuration after migration.

#### 2. Environment Detection

The environment detection logic has been improved. Ensure your environment variables are properly set:

```bash
APP_ENV=production
```

#### 3. Result Format

The JSON output format has minor changes for consistency:

**Old format:**
```json
{
  "rule_id": "env_debug_false_in_production",
  "status": "failed"
}
```

**New format:**
```json
{
  "rule": "app-debug-false-in-production",
  "status": "failed"
}
```

## Configuration Migration

### Full Configuration Example

Here's a complete migration example:

**Version 1.x Configuration:**
```php
<?php
return [
    'rules' => [
        'env_debug_false_in_production' => true,
        'app_key_is_set' => true,
        'env_has_all_required_keys' => true,
        'no_secrets_in_code' => true,
        'csrf_enabled' => true,
        'env_file_permissions' => true,
    ],
    'environments' => [
        'production' => [
            'env_debug_false_in_production',
            'app_key_is_set',
            'env_file_permissions',
        ],
    ],
    'scan_paths' => [
        'app/',
        'config/',
    ],
];
```

**Version 2.x Configuration:**
```php
<?php
return [
    'rules' => [
        // Updated rule names
        'app-debug-false-in-production' => true,
        'app-key-is-set' => true,
        'env-has-all-required-keys' => true,
        'no-secrets-in-code' => true,
        'csrf-enabled' => true,
        'env-file-permissions' => true,
        
        // New rules available in 2.x
        'database-connection-encrypted' => true,
        'database-credentials-not-default' => true,
        'password-policy-compliance' => true,
        'composer-package-security' => true,
    ],
    'environments' => [
        'production' => [
            // Updated rule names
            'app-debug-false-in-production',
            'app-key-is-set',
            'env-file-permissions',
            
            // New rules for production
            'database-connection-encrypted',
            'password-policy-compliance',
        ],
    ],
    'scan_paths' => [
        'app/',
        'config/',
        'routes/',
        'resources/views/',
    ],
    
    // New configuration sections in 2.x
    'custom_rules_path' => app_path('SafeguardRules'),
    'custom_rules_namespace' => 'App\\SafeguardRules',
];
```

## Testing Migration

### Verification Steps

1. **Configuration Validation:**
```bash
php artisan safeguard:list
```

2. **Rule Execution Test:**
```bash
php artisan safeguard:check --env=local
```

3. **Environment-Specific Test:**
```bash
php artisan safeguard:check --env=production
```

4. **CI/CD Integration Test:**
```bash
php artisan safeguard:check --ci --format=json
```

### Common Issues

#### Issue 1: Rule Not Found

**Error:**
```
Rule 'env_debug_false_in_production' not found
```

**Solution:**
Update rule name to `app-debug-false-in-production`

#### Issue 2: Environment Rules Not Running

**Error:**
Rules are enabled but not running for specific environment

**Solution:**
Check that rule names in environment configuration match the new format:

```php
'environments' => [
    'production' => [
        'app-debug-false-in-production', // Updated name
        'app-key-is-set',               // Updated name
    ],
],
```

#### Issue 3: Custom Rules Not Loading

**Error:**
Custom rules not being executed

**Solution:**
Ensure custom rules use kebab-case IDs:

```php
public function id(): string
{
    return 'my-custom-rule'; // Use kebab-case
}
```

## Rollback Procedure

If you need to rollback to version 1.x:

1. **Restore Configuration:**
```bash
cp config/safeguard.php.backup config/safeguard.php
```

2. **Downgrade Package:**
```bash
composer require grazulex/laravel-safeguard:^1.0
```

3. **Clear Cache:**
```bash
php artisan cache:clear
php artisan config:clear
```

## Best Practices for Migration

1. **Test in Development First:**
   - Always test the migration in a development environment
   - Verify all rules work as expected

2. **Backup Configuration:**
   - Keep a backup of your working configuration
   - Document any customizations

3. **Gradual Migration:**
   - Migrate one environment at a time
   - Start with local/development environments

4. **Update Documentation:**
   - Update any internal documentation
   - Inform your team of the changes

5. **Monitor After Migration:**
   - Watch for any unexpected behavior
   - Check CI/CD pipelines continue to work

## Support

If you encounter issues during migration:

1. **Check Documentation:** Review the updated documentation for changes
2. **GitHub Issues:** Report problems at https://github.com/Grazulex/laravel-safeguard/issues
3. **Discussions:** Ask questions at https://github.com/Grazulex/laravel-safeguard/discussions

## Related Documentation

- [Configuration Reference](configuration-reference.md) - Complete configuration options
- [Commands Reference](commands.md) - Updated command documentation
- [Custom Rules Guide](custom-rules.md) - Creating rules in version 2.x
- [Troubleshooting](troubleshooting.md) - Common migration issues