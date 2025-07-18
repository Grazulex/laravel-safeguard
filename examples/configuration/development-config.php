<?php

declare(strict_types=1);

/**
 * Development Configuration Example
 *
 * This configuration is optimized for development environments
 * with developer-friendly settings.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Security Rules Configuration - DEVELOPMENT
    |--------------------------------------------------------------------------
    |
    | Relaxed security rules for development environments.
    | Focus on essential checks without blocking development workflow.
    |
    */
    'rules' => [
        // Essential rules that should always be enabled
        'app_key_is_set' => true,
        'storage_writable' => true,
        'csrf_enabled' => true,

        // Development-friendly settings
        'env_debug_false_in_production' => false, // Allow debug in development
        'secure_cookies_in_production' => false,  // Not needed in development
        'https_enforced_in_production' => false,  // Not needed in development
        'env_file_permissions' => false,          // Relaxed for development

        // Optional checks (can be enabled/disabled based on preference)
        'env_has_all_required_keys' => true,      // Helps catch missing vars
        'no_secrets_in_code' => true,             // Good practice even in dev
        'sensitive_files_hidden' => false,        // Not critical in development

        // Custom rules (if you have them)
        'database_security_check' => false,       // May conflict with dev settings
        'api_security_check' => false,            // May be too strict for dev
        'file_upload_security' => false,          // Relaxed for testing
    ],

    /*
    |--------------------------------------------------------------------------
    | Environment-Specific Rules
    |--------------------------------------------------------------------------
    |
    | Development and local environments have minimal requirements.
    |
    */
    'environments' => [
        'local' => [
            'app_key_is_set',
            'storage_writable',
            'env_has_all_required_keys',
        ],

        'development' => [
            'app_key_is_set',
            'storage_writable',
            'csrf_enabled',
            'env_has_all_required_keys',
            'no_secrets_in_code',
        ],

        'testing' => [
            'app_key_is_set',
            'storage_writable',
            'no_secrets_in_code',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Scan Paths - DEVELOPMENT
    |--------------------------------------------------------------------------
    |
    | Limited scanning for faster execution in development.
    |
    */
    'scan_paths' => [
        'app/',
        'config/',
        'routes/',
        // Exclude large directories for performance
        // 'resources/views/',
        // 'database/',
        // 'public/',
        // 'storage/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Secret Patterns - DEVELOPMENT
    |--------------------------------------------------------------------------
    |
    | Basic patterns to catch obvious hardcoded secrets.
    |
    */
    'secret_patterns' => [
        // Essential patterns
        '*_KEY',
        '*_SECRET',
        '*_TOKEN',
        '*_PASSWORD',

        // Common API patterns
        'API_*',
        'STRIPE_*',
        'PAYPAL_*',

        // Development-specific patterns
        'TEST_*_SECRET',
        'DEV_*_KEY',

        // Remove overly broad patterns that may cause false positives
        // '*_PASS',
        // 'CLIENT_*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Required Environment Variables - DEVELOPMENT
    |--------------------------------------------------------------------------
    |
    | Minimal required variables for development.
    |
    */
    'required_env_vars' => [
        // Laravel essentials
        'APP_KEY',
        'APP_ENV',
        'APP_DEBUG',
        'APP_URL',

        // Database (basic)
        'DB_CONNECTION',
        'DB_HOST',
        'DB_DATABASE',
        'DB_USERNAME',

        // Optional in development
        // 'DB_PASSWORD',     // May be empty in local development
        // 'REDIS_HOST',      // May not be used
        // 'MAIL_HOST',       // May use log driver
    ],

    /*
    |--------------------------------------------------------------------------
    | Sensitive Files - DEVELOPMENT
    |--------------------------------------------------------------------------
    |
    | Basic sensitive files (reduced list for development).
    |
    */
    'sensitive_files' => [
        '.env',
        '.env.local',
        'composer.json',
        'package.json',

        // Keep development files accessible
        // 'README.md',       // Useful for developers
        // '.env.example',    // Template file
        // 'phpunit.xml',     // Test configuration
    ],

    /*
    |--------------------------------------------------------------------------
    | Development-Specific Settings
    |--------------------------------------------------------------------------
    */

    // Don't fail on errors in development - just warn
    'fail_on_critical' => false,

    // Minimal logging in development
    'log_results' => false,

    // No caching in development for immediate feedback
    'cache_results' => false,

    // Development performance settings
    'scan_timeout' => 30,           // Shorter timeout
    'max_file_size' => 1024 * 1024, // Skip large files (1MB)

    // Development notifications (disable for less noise)
    'report_to_sentry' => false,
    'webhook_url' => null,

    /*
    |--------------------------------------------------------------------------
    | Developer Convenience Features
    |--------------------------------------------------------------------------
    */

    // Show detailed information in development
    'show_rule_details' => true,
    'show_recommendations' => true,
    'show_file_paths' => true,

    // Development-friendly error handling
    'continue_on_error' => true,
    'suppress_warnings' => false,

    /*
    |--------------------------------------------------------------------------
    | Custom Rules Configuration
    |--------------------------------------------------------------------------
    */
    'custom_rules_path' => 'app/SafeguardRules', // app_path('SafeguardRules'),
    'custom_rules_namespace' => 'App\\SafeguardRules',

    // Auto-load custom rules in development
    'auto_load_custom_rules' => true,

    /*
    |--------------------------------------------------------------------------
    | Development Testing Configuration
    |--------------------------------------------------------------------------
    */

    // Enable rule testing mode
    'testing_mode' => env('SAFEGUARD_TESTING', false),

    // Mock external services in development
    'mock_external_services' => true,

    // Skip expensive checks in development
    'skip_expensive_checks' => [
        'file_content_scanning',
        'network_connectivity_checks',
        'external_api_validation',
    ],

    /*
    |--------------------------------------------------------------------------
    | IDE Integration
    |--------------------------------------------------------------------------
    */

    // Generate IDE-friendly output
    'ide_integration' => [
        'phpstorm' => [
            'enabled' => true,
            'format' => 'xml',
            'output_path' => 'storage/safeguard/phpstorm.xml', // storage_path('safeguard/phpstorm.xml'),
        ],
        'vscode' => [
            'enabled' => true,
            'format' => 'json',
            'output_path' => '.vscode/safeguard.json',
        ],
    ],
];
