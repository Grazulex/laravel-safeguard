<?php

declare(strict_types=1);

/**
 * Complete Laravel Safeguard Feature Demonstration
 *
 * This comprehensive example demonstrates all major features and capabilities
 * of Laravel Safeguard in a real-world scenario.
 */

echo "🔐 Laravel Safeguard - Complete Feature Demonstration\n";
echo "====================================================\n\n";

echo "This example demonstrates all major Laravel Safeguard features in action.\n\n";

echo "## 1. BASIC SECURITY CHECKS\n";
echo "-----------------------------\n\n";

echo "🔹 Run all enabled security checks:\n";
echo "   php artisan safeguard:check\n\n";

echo "🔹 Check specific environment (runs all enabled rules for that env):\n";
echo "   php artisan safeguard:check --env=production\n\n";

echo "🔹 Use environment-specific rules only (subset of rules):\n";
echo "   php artisan safeguard:check --env=production --env-rules\n\n";

echo "## 2. OUTPUT FORMATS\n";
echo "---------------------\n\n";

echo "🔹 Human-readable CLI output (default):\n";
echo "   php artisan safeguard:check\n\n";

echo "🔹 Machine-readable JSON output:\n";
echo "   php artisan safeguard:check --format=json\n\n";

echo "🔹 CI-friendly output (no colors, compact):\n";
echo "   php artisan safeguard:check --ci\n\n";

echo "## 3. DETAILED INFORMATION\n";
echo "---------------------------\n\n";

echo "🔹 Show details for failed checks only:\n";
echo "   php artisan safeguard:check --details\n\n";

echo "🔹 Show details for all checks (passed and failed):\n";
echo "   php artisan safeguard:check --show-all\n\n";

echo "🔹 Combine detailed output with specific environments:\n";
echo "   php artisan safeguard:check --env=production --env-rules --show-all\n\n";

echo "## 4. RULE MANAGEMENT\n";
echo "----------------------\n\n";

echo "🔹 List all available rules:\n";
echo "   php artisan safeguard:list\n\n";

echo "🔹 Show only enabled rules:\n";
echo "   php artisan safeguard:list --enabled\n\n";

echo "🔹 Show rules for specific environment:\n";
echo "   php artisan safeguard:list --environment=production\n\n";

echo "🔹 Filter by severity level:\n";
echo "   php artisan safeguard:list --severity=error\n\n";

echo "## 5. CUSTOM RULES\n";
echo "-------------------\n\n";

echo "🔹 Create a custom rule with default severity (info):\n";
echo "   php artisan safeguard:make-rule CustomSecurityCheck\n\n";

echo "🔹 Create rule with specific severity:\n";
echo "   php artisan safeguard:make-rule DatabaseAuditRule --severity=error\n\n";

echo "🔹 Create warning-level rule:\n";
echo "   php artisan safeguard:make-rule PerformanceSecurityRule --severity=warning\n\n";

echo "## 6. CI/CD INTEGRATION\n";
echo "------------------------\n\n";

echo "🔹 Fail build on security issues:\n";
echo "   php artisan safeguard:check --fail-on-error\n\n";

echo "🔹 Complete CI/CD command:\n";
echo "   php artisan safeguard:check --env=production --env-rules --ci --fail-on-error\n\n";

echo "🔹 Generate reports for analysis:\n";
echo "   php artisan safeguard:check --format=json --show-all > security-report.json\n\n";

echo "## 7. CONFIGURATION FEATURES\n";
echo "------------------------------\n\n";

echo "The config/safeguard.php file includes:\n\n";

echo "✅ **Rule Configuration**: Enable/disable individual security rules\n";
echo "   'rules' => ['app-debug-false-in-production' => true, ...]\n\n";

echo "✅ **Environment-Specific Rules**: Different rule sets per environment\n";
echo "   'environments' => ['production' => ['rule1', 'rule2'], ...]\n\n";

echo "✅ **Custom Rules Path**: Configure where custom rules are stored\n";
echo "   'custom_rules_path' => app_path('SafeguardRules')\n\n";

echo "✅ **Scan Paths**: Define directories to scan for secrets\n";
echo "   'scan_paths' => ['app/', 'config/', 'routes/']\n\n";

echo "✅ **Secret Patterns**: Patterns to detect hardcoded secrets\n";
echo "   'secret_patterns' => ['*_KEY', '*_SECRET', 'API_*']\n\n";

echo "✅ **Required Environment Variables**: Variables that must exist\n";
echo "   'required_env_vars' => ['APP_KEY', 'DB_CONNECTION']\n\n";

echo "## 8. REAL-WORLD USE CASES\n";
echo "---------------------------\n\n";

echo "🚀 **Pre-deployment Security Gate**:\n";
echo "   php artisan safeguard:check --env=production --env-rules --fail-on-error\n\n";

echo "🔍 **Security Audit**:\n";
echo "   php artisan safeguard:check --show-all --format=json > full-audit.json\n\n";

echo "🛠️ **Development Environment Health Check**:\n";
echo "   php artisan safeguard:check --env=local --details\n\n";

echo "📋 **Generate Security Report**:\n";
echo "   php artisan safeguard:check --env=production --format=json --show-all | jq '.'\n\n";

echo "🎯 **Focus on Critical Issues Only**:\n";
echo "   php artisan safeguard:list --severity=error --enabled\n\n";

echo "## 9. AVAILABLE SECURITY RULES\n";
echo "--------------------------------\n\n";

echo "🔐 **Environment & Configuration**:\n";
echo "   • app-debug-false-in-production\n";
echo "   • env-has-all-required-keys\n";
echo "   • app-key-is-set\n";
echo "   • no-secrets-in-code\n\n";

echo "🛡️ **Security Rules**:\n";
echo "   • csrf-enabled\n";
echo "   • composer-package-security\n\n";

echo "🗄️ **Database Security**:\n";
echo "   • database-connection-encrypted\n";
echo "   • database-credentials-not-default\n";
echo "   • database-backup-security\n";
echo "   • database-query-logging\n\n";

echo "🔑 **Authentication Security**:\n";
echo "   • password-policy-compliance\n";
echo "   • two-factor-auth-enabled\n";
echo "   • session-security-settings\n\n";

echo "🔒 **Encryption & File Security**:\n";
echo "   • encryption-key-rotation\n";
echo "   • sensitive-data-encryption\n";
echo "   • env-file-permissions\n\n";

echo "## 10. EXAMPLE OUTPUT SNIPPETS\n";
echo "--------------------------------\n\n";

echo "✅ **Successful Check**:\n";
echo "```\n";
echo "🔐 Laravel Safeguard Security Check\n";
echo "═══════════════════════════════════════\n";
echo "\n";
echo "Environment: production\n";
echo "\n";
echo "✅ APP_DEBUG is false in production\n";
echo "✅ APP_KEY is set\n";
echo "✅ All required environment variables present\n";
echo "✅ CSRF protection enabled\n";
echo "\n";
echo "═══════════════════════════════════════\n";
echo "🎯 All checks passed! (4 checks)\n";
echo "```\n\n";

echo "❌ **Failed Check with Details**:\n";
echo "```\n";
echo "🔐 Laravel Safeguard Security Check\n";
echo "═══════════════════════════════════════\n";
echo "\n";
echo "Environment: production\n";
echo "\n";
echo "❌ APP_DEBUG is enabled in production environment\n";
echo "   ⚙️ Current Setting: true\n";
echo "   💡 Recommendation: Set APP_DEBUG=false in production\n";
echo "   ⚠️ Security Impact: Debug mode exposes sensitive information\n";
echo "\n";
echo "❌ Hardcoded secret found in config/services.php\n";
echo "   📁 File Path: config/services.php\n";
echo "   📋 Issues Found:\n";
echo "     🔍 STRIPE_SECRET detected on line 15\n";
echo "   💡 Recommendation: Move secrets to environment variables\n";
echo "\n";
echo "═══════════════════════════════════════\n";
echo "🎯 2 critical issues found\n";
echo "```\n\n";

echo "📊 **JSON Output Example**:\n";
echo "```json\n";
echo "{\n";
echo "  \"status\": \"failed\",\n";
echo "  \"environment\": \"production\",\n";
echo "  \"summary\": {\n";
echo "    \"total\": 4,\n";
echo "    \"passed\": 2,\n";
echo "    \"errors\": 2,\n";
echo "    \"warnings\": 0\n";
echo "  },\n";
echo "  \"results\": [\n";
echo "    {\n";
echo "      \"rule\": \"app-debug-false-in-production\",\n";
echo "      \"status\": \"failed\",\n";
echo "      \"message\": \"APP_DEBUG is enabled in production environment\",\n";
echo "      \"severity\": \"critical\"\n";
echo "    }\n";
echo "  ]\n";
echo "}\n";
echo "```\n\n";

echo "💡 **Pro Tips**:\n";
echo "• Start with basic checks, then add environment-specific rules\n";
echo "• Use --env-rules for focused environment checks\n";
echo "• Combine --details with specific environments for troubleshooting\n";
echo "• Integrate --fail-on-error in CI/CD pipelines\n";
echo "• Create custom rules for organization-specific requirements\n";
echo "• Use JSON format for automated report processing\n";
echo "• Regular security audits help maintain application security posture\n\n";

echo "🎯 **This comprehensive example covers all Laravel Safeguard features!**\n\n";

echo "Example completed! ✅\n";