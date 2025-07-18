<?php

declare(strict_types=1);

/**
 * Environment-Specific Security Checks Example
 *
 * This example demonstrates how to run security checks for different environments
 * with Laravel Safeguard.
 */
echo "🔐 Laravel Safeguard - Environment-Specific Checks\n";
echo "==================================================\n\n";

echo "## Usage Modes\n\n";

echo "1. **All Enabled Rules** (default):\n";
echo "   php artisan safeguard:check --env=production\n";
echo "   Runs all enabled rules but provides environment context\n\n";

echo "2. **Environment-Specific Rules Only**:\n";
echo "   php artisan safeguard:check --env=production --env-rules\n";
echo "   Runs only rules configured for the specific environment\n\n";

echo "## Environment Configuration\n\n";

echo "Different environments require different security priorities:\n\n";

// Example commands for different environments
$environments = [
    'local' => [
        'description' => 'Development environment with relaxed rules',
        'command' => 'php artisan safeguard:check --env=local',
        'rules' => [
            'app-key-is-set' => true,
            'env-has-all-required-keys' => true,
            'app-debug-false-in-production' => false, // Allowed in local
        ],
    ],
    'staging' => [
        'description' => 'Staging environment with moderate security',
        'command' => 'php artisan safeguard:check --env=staging',
        'rules' => [
            'app-key-is-set' => true,
            'app-debug-false-in-production' => true,
            'csrf-enabled' => true,
        ],
    ],
    'production' => [
        'description' => 'Production environment with strict security',
        'command' => 'php artisan safeguard:check --env=production --fail-on-error',
        'rules' => [
            'app-debug-false-in-production' => true,
            'app-key-is-set' => true,
            'env-file-permissions' => true,
            'database-connection-encrypted' => true,
            'password-policy-compliance' => true,
        ],
    ],
];

foreach ($environments as $env => $config) {
    echo "## {$env} Environment\n";
    echo "{$config['description']}\n\n";
    echo "Command:\n";
    echo "  {$config['command']}\n\n";
    echo "Key rules enabled:\n";
    foreach ($config['rules'] as $rule => $enabled) {
        $status = $enabled ? '✅' : '❌';
        echo "  {$status} {$rule}\n";
    }
    echo "\n";

    echo "Expected output for {$env}:\n";
    echo "---\n";
    echo "🔐 Laravel Safeguard Security Check\n";
    echo "═══════════════════════════════════════\n";
    echo "\n";
    echo "Environment: {$env}\n";
    echo "\n";

    // Simulate some example output based on environment
    switch ($env) {
        case 'local':
            echo "✅ APP_KEY is set\n";
            echo "✅ Environment variables present\n";
            echo "⚠️  APP_DEBUG is enabled (acceptable in local)\n";
            echo "\n";
            echo "═══════════════════════════════════════\n";
            echo "🎯 All checks passed! (3 checks)\n";
            break;

        case 'staging':
            echo "✅ APP_KEY is set\n";
            echo "✅ APP_DEBUG is false\n";
            echo "✅ CSRF protection enabled\n";
            echo "⚠️  Database encryption not required in staging\n";
            echo "\n";
            echo "═══════════════════════════════════════\n";
            echo "🎯 All checks passed! (3 checks)\n";
            break;

        case 'production':
            echo "✅ APP_KEY is set\n";
            echo "✅ APP_DEBUG is false\n";
            echo "✅ .env file permissions secure\n";
            echo "✅ Database connection encrypted\n";
            echo "✅ Password policy compliant\n";
            echo "\n";
            echo "═══════════════════════════════════════\n";
            echo "🎯 All checks passed! (5 checks)\n";
            break;
    }
    echo "---\n\n";
}

echo "## Configuration Example\n\n";
echo "In config/safeguard.php:\n\n";
echo "```php\n";
echo "'environments' => [\n";
echo "    'local' => [\n";
echo "        'app-key-is-set',\n";
echo "        'env-has-all-required-keys',\n";
echo "    ],\n";
echo "    'staging' => [\n";
echo "        'app-debug-false-in-production',\n";
echo "        'app-key-is-set',\n";
echo "        'csrf-enabled',\n";
echo "    ],\n";
echo "    'production' => [\n";
echo "        'app-debug-false-in-production',\n";
echo "        'app-key-is-set',\n";
echo "        'env-file-permissions',\n";
echo "        'database-connection-encrypted',\n";
echo "        'password-policy-compliance',\n";
echo "    ],\n";
echo "],\n";
echo "```\n\n";

echo "Example completed! ✅\n";
