<?php

declare(strict_types=1);

/**
 * Database Security Rule Example
 *
 * This example demonstrates how to create a custom security rule
 * that validates database security configuration.
 */

namespace App\SafeguardRules;

use Grazulex\LaravelSafeguard\Contracts\SafeguardRule;
use Grazulex\LaravelSafeguard\SafeguardResult;
use PDO;

class DatabaseSecurityRule implements SafeguardRule
{
    public function id(): string
    {
        return 'database-security-check';
    }

    public function description(): string
    {
        return 'Validates database security configuration including passwords, SSL, and connection settings';
    }

    public function check(): SafeguardResult
    {
        $issues = [];
        $recommendations = [];

        // Get database configuration
        $connections = config('database.connections', []);

        foreach ($connections as $name => $config) {
            // Skip non-MySQL connections for this example
            if (! in_array($config['driver'] ?? '', ['mysql', 'pgsql', 'sqlsrv'])) {
                continue;
            }

            // Check for default/weak passwords
            $password = $config['password'] ?? '';
            if (in_array($password, ['', 'password', 'root', '123456', 'admin'])) {
                $issues[] = "Connection '{$name}' uses a weak or default password";
                $recommendations[] = "Use a strong, unique password for database connection '{$name}'";
            }

            // Check for default usernames
            $username = $config['username'] ?? '';
            if (in_array($username, ['root', 'admin', 'sa']) && app()->environment('production')) {
                $issues[] = "Connection '{$name}' uses a default username in production";
                $recommendations[] = "Create a dedicated database user for production connection '{$name}'";
            }

            // Check SSL configuration for production
            if (app()->environment('production')) {
                $sslOptions = $config['options'] ?? [];
                $sslEnabled = $sslOptions[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] ?? false;

                if (! $sslEnabled) {
                    $issues[] = "Connection '{$name}' does not use SSL in production";
                    $recommendations[] = "Enable SSL for database connection '{$name}' in production";
                }
            }

            // Check for localhost in production
            $host = $config['host'] ?? '';
            if ($host === 'localhost' && app()->environment('production')) {
                $issues[] = "Connection '{$name}' uses localhost in production";
                $recommendations[] = "Use a proper database server hostname for production connection '{$name}'";
            }

            // Check for default ports
            $port = $config['port'] ?? null;
            $defaultPorts = [
                'mysql' => 3306,
                'pgsql' => 5432,
                'sqlsrv' => 1433,
            ];

            $driver = $config['driver'] ?? '';
            if (isset($defaultPorts[$driver]) && $port === $defaultPorts[$driver] && app()->environment('production')) {
                $issues[] = "Connection '{$name}' uses default port {$port} in production";
                $recommendations[] = "Consider using a non-default port for enhanced security on connection '{$name}'";
            }
        }

        // Return result based on findings
        if (empty($issues)) {
            return SafeguardResult::pass(
                'Database security configuration is acceptable',
                [
                    'connections_checked' => array_keys($connections),
                    'environment' => app()->environment(),
                ]
            );
        }

        // Determine severity based on environment and issue types
        $hasWeakPasswords = collect($issues)->contains(fn ($issue) => str_contains($issue, 'weak or default password'));
        $isProduction = app()->environment('production');

        if ($hasWeakPasswords || $isProduction) {
            return SafeguardResult::critical(
                'Critical database security issues detected',
                [
                    'issues' => $issues,
                    'recommendations' => $recommendations,
                    'environment' => app()->environment(),
                    'connections_checked' => array_keys($connections),
                ]
            );
        }

        return SafeguardResult::warning(
            'Database security concerns detected',
            [
                'issues' => $issues,
                'recommendations' => $recommendations,
                'environment' => app()->environment(),
                'connections_checked' => array_keys($connections),
            ]
        );

    }

    public function appliesToEnvironment(string $environment): bool
    {
        // Run in all environments, but severity may vary
        return true;
    }

    public function severity(): string
    {
        return app()->environment('production') ? 'critical' : 'warning';
    }
}

// Example usage demonstration
if (php_sapi_name() === 'cli') {
    echo "🔐 Database Security Rule Example\n";
    echo "=================================\n\n";

    echo "This custom rule validates database security configuration.\n\n";

    echo "## What it checks:\n";
    echo "- Weak or default passwords\n";
    echo "- Default usernames in production\n";
    echo "- SSL configuration for production\n";
    echo "- Use of localhost in production\n";
    echo "- Default database ports in production\n\n";

    echo "## To use this rule:\n\n";
    echo "1. Copy this file to app/SafeguardRules/DatabaseSecurityRule.php\n";
    echo "2. Add to config/safeguard.php:\n\n";
    echo "   'rules' => [\n";
    echo "       // ... other rules\n";
    echo "       'database_security_check' => true,\n";
    echo "   ],\n\n";
    echo "3. Run the security check:\n";
    echo "   php artisan safeguard:check\n\n";

    echo "## Example output:\n\n";
    echo "❌ Critical database security issues detected\n";
    echo "   Issues:\n";
    echo "   - Connection 'mysql' uses a weak or default password\n";
    echo "   - Connection 'mysql' does not use SSL in production\n";
    echo "   Recommendations:\n";
    echo "   - Use a strong, unique password for database connection 'mysql'\n";
    echo "   - Enable SSL for database connection 'mysql' in production\n\n";

    echo "Example completed! ✅\n";
}
