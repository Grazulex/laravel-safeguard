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
        'env_debug_false_in_production' => true,
        'app_key_is_set' => true,
        'env_has_all_required_keys' => true,
        'no_secrets_in_code' => true,

        // Security Rules
        'csrf_enabled' => true,
        'secure_cookies_in_production' => true,
        'storage_writable' => true,
        'https_enforced_in_production' => false,

        // File System Rules
        'env_file_permissions' => true,
        'sensitive_files_hidden' => true,
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
    | Define which rules should run for specific environments.
    |
    */
    'environments' => [
        'production' => [
            'env_debug_false_in_production',
            'app_key_is_set',
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
