<?php

declare(strict_types=1);

/**
 * Laravel Safeguard Make Rule Command Example
 *
 * This example demonstrates how to create custom security rules
 * using the safeguard:make-rule command with different severity levels.
 */
echo "🔧 Laravel Safeguard - Make Rule Command Example\n";
echo "=================================================\n\n";

echo "This example shows how to create custom security rules with different severity levels.\n\n";

echo "1. Create a rule with default severity (info):\n";
echo "   php artisan safeguard:make-rule CustomSecurityRule\n\n";

echo "   Expected output:\n";
echo "   ---\n";
echo "   Rule created successfully.\n";
echo "   Don't forget to register your rule in a service provider or configure it in config/safeguard.php\n";
echo "   ---\n\n";

echo "   Generated file: app/Safeguard/Rules/CustomSecurityRule.php\n";
echo "   Default severity: info\n\n";

echo "2. Create a rule with warning severity:\n";
echo "   php artisan safeguard:make-rule DatabaseSecurityRule --severity=warning\n\n";

echo "   Generated rule will have severity(): return 'warning';\n\n";

echo "3. Create a rule with error severity:\n";
echo "   php artisan safeguard:make-rule ApiSecurityRule --severity=error\n\n";

echo "   Generated rule will have severity(): return 'error';\n\n";

echo "4. Example of generated rule structure:\n";
echo "   ---\n";
echo "   <?php\n";
echo "   \n";
echo "   declare(strict_types=1);\n";
echo "   \n";
echo "   namespace App\\Safeguard\\Rules;\n";
echo "   \n";
echo "   use Grazulex\\LaravelSafeguard\\Contracts\\SafeguardRule;\n";
echo "   use Grazulex\\LaravelSafeguard\\SafeguardResult;\n";
echo "   \n";
echo "   class DatabaseSecurityRule implements SafeguardRule\n";
echo "   {\n";
echo "       public function id(): string\n";
echo "       {\n";
echo "           return 'database-security-rule';\n";
echo "       }\n";
echo "   \n";
echo "       public function description(): string\n";
echo "       {\n";
echo "           return 'Checks database security rule';\n";
echo "       }\n";
echo "   \n";
echo "       public function check(): SafeguardResult\n";
echo "       {\n";
echo "           // TODO: Implement your rule logic here\n";
echo "           \n";
echo "           // Example of a passing check:\n";
echo "           // return SafeguardResult::pass('Check passed');\n";
echo "           \n";
echo "           // Example of a failing check:\n";
echo "           // return SafeguardResult::fail('Check failed', ['details' => 'Additional context']);\n";
echo "           \n";
echo "           return SafeguardResult::pass('Rule not yet implemented');\n";
echo "       }\n";
echo "   \n";
echo "       public function appliesToEnvironment(string \$environment): bool\n";
echo "       {\n";
echo "           // Return true to run in all environments, or customize as needed\n";
echo "           return true;\n";
echo "       }\n";
echo "   \n";
echo "       public function severity(): string\n";
echo "       {\n";
echo "           return 'warning'; // This matches the --severity=warning option\n";
echo "       }\n";
echo "   }\n";
echo "   ---\n\n";

echo "5. Available severity levels:\n";
echo "   • info    - Informational messages (default)\n";
echo "   • warning - Warnings that should be reviewed\n";
echo "   • error   - Errors that should be fixed\n\n";

echo "6. Real-world examples by severity:\n\n";

echo "   📘 INFO severity examples:\n";
echo "   php artisan safeguard:make-rule ConfigurationDocumentationRule --severity=info\n";
echo "   php artisan safeguard:make-rule PerformanceOptimizationRule --severity=info\n\n";

echo "   📙 WARNING severity examples:\n";
echo "   php artisan safeguard:make-rule OutdatedDependencyRule --severity=warning\n";
echo "   php artisan safeguard:make-rule UnusedConfigurationRule --severity=warning\n\n";

echo "   📕 ERROR severity examples:\n";
echo "   php artisan safeguard:make-rule DatabaseConnectionSecurityRule --severity=error\n";
echo "   php artisan safeguard:make-rule AuthenticationSecurityRule --severity=error\n";
echo "   php artisan safeguard:make-rule ProductionSecretsRule --severity=error\n\n";

echo "7. Next steps after creating a rule:\n";
echo "   • Implement the check() method with your security logic\n";
echo "   • Add the rule to config/safeguard.php configuration\n";
echo "   • Test the rule with: php artisan safeguard:check\n";
echo "   • Optionally register the rule in a service provider\n\n";

echo "8. Testing your custom rule:\n";
echo "   # Enable your rule in config/safeguard.php\n";
echo "   'your-custom-rule-id' => true,\n\n";
echo "   # Run checks to test\n";
echo "   php artisan safeguard:check --details\n\n";

echo "💡 Pro Tips:\n";
echo "• Use appropriate severity levels based on impact\n";
echo "• Choose descriptive rule names that indicate their purpose\n";
echo "• Implement detailed error messages in your check() method\n";
echo "• Consider environment-specific rules in appliesToEnvironment()\n\n";

echo "Example completed! ✅\n";
