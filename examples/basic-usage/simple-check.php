<?php

declare(strict_types=1);

/**
 * Basic Laravel Safeguard Usage Example
 *
 * This example demonstrates the most basic usage of Laravel Safeguard
 * for running security checks in a Laravel application.
 */

// Note: In a real Laravel application, this would be:
// require_once __DIR__ . '/../../../vendor/autoload.php';
// For this example, we're just demonstrating the concept

// Note: This example demonstrates the concept
// In a real Laravel application, you would run: php artisan safeguard:check

echo "🔐 Laravel Safeguard - Basic Usage Example\n";
echo "==========================================\n\n";

echo "This example shows how to run a basic security check.\n";
echo "In a real Laravel application, you would run:\n\n";

echo "  php artisan safeguard:check\n\n";

echo "Expected output:\n";
echo "---\n";
echo "🔐 Laravel Safeguard Security Check\n";
echo "═══════════════════════════════════════\n";
echo "\n";
echo "Environment: local\n";
echo "\n";
echo "✅ APP_KEY is set\n";
echo "✅ Storage directories are writable\n";
echo "✅ CSRF protection enabled\n";
echo "⚠️  APP_DEBUG is enabled (acceptable in local environment)\n";
echo "\n";
echo "═══════════════════════════════════════\n";
echo "🎯 All checks passed! (4 checks)\n";
echo "---\n\n";

echo "For detailed information about failures, use:\n";
echo "  php artisan safeguard:check --details\n\n";

echo "For detailed information about all checks, use:\n";
echo "  php artisan safeguard:check --show-all\n\n";

echo "For JSON output, use:\n";
echo "  php artisan safeguard:check --format=json\n\n";

echo "For CI-friendly output, use:\n";
echo "  php artisan safeguard:check --ci\n\n";

echo "To fail on errors (useful for CI/CD), use:\n";
echo "  php artisan safeguard:check --fail-on-error\n\n";

echo "To run checks for a specific environment:\n";
echo "  php artisan safeguard:check --env=production\n\n";

echo "Example completed! ✅\n";
