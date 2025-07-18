<?php

declare(strict_types=1);

/**
 * Production Configuration Example
 *
 * This configuration is optimized for production environments
 * with strict security requirements.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Security Rules Configuration - PRODUCTION
    |--------------------------------------------------------------------------
    |
    | Strict security rules for production environments.
    | All critical security checks are enabled.
    |
    */
    'rules' => [
        // Environment & Configuration Rules - CRITICAL
        'app-debug-false-in-production' => true,
        'app-key-is-set' => true,
        'env-has-all-required-keys' => true,
        'no-secrets-in-code' => true,

        // Security Rules - CRITICAL
        'csrf-enabled' => true,
        'composer-package-security' => true,

        // File System Rules - CRITICAL
        'env-file-permissions' => true,

        // Database Security Rules - CRITICAL for production
        'database-connection-encrypted' => true,
        'database-credentials-not-default' => true,
        'database-backup-security' => true,
        'database-query-logging' => true,

        // Authentication Security Rules - CRITICAL
        'password-policy-compliance' => true,
        'two-factor-auth-enabled' => true,
        'session-security-settings' => true,

        // Encryption Security Rules - CRITICAL
        'encryption-key-rotation' => true,
        'sensitive-data-encryption' => true,
        'secure_cookies_in_production' => true,
        'https_enforced_in_production' => true,

        // Custom Rules (if you have them)
        'database-security-check' => true,
        'api-security-check' => true,
        'file-upload-security' => true,
        'third-party-service-security' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Environment-Specific Rules
    |--------------------------------------------------------------------------
    |
    | Production environment runs all critical security checks.
    |
    */
    'environments' => [
        'production' => [
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
            'https_enforced_in_production',
            'env_file_permissions',
            'sensitive_files_hidden',
            'storage_writable',
            'database_security_check',
            'api_security_check',
            'file_upload_security',
            'third_party_service_security',
        ],

        // Staging should mirror production
        'staging' => [
            'env_debug_false_in_production',
            'app_key_is_set',
            'env_has_all_required_keys',
            'no_secrets_in_code',
            'csrf_enabled',
            'secure_cookies_in_production',
            'env_file_permissions',
            'sensitive_files_hidden',
            'storage_writable',
            'database_security_check',
            'api_security_check',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Scan Paths - PRODUCTION
    |--------------------------------------------------------------------------
    |
    | Comprehensive scanning for production security audit.
    |
    */
    'scan_paths' => [
        'app/',
        'config/',
        'routes/',
        'resources/views/',
        'database/seeders/',
        'database/factories/',
        'bootstrap/',
        'public/',
        'storage/app/public/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Secret Patterns - PRODUCTION
    |--------------------------------------------------------------------------
    |
    | Comprehensive patterns to detect hardcoded secrets.
    |
    */
    'secret_patterns' => [
        // Generic patterns
        '*_KEY',
        '*_SECRET',
        '*_TOKEN',
        '*_PASSWORD',
        '*_PASS',

        // API patterns
        'API_*',
        'CLIENT_*',

        // Cloud services
        'AWS_*',
        'AZURE_*',
        'GCP_*',
        'GOOGLE_*',

        // Payment services
        'STRIPE_*',
        'PAYPAL_*',
        'SQUARE_*',

        // Communication services
        'TWILIO_*',
        'SENDGRID_*',
        'MAILGUN_*',
        'PUSHER_*',

        // Social media
        'FACEBOOK_*',
        'TWITTER_*',
        'GITHUB_*',
        'LINKEDIN_*',

        // Databases
        'REDIS_*',
        'MONGO_*',
        'ELASTIC_*',

        // Monitoring & Analytics
        'SENTRY_*',
        'BUGSNAG_*',
        'MIXPANEL_*',
        'ANALYTICS_*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Required Environment Variables - PRODUCTION
    |--------------------------------------------------------------------------
    |
    | Critical environment variables that must be present in production.
    |
    */
    'required_env_vars' => [
        // Laravel core
        'APP_KEY',
        'APP_ENV',
        'APP_DEBUG',
        'APP_URL',

        // Database
        'DB_CONNECTION',
        'DB_HOST',
        'DB_PORT',
        'DB_DATABASE',
        'DB_USERNAME',
        'DB_PASSWORD',

        // Cache
        'CACHE_DRIVER',
        'REDIS_HOST',
        'REDIS_PASSWORD',
        'REDIS_PORT',

        // Session & Queue
        'SESSION_DRIVER',
        'QUEUE_CONNECTION',

        // Mail
        'MAIL_MAILER',
        'MAIL_HOST',
        'MAIL_PORT',
        'MAIL_USERNAME',
        'MAIL_PASSWORD',
        'MAIL_ENCRYPTION',
        'MAIL_FROM_ADDRESS',
        'MAIL_FROM_NAME',

        // File storage
        'FILESYSTEM_DISK',

        // Security
        'SESSION_SECURE_COOKIE',
        'SESSION_SAME_SITE',

        // Logging
        'LOG_CHANNEL',
        'LOG_LEVEL',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sensitive Files - PRODUCTION
    |--------------------------------------------------------------------------
    |
    | Files that must not be accessible via web requests in production.
    |
    */
    'sensitive_files' => [
        // Environment files
        '.env',
        '.env.example',
        '.env.local',
        '.env.production',
        '.env.staging',

        // Composer files
        'composer.json',
        'composer.lock',
        'composer.phar',

        // Package files
        'package.json',
        'package-lock.json',
        'yarn.lock',

        // Build files
        'webpack.mix.js',
        'vite.config.js',
        'tailwind.config.js',

        // Laravel files
        'artisan',
        'server.php',

        // Testing files
        'phpunit.xml',
        'phpunit.xml.dist',
        'pest.php',

        // Documentation
        'README.md',
        'CHANGELOG.md',
        'LICENSE.md',
        'CONTRIBUTING.md',

        // Configuration files
        'phpstan.neon',
        'pint.json',
        'rector.php',
        '.gitignore',
        '.gitattributes',

        // IDE files
        '.editorconfig',
        '.php-cs-fixer.php',

        // Docker files
        'Dockerfile',
        'docker-compose.yml',
        'docker-compose.yaml',

        // CI/CD files
        '.github/',
        '.gitlab-ci.yml',
        'Jenkinsfile',
        'azure-pipelines.yml',
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Rules Configuration
    |--------------------------------------------------------------------------
    */
    'custom_rules_path' => 'app/SafeguardRules', // app_path('SafeguardRules'),
    'custom_rules_namespace' => 'App\\SafeguardRules',

    /*
    |--------------------------------------------------------------------------
    | Production-Specific Settings
    |--------------------------------------------------------------------------
    */

    // Fail fast in production - don't continue if critical issues found
    'fail_on_critical' => true,

    // Enable detailed logging for security audits
    'log_results' => true,
    'log_channel' => 'security',

    // Performance settings for production
    'cache_results' => true,
    'cache_ttl' => 3600, // 1 hour

    // Security reporting
    'report_to_sentry' => env('SAFEGUARD_REPORT_TO_SENTRY', false),
    'webhook_url' => env('SAFEGUARD_WEBHOOK_URL'),
];
