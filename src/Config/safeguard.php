<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Security Rules Configuration
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific security rules. Set to true to enable
    | a rule, false to disable it.
    |
    */
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

    /*
    |--------------------------------------------------------------------------
    | Custom Rules Path
    |--------------------------------------------------------------------------
    |
    | Path where your custom security rules are stored. These will be
    | automatically loaded and registered with the SafeguardManager.
    |
    */
    'custom_rules_path' => app_path('SafeguardRules'),

    /*
    |--------------------------------------------------------------------------
    | Custom Rules Namespace
    |--------------------------------------------------------------------------
    |
    | The namespace for your custom security rules classes.
    |
    */
    'custom_rules_namespace' => 'App\\SafeguardRules',

    /*
    |--------------------------------------------------------------------------
    | Environment-Specific Rules
    |--------------------------------------------------------------------------
    |
    | Define which rules should run for specific environments when using
    | the --env-rules option with safeguard:check.
    |
    | By default, safeguard:check runs all enabled rules regardless of
    | environment. Use --env-rules to limit to environment-specific rules.
    |
    */
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

    /*
    |--------------------------------------------------------------------------
    | Scan Paths
    |--------------------------------------------------------------------------
    |
    | Directories to scan for hardcoded secrets and other security issues.
    |
    */
    'scan_paths' => [
        'app/',
        'config/',
        'routes/',
        'resources/views/',
        'database/seeders/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Secret Patterns
    |--------------------------------------------------------------------------
    |
    | Patterns to detect potentially hardcoded secrets in your code.
    |
    */
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

    /*
    |--------------------------------------------------------------------------
    | Required Environment Variables
    |--------------------------------------------------------------------------
    |
    | Environment variables that should be present in your .env file.
    |
    */
    'required_env_vars' => [
        'APP_KEY',
        'APP_ENV',
        'APP_DEBUG',
        'DB_CONNECTION',
        'DB_HOST',
        'DB_PORT',
        'DB_DATABASE',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sensitive Files
    |--------------------------------------------------------------------------
    |
    | Files that should not be accessible via web requests.
    |
    */
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
];
