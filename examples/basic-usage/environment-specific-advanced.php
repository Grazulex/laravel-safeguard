<?php

declare(strict_types=1);

/**
 * Advanced Environment-Specific Security Checks Example
 *
 * This example demonstrates advanced usage of environment-specific
 * security checks with the --env-rules option.
 */

echo "🔐 Laravel Safeguard - Advanced Environment-Specific Example\n";
echo "==========================================================\n\n";

echo "This example shows advanced environment-specific security checking.\n\n";

echo "1. Using environment-specific rules only (--env-rules):\n";
echo "   php artisan safeguard:check --env-rules --env=production\n\n";

echo "   This runs ONLY the rules defined in config/safeguard.php under:\n";
echo "   'environments' => ['production' => [...]])\n\n";

echo "   Expected output:\n";
echo "   ---\n";
echo "   🔐 Laravel Safeguard Security Check\n";
echo "   ═══════════════════════════════════════\n";
echo "   \n";
echo "   Environment: production\n";
echo "   \n";
echo "   ✅ APP_DEBUG is false in production\n";
echo "   ✅ APP_KEY is set\n";
echo "   ✅ .env file has secure permissions\n";
echo "   ✅ Database connection uses encryption\n";
echo "   ✅ Database credentials are not default\n";
echo "   ✅ Password policy meets security standards\n";
echo "   ⚠️  Encryption key rotation policy not configured\n";
echo "   \n";
echo "   ═══════════════════════════════════════\n";
echo "   🎯 1 warning found, 6 checks passed\n";
echo "   ---\n\n";

echo "2. Compare with running ALL rules for the environment:\n";
echo "   php artisan safeguard:check --env=production\n\n";

echo "   This runs ALL enabled rules, not just environment-specific ones.\n\n";

echo "3. Environment-specific rules with detailed output:\n";
echo "   php artisan safeguard:check --env-rules --env=production --details\n\n";

echo "4. Different environments have different rule sets:\n\n";

echo "   📋 Production environment rules (strict security):\n";
echo "   • app-debug-false-in-production\n";
echo "   • app-key-is-set\n";
echo "   • env-file-permissions\n";
echo "   • database-connection-encrypted\n";
echo "   • database-credentials-not-default\n";
echo "   • password-policy-compliance\n";
echo "   • encryption-key-rotation\n\n";

echo "   📋 Staging environment rules (moderate security):\n";
echo "   • app-debug-false-in-production\n";
echo "   • app-key-is-set\n";
echo "   • csrf-enabled\n";
echo "   • database-connection-encrypted\n\n";

echo "   📋 Local environment rules (development-friendly):\n";
echo "   • app-key-is-set\n";
echo "   • env-has-all-required-keys\n\n";

echo "5. Configuration example for custom environment rules:\n";
echo "   ---\n";
echo "   // config/safeguard.php\n";
echo "   'environments' => [\n";
echo "       'production' => [\n";
echo "           'app-debug-false-in-production',\n";
echo "           'app-key-is-set',\n";
echo "           'database-connection-encrypted',\n";
echo "           'password-policy-compliance',\n";
echo "       ],\n";
echo "       'staging' => [\n";
echo "           'app-debug-false-in-production',\n";
echo "           'csrf-enabled',\n";
echo "       ],\n";
echo "       'development' => [\n";
echo "           'app-key-is-set',\n";
echo "           'env-has-all-required-keys',\n";
echo "       ],\n";
echo "   ],\n";
echo "   ---\n\n";

echo "6. Practical use cases:\n\n";

echo "   🚀 Pre-deployment checks:\n";
echo "   php artisan safeguard:check --env-rules --env=production --fail-on-error\n\n";

echo "   🔄 Staging validation:\n";
echo "   php artisan safeguard:check --env-rules --env=staging --ci\n\n";

echo "   🛠️ Development environment health:\n";
echo "   php artisan safeguard:check --env-rules --env=local --show-all\n\n";

echo "7. CI/CD integration with environment-specific rules:\n";
echo "   ---\n";
echo "   # In your CI/CD pipeline\n";
echo "   - name: Production security check\n";
echo "     run: |\n";
echo "       php artisan safeguard:check \\\n";
echo "         --env-rules \\\n";
echo "         --env=production \\\n";
echo "         --format=json \\\n";
echo "         --fail-on-error > security-prod.json\n";
echo "   \n";
echo "   - name: Staging security check\n";
echo "     run: |\n";
echo "       php artisan safeguard:check \\\n";
echo "         --env-rules \\\n";
echo "         --env=staging \\\n";
echo "         --ci --details\n";
echo "   ---\n\n";

echo "💡 Pro Tips:\n";
echo "• Use --env-rules for focused checks specific to each environment\n";
echo "• Configure stricter rules for production, more lenient for development\n";
echo "• Environment-specific rules are perfect for deployment gates\n";
echo "• Combine with --fail-on-error in CI/CD for automated blocking\n\n";

echo "Example completed! ✅\n";