<?php

/**
 * Environment-Specific Security Checks Example
 * 
 * This example demonstrates how to run security checks for different environments
 * with Laravel Safeguard.
 */

echo "🔐 Laravel Safeguard - Environment-Specific Checks\n";
echo "==================================================\n\n";

echo "Different environments require different security rules.\n";
echo "Laravel Safeguard supports environment-specific configurations.\n\n";

// Example commands for different environments
$environments = [
    'local' => [
        'description' => 'Development environment with relaxed rules',
        'command' => 'php artisan safeguard:check --env=local',
        'rules' => [
            'app_key_is_set' => true,
            'storage_writable' => true,
            'env_debug_false_in_production' => false, // Allowed in local
        ]
    ],
    'staging' => [
        'description' => 'Staging environment with moderate security',
        'command' => 'php artisan safeguard:check --env=staging',
        'rules' => [
            'app_key_is_set' => true,
            'env_debug_false_in_production' => true,
            'csrf_enabled' => true,
        ]
    ],
    'production' => [
        'description' => 'Production environment with strict security',
        'command' => 'php artisan safeguard:check --env=production --fail-on-error',
        'rules' => [
            'env_debug_false_in_production' => true,
            'secure_cookies_in_production' => true,
            'https_enforced_in_production' => true,
            'env_file_permissions' => true,
            'sensitive_files_hidden' => true,
        ]
    ]
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
            echo "✅ Storage directories are writable\n";
            echo "⚠️  APP_DEBUG is enabled (acceptable in local)\n";
            echo "\n";
            echo "═══════════════════════════════════════\n";
            echo "🎯 All checks passed! (3 checks)\n";
            break;
            
        case 'staging':
            echo "✅ APP_KEY is set\n";
            echo "✅ APP_DEBUG is false\n";
            echo "✅ CSRF protection enabled\n";
            echo "⚠️  HTTPS not enforced (rule disabled for staging)\n";
            echo "\n";
            echo "═══════════════════════════════════════\n";
            echo "🎯 All checks passed! (3 checks)\n";
            break;
            
        case 'production':
            echo "✅ APP_KEY is set\n";
            echo "✅ APP_DEBUG is false\n";
            echo "✅ Secure cookies configured\n";
            echo "✅ HTTPS enforced\n";
            echo "✅ .env file permissions secure\n";
            echo "✅ Sensitive files hidden\n";
            echo "\n";
            echo "═══════════════════════════════════════\n";
            echo "🎯 All checks passed! (6 checks)\n";
            break;
    }
    echo "---\n\n";
}

echo "## Configuration Example\n\n";
echo "In config/safeguard.php:\n\n";
echo "```php\n";
echo "'environments' => [\n";
echo "    'local' => [\n";
echo "        'app_key_is_set',\n";
echo "        'storage_writable',\n";
echo "    ],\n";
echo "    'staging' => [\n";
echo "        'env_debug_false_in_production',\n";
echo "        'app_key_is_set',\n";
echo "        'csrf_enabled',\n";
echo "    ],\n";
echo "    'production' => [\n";
echo "        'env_debug_false_in_production',\n";
echo "        'secure_cookies_in_production',\n";
echo "        'https_enforced_in_production',\n";
echo "        'env_file_permissions',\n";
echo "        'sensitive_files_hidden',\n";
echo "    ],\n";
echo "],\n";
echo "```\n\n";

echo "Example completed! ✅\n";