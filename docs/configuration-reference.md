# Configuration Reference

Complete reference for all configuration options available in Laravel Safeguard.

## Configuration File Structure

The main configuration file is located at `config/safeguard.php`. Here's the complete structure with all available options:

```php
<?php

return [
    // Security Rules Configuration
    'rules' => [
        // ... rule definitions
    ],
    
    // Custom Rules Configuration  
    'custom_rules_path' => app_path('SafeguardRules'),
    'custom_rules_namespace' => 'App\\SafeguardRules',
    
    // Environment-Specific Rules
    'environments' => [
        // ... environment configurations
    ],
    
    // Scanning Configuration
    'scan_paths' => [
        // ... paths to scan
    ],
    'scan_exclusions' => [
        // ... files/patterns to exclude
    ],
    
    // Secret Detection
    'secret_patterns' => [
        // ... patterns to detect
    ],
    
    // Required Environment Variables
    'required_env_vars' => [
        // ... required variables
    ],
    
    // Sensitive Files
    'sensitive_files' => [
        // ... files to check
    ],
    
    // Performance Settings
    'max_file_size' => 1024 * 1024, // 1MB
    'timeout' => 30, // seconds
    
    // Output Configuration
    'output' => [
        // ... output settings
    ],
];
```

## Core Configuration Sections

### 1. Rules Configuration

Controls which security rules are enabled or disabled.

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

**Options:**
- `true` - Enable the rule
- `false` - Disable the rule

### 2. Custom Rules Configuration

Settings for loading custom security rules.

```php
'custom_rules_path' => app_path('SafeguardRules'),
'custom_rules_namespace' => 'App\\SafeguardRules',
```

**Options:**
- `custom_rules_path` - Directory path where custom rules are stored
- `custom_rules_namespace` - PHP namespace for custom rule classes

**Example Custom Structure:**
```
app/
└── SafeguardRules/
    ├── DatabaseSecurityRule.php
    ├── ApiSecurityRule.php
    └── CustomAuthRule.php
```

### 3. Environment-Specific Rules

Configure which rules run in specific environments.

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

**Environment Keys:** Any environment name (local, staging, production, testing, etc.)
**Rule Values:** Array of rule IDs that should run in that environment

### 4. Scan Paths Configuration

Directories to scan for security issues.

```php
'scan_paths' => [
    'app/',
    'config/',
    'routes/',
    'resources/views/',
    'database/seeders/',
],
```

**Path Options:**
- Relative paths from Laravel root
- Can include subdirectories
- Supports glob patterns

**Examples:**
```php
'scan_paths' => [
    'app/',                    // Entire app directory
    'app/Http/Controllers/',   // Specific subdirectory
    'config/*.php',           // Glob pattern
    'resources/**/*.blade.php', // Recursive glob
],
```

### 5. Scan Exclusions

Files and patterns to exclude from scanning.

```php
'scan_exclusions' => [
    '*.min.js',
    '*.min.css', 
    'vendor/',
    'node_modules/',
    'storage/logs/*',
    'bootstrap/cache/*',
    '.git/',
],
```

**Exclusion Patterns:**
- File extensions: `*.min.js`
- Directories: `vendor/`
- Glob patterns: `storage/logs/*`
- Specific files: `public/mix-manifest.json`

### 6. Secret Detection Patterns

Patterns used to detect hardcoded secrets in code.

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
    'PUSHER_*',
    'GITHUB_*',
    'GOOGLE_*',
    'FACEBOOK_*',
],
```

**Pattern Types:**
- Wildcards: `*_KEY` matches `API_KEY`, `STRIPE_KEY`, etc.
- Prefixes: `AWS_*` matches `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`
- Exact matches: `DATABASE_PASSWORD`

**Custom Patterns:**
```php
'secret_patterns' => [
    'MY_SERVICE_*',     // Custom service
    'INTERNAL_TOKEN',   // Specific token
    '*_CREDENTIAL',     // Any credential
],
```

### 7. Required Environment Variables

Environment variables that must be present.

```php
'required_env_vars' => [
    'APP_KEY',
    'APP_ENV',
    'APP_DEBUG',
    'APP_URL',
    'DB_CONNECTION',
    'DB_HOST',
    'DB_PORT',
    'DB_DATABASE',
    'DB_USERNAME',
    'DB_PASSWORD',
],
```

**Variable Categories:**
```php
'required_env_vars' => [
    // Laravel Core
    'APP_KEY',
    'APP_ENV',
    'APP_DEBUG',
    'APP_URL',
    
    // Database
    'DB_CONNECTION',
    'DB_HOST',
    'DB_DATABASE',
    
    // Cache & Session
    'CACHE_DRIVER',
    'SESSION_DRIVER',
    
    // Mail
    'MAIL_MAILER',
    'MAIL_HOST',
    
    // Custom Variables
    'MY_API_ENDPOINT',
    'EXTERNAL_SERVICE_KEY',
],
```

### 8. Sensitive Files

Files that should not be web-accessible.

```php
'sensitive_files' => [
    '.env',
    '.env.example',
    '.env.local',
    '.env.production',
    'composer.json',
    'composer.lock',
    'package.json',
    'package-lock.json',
    'yarn.lock',
    'artisan',
    'phpunit.xml',
    'README.md',
    'CHANGELOG.md',
    '.gitignore',
    '.gitattributes',
],
```

### 9. Performance Settings

Settings to control performance and resource usage.

```php
'performance' => [
    'max_file_size' => 1024 * 1024,    // 1MB - Skip files larger than this
    'timeout' => 30,                    // 30 seconds - Max execution time
    'memory_limit' => '256M',           // Memory limit for checks
    'max_scan_files' => 10000,          // Maximum files to scan
    'parallel_processes' => 4,          // Parallel processing
],
```

### 10. Output Configuration

Settings for command output and reporting.

```php
'output' => [
    'colors' => true,               // Use colors in CLI output
    'verbosity' => 'normal',        // normal, quiet, verbose
    'show_passed' => true,          // Show passed checks
    'show_skipped' => false,        // Show skipped checks
    'group_by_severity' => true,    // Group results by severity
    'max_issues_display' => 50,     // Limit displayed issues
],
```

### 11. Notification Settings

Configure notifications for security issues.

```php
'notifications' => [
    'enabled' => env('SAFEGUARD_NOTIFICATIONS', false),
    'channels' => [
        'slack' => [
            'webhook_url' => env('SLACK_WEBHOOK_URL'),
            'channel' => '#security',
            'severity_threshold' => 'warning',
        ],
        'email' => [
            'enabled' => false,
            'recipients' => ['security@example.com'],
            'severity_threshold' => 'critical',
        ],
    ],
],
```

## Rule-Specific Configuration

Some rules accept additional configuration options.

### Database Rules

```php
'database' => [
    'ssl_required_environments' => ['production', 'staging'],
    'allowed_drivers' => ['mysql', 'pgsql'],
    'weak_passwords' => ['password', 'root', '123456', 'admin'],
    'backup_encryption_required' => true,
],
```

### Authentication Rules

```php
'authentication' => [
    'password_min_length' => 8,
    'password_require_uppercase' => true,
    'password_require_numbers' => true,
    'password_require_symbols' => true,
    'session_lifetime_max' => 120, // minutes
    'two_factor_required_roles' => ['admin', 'manager'],
],
```

### Encryption Rules

```php
'encryption' => [
    'key_rotation_days' => 90,
    'required_cipher' => 'AES-256-CBC',
    'sensitive_fields' => [
        'password',
        'ssn',
        'credit_card',
        'api_token',
    ],
],
```

## Environment-Specific Configuration

Override settings per environment using environment variables:

```bash
# .env
SAFEGUARD_NOTIFICATIONS=true
SAFEGUARD_STRICT_MODE=true
SAFEGUARD_MAX_FILE_SIZE=2097152  # 2MB
```

Use in configuration:
```php
'performance' => [
    'max_file_size' => env('SAFEGUARD_MAX_FILE_SIZE', 1024 * 1024),
],
'notifications' => [
    'enabled' => env('SAFEGUARD_NOTIFICATIONS', false),
],
```

## Configuration Validation

Laravel Safeguard validates configuration on startup. Common validation rules:

### Rule Validation
- Rule IDs must exist
- Rule values must be boolean
- Environment rules must reference enabled rules

### Path Validation
- Scan paths must exist and be readable
- Custom rules path must exist if custom rules are used
- Exclusion patterns must be valid

### Performance Validation
- Timeouts must be positive integers
- Memory limits must be valid PHP memory notation
- File size limits must be positive integers

## Configuration Examples

### Minimal Configuration
```php
return [
    'rules' => [
        'app-key-is-set' => true,
        'app-debug-false-in-production' => true,
    ],
    'environments' => [
        'production' => [
            'app-key-is-set',
            'app-debug-false-in-production',
        ],
    ],
];
```

### Development-Friendly Configuration
```php
return [
    'rules' => [
        'app-key-is-set' => true,
        'env-has-all-required-keys' => true,
        'no-secrets-in-code' => false, // Disabled for development
    ],
    'scan_paths' => [
        'app/',
        'config/',
    ],
    'scan_exclusions' => [
        'vendor/',
        'node_modules/',
        'storage/',
    ],
];
```

### Comprehensive Production Configuration
```php
return [
    'rules' => [
        // Enable all security rules
        'app-debug-false-in-production' => true,
        'app-key-is-set' => true,
        'env-has-all-required-keys' => true,
        'no-secrets-in-code' => true,
        'csrf-enabled' => true,
        'composer-package-security' => true,
        'env-file-permissions' => true,
        'database-connection-encrypted' => true,
        'database-credentials-not-default' => true,
        'database-backup-security' => true,
        'database-query-logging' => true,
        'password-policy-compliance' => true,
        'two-factor-auth-enabled' => true,
        'session-security-settings' => true,
        'encryption-key-rotation' => true,
        'sensitive-data-encryption' => true,
    ],
    'environments' => [
        'production' => [
            // All rules for production
            'app-debug-false-in-production',
            'app-key-is-set',
            'env-has-all-required-keys',
            'no-secrets-in-code',
            'csrf-enabled',
            'composer-package-security',
            'env-file-permissions',
            'database-connection-encrypted',
            'database-credentials-not-default',
            'database-backup-security',
            'password-policy-compliance',
            'two-factor-auth-enabled',
            'session-security-settings',
            'encryption-key-rotation',
            'sensitive-data-encryption',
        ],
    ],
    'scan_paths' => [
        'app/',
        'config/',
        'routes/',
        'resources/views/',
        'database/migrations/',
        'database/seeders/',
    ],
    'required_env_vars' => [
        'APP_KEY',
        'APP_ENV',
        'APP_DEBUG',
        'DB_CONNECTION',
        'DB_HOST',
        'DB_DATABASE',
        'DB_USERNAME',
        'DB_PASSWORD',
        'MAIL_MAILER',
        'CACHE_DRIVER',
        'SESSION_DRIVER',
    ],
    'performance' => [
        'max_file_size' => 2 * 1024 * 1024, // 2MB
        'timeout' => 60,
        'memory_limit' => '512M',
    ],
    'notifications' => [
        'enabled' => true,
        'channels' => [
            'slack' => [
                'webhook_url' => env('SLACK_WEBHOOK_URL'),
                'severity_threshold' => 'warning',
            ],
        ],
    ],
];
```

## Configuration Best Practices

1. **Environment-Specific**: Use different configurations for different environments
2. **Start Simple**: Begin with basic rules and add more as needed
3. **Document Changes**: Comment your configuration changes
4. **Version Control**: Keep configuration in version control
5. **Environment Variables**: Use environment variables for sensitive settings
6. **Validation**: Test configuration changes in development first
7. **Performance**: Monitor performance impact of configuration changes

## Related Documentation

- [Installation Guide](installation.md) - Initial configuration setup
- [Environment Rules](environment-rules.md) - Environment-specific configuration
- [Custom Rules](custom-rules.md) - Configure custom rules
- [Performance](performance.md) - Performance-related configuration