<?php

declare(strict_types=1);

/**
 * Laravel Safeguard Detailed Output Example
 *
 * This example demonstrates how to get detailed information about
 * security check results using the --details and --show-all options.
 */
echo "🔐 Laravel Safeguard - Detailed Output Example\n";
echo "===============================================\n\n";

echo "This example shows how to get detailed information about security checks.\n\n";

echo "1. Show details for failed checks only:\n";
echo "   php artisan safeguard:check --details\n\n";

echo "   Expected output:\n";
echo "   ---\n";
echo "   🔐 Laravel Safeguard Security Check\n";
echo "   ═══════════════════════════════════════\n";
echo "   \n";
echo "   Environment: production\n";
echo "   \n";
echo "   ✅ APP_KEY is set\n";
echo "   ❌ APP_DEBUG is true in production\n";
echo "      ⚙️ Current Setting: true\n";
echo "      💡 Recommendation: Set APP_DEBUG=false in production environment\n";
echo "      ⚠️ Security Impact: Debug mode exposes sensitive application information\n";
echo "   \n";
echo "   ❌ Hardcoded secret found in config/services.php\n";
echo "      📁 File Path: config/services.php\n";
echo "      📋 Detected Secrets:\n";
echo "        • STRIPE_SECRET on line 15\n";
echo "        • API_TOKEN on line 23\n";
echo "      💡 Recommendation: Move secrets to environment variables\n";
echo "   \n";
echo "   ✅ CSRF protection enabled\n";
echo "   \n";
echo "   ═══════════════════════════════════════\n";
echo "   🎯 2 issues found, 2 checks passed\n";
echo "   ---\n\n";

echo "2. Show details for ALL checks (passed and failed):\n";
echo "   php artisan safeguard:check --show-all\n\n";

echo "   Expected output:\n";
echo "   ---\n";
echo "   🔐 Laravel Safeguard Security Check\n";
echo "   ═══════════════════════════════════════\n";
echo "   \n";
echo "   Environment: production\n";
echo "   \n";
echo "   ✅ APP_KEY is set\n";
echo "      📌 Status: Application key is properly configured\n";
echo "      💡 Recommendation: Rotate key periodically for enhanced security\n";
echo "   \n";
echo "   ❌ APP_DEBUG is true in production\n";
echo "      ⚙️ Current Setting: true\n";
echo "      💡 Recommendation: Set APP_DEBUG=false in production environment\n";
echo "      ⚠️ Security Impact: Debug mode exposes sensitive application information\n";
echo "   \n";
echo "   ✅ CSRF protection enabled\n";
echo "      📌 Status: CSRF middleware is active\n";
echo "      💡 Recommendation: Ensure all forms include CSRF tokens\n";
echo "   \n";
echo "   ═══════════════════════════════════════\n";
echo "   🎯 1 issue found, 2 checks passed\n";
echo "   ---\n\n";

echo "3. Combine with environment-specific checks:\n";
echo "   php artisan safeguard:check --env=production --details\n\n";

echo "4. Combine with JSON output for detailed automation:\n";
echo "   php artisan safeguard:check --format=json --show-all\n\n";

echo "   Expected JSON output (excerpt):\n";
echo "   ---\n";
echo "   {\n";
echo "     \"status\": \"failed\",\n";
echo "     \"environment\": \"production\",\n";
echo "     \"summary\": {\n";
echo "       \"total\": 3,\n";
echo "       \"passed\": 2,\n";
echo "       \"failed\": 1\n";
echo "     },\n";
echo "     \"results\": [\n";
echo "       {\n";
echo "         \"rule\": \"app_debug_false_in_production\",\n";
echo "         \"status\": \"failed\",\n";
echo "         \"message\": \"APP_DEBUG is true in production\",\n";
echo "         \"severity\": \"error\",\n";
echo "         \"details\": {\n";
echo "           \"current_setting\": \"true\",\n";
echo "           \"recommendation\": \"Set APP_DEBUG=false in production\",\n";
echo "           \"security_impact\": \"Exposes sensitive information\"\n";
echo "         }\n";
echo "       }\n";
echo "     ]\n";
echo "   }\n";
echo "   ---\n\n";

echo "5. Common use cases:\n\n";

echo "   📋 Development troubleshooting:\n";
echo "   php artisan safeguard:check --details\n\n";

echo "   📋 Comprehensive audit:\n";
echo "   php artisan safeguard:check --show-all --env=production\n\n";

echo "   📋 CI/CD with detailed reports:\n";
echo "   php artisan safeguard:check --ci --details --fail-on-error\n\n";

echo "   📋 Automated report generation:\n";
echo "   php artisan safeguard:check --format=json --show-all > security-report.json\n\n";

echo "💡 Pro Tips:\n";
echo "• Use --details during development to understand failures\n";
echo "• Use --show-all for comprehensive security audits\n";
echo "• Combine with --format=json for automated processing\n";
echo "• The details include actionable recommendations\n\n";

echo "Example completed! ✅\n";
