<?php

declare(strict_types=1);

/**
 * Advanced Database Security Rule Example
 *
 * This example demonstrates how to create a comprehensive custom security rule
 * that validates multiple aspects of database security configuration.
 */

namespace App\SafeguardRules;

use Grazulex\LaravelSafeguard\Contracts\SafeguardRule;
use Grazulex\LaravelSafeguard\SafeguardResult;
use PDO;

class AdvancedDatabaseSecurityRule implements SafeguardRule
{
    /**
     * List of weak passwords to check against
     */
    private array $weakPasswords = [
        '',
        'password',
        'root',
        'admin',
        '123456',
        'password123',
        'admin123',
        'root123',
        'laravel',
        'secret',
        'default',
    ];

    /**
     * Required SSL modes for secure connections
     */
    private array $secureSslModes = [
        'require',
        'verify-ca',
        'verify-full',
    ];

    public function id(): string
    {
        return 'advanced-database-security';
    }

    public function description(): string
    {
        return 'Comprehensive database security validation including SSL, credentials, and configuration';
    }

    public function check(): SafeguardResult
    {
        $issues = [];
        $recommendations = [];
        $securityScore = 100;

        // Get all database connections
        $connections = config('database.connections', []);

        if (empty($connections)) {
            return SafeguardResult::critical(
                'No database connections configured',
                ['recommendation' => 'Configure at least one database connection']
            );
        }

        foreach ($connections as $name => $config) {
            $connectionIssues = $this->validateConnection($name, $config);

            if (! empty($connectionIssues['issues'])) {
                $issues = array_merge($issues, $connectionIssues['issues']);
                $recommendations = array_merge($recommendations, $connectionIssues['recommendations']);
                $securityScore -= $connectionIssues['score_penalty'];
            }
        }

        // Additional environment-specific checks
        $envIssues = $this->validateEnvironmentSpecificSecurity();
        if (! empty($envIssues['issues'])) {
            $issues = array_merge($issues, $envIssues['issues']);
            $recommendations = array_merge($recommendations, $envIssues['recommendations']);
            $securityScore -= $envIssues['score_penalty'];
        }

        // Determine result based on issues found
        if (empty($issues)) {
            return SafeguardResult::pass(
                'All database security checks passed',
                [
                    'connections_checked' => count($connections),
                    'security_score' => $securityScore,
                    'ssl_enabled' => $this->countSslEnabledConnections($connections),
                ]
            );
        }

        $severity = $this->determineSeverity($issues, $securityScore);

        return SafeguardResult::fail(
            sprintf('Found %d database security issues', count($issues)),
            $severity,
            [
                'issues' => $issues,
                'recommendations' => $recommendations,
                'security_score' => max(0, $securityScore),
                'connections_checked' => count($connections),
            ]
        );
    }

    public function appliesToEnvironment(string $environment): bool
    {
        // Apply to all environments, but with different severity
        return true;
    }

    public function severity(): string
    {
        return 'critical';
    }

    /**
     * Validate individual database connection
     */
    private function validateConnection(string $name, array $config): array
    {
        $issues = [];
        $recommendations = [];
        $scorePenalty = 0;

        // Skip non-database connections (like redis, etc.)
        if (! in_array($config['driver'] ?? '', ['mysql', 'pgsql', 'sqlsrv', 'sqlite'])) {
            return ['issues' => [], 'recommendations' => [], 'score_penalty' => 0];
        }

        // 1. Password strength check
        $password = $config['password'] ?? '';
        if (in_array(mb_strtolower($password), array_map('strtolower', $this->weakPasswords))) {
            $issues[] = "Connection '{$name}' uses a weak or default password";
            $recommendations[] = "Use a strong, unique password for connection '{$name}'";
            $scorePenalty += 25;
        }

        // 2. SSL/TLS validation
        if ($this->shouldRequireSSL($name)) {
            $sslIssues = $this->validateSSLConfiguration($name, $config);
            $issues = array_merge($issues, $sslIssues['issues']);
            $recommendations = array_merge($recommendations, $sslIssues['recommendations']);
            $scorePenalty += $sslIssues['score_penalty'];
        }

        // 3. Default port usage
        if ($this->usesDefaultPort($config)) {
            $issues[] = "Connection '{$name}' uses default database port";
            $recommendations[] = "Consider using a non-default port for connection '{$name}' to reduce attack surface";
            $scorePenalty += 5;
        }

        // 4. Username validation
        if ($this->hasWeakUsername($config)) {
            $issues[] = "Connection '{$name}' uses a weak username";
            $recommendations[] = "Use a non-default username for connection '{$name}'";
            $scorePenalty += 15;
        }

        // 5. Host configuration
        if ($this->hasInsecureHost($config)) {
            $issues[] = "Connection '{$name}' allows connections from any host";
            $recommendations[] = "Restrict database host access for connection '{$name}'";
            $scorePenalty += 10;
        }

        return [
            'issues' => $issues,
            'recommendations' => $recommendations,
            'score_penalty' => $scorePenalty,
        ];
    }

    /**
     * Validate SSL configuration
     */
    private function validateSSLConfiguration(string $name, array $config): array
    {
        $issues = [];
        $recommendations = [];
        $scorePenalty = 0;

        // Check if SSL is enabled
        $sslMode = $config['sslmode'] ?? $config['options'][PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] ?? null;

        if (empty($sslMode) || $sslMode === false) {
            $issues[] = "Connection '{$name}' does not have SSL/TLS enabled";
            $recommendations[] = "Enable SSL/TLS encryption for connection '{$name}'";
            $scorePenalty += 30;
        } elseif (! in_array($sslMode, $this->secureSslModes)) {
            $issues[] = "Connection '{$name}' has weak SSL configuration";
            $recommendations[] = "Use a secure SSL mode (require, verify-ca, or verify-full) for connection '{$name}'";
            $scorePenalty += 20;
        }

        // Check for SSL certificate validation
        if (isset($config['options'][PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT]) &&
            $config['options'][PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] === false) {
            $issues[] = "Connection '{$name}' has SSL certificate verification disabled";
            $recommendations[] = "Enable SSL certificate verification for connection '{$name}'";
            $scorePenalty += 15;
        }

        return [
            'issues' => $issues,
            'recommendations' => $recommendations,
            'score_penalty' => $scorePenalty,
        ];
    }

    /**
     * Validate environment-specific security requirements
     */
    private function validateEnvironmentSpecificSecurity(): array
    {
        $issues = [];
        $recommendations = [];
        $scorePenalty = 0;

        $environment = app()->environment();

        // Production-specific checks
        if ($environment === 'production') {
            // Check for development/debug database settings
            if (config('database.default') === 'sqlite' && config('database.connections.sqlite.database') === ':memory:') {
                $issues[] = 'Using in-memory SQLite database in production';
                $recommendations[] = 'Use a persistent database in production environment';
                $scorePenalty += 40;
            }

            // Check for query logging in production
            if (config('database.connections.'.config('database.default').'.log_queries', false)) {
                $issues[] = 'Database query logging is enabled in production';
                $recommendations[] = 'Disable query logging in production to prevent performance issues and log bloat';
                $scorePenalty += 10;
            }
        }

        // Check for database connection pooling
        if ($this->shouldUseConnectionPooling() && ! $this->hasConnectionPooling()) {
            $issues[] = 'Database connection pooling is not configured';
            $recommendations[] = 'Consider implementing connection pooling for better performance and security';
            $scorePenalty += 5;
        }

        return [
            'issues' => $issues,
            'recommendations' => $recommendations,
            'score_penalty' => $scorePenalty,
        ];
    }

    /**
     * Check if SSL should be required for this connection
     */
    private function shouldRequireSSL(string $connectionName): bool
    {
        $environment = app()->environment();

        // Always require SSL in production
        if ($environment === 'production') {
            return true;
        }

        // Require SSL for non-local connections
        $config = config("database.connections.{$connectionName}");
        $host = $config['host'] ?? 'localhost';

        return ! in_array($host, ['localhost', '127.0.0.1', '::1']);
    }

    /**
     * Check if connection uses default port
     */
    private function usesDefaultPort(array $config): bool
    {
        $defaultPorts = [
            'mysql' => 3306,
            'pgsql' => 5432,
            'sqlsrv' => 1433,
        ];

        $driver = $config['driver'] ?? '';
        $port = $config['port'] ?? null;

        return isset($defaultPorts[$driver]) && $port === $defaultPorts[$driver];
    }

    /**
     * Check for weak usernames
     */
    private function hasWeakUsername(array $config): bool
    {
        $weakUsernames = ['root', 'admin', 'administrator', 'user', 'postgres', 'sa'];
        $username = mb_strtolower($config['username'] ?? '');

        return in_array($username, $weakUsernames);
    }

    /**
     * Check for insecure host configuration
     */
    private function hasInsecureHost(array $config): bool
    {
        $host = $config['host'] ?? '';

        // Check for wildcards or overly permissive hosts
        return in_array($host, ['%', '*', '0.0.0.0', '::']);
    }

    /**
     * Count SSL-enabled connections
     */
    private function countSslEnabledConnections(array $connections): int
    {
        $sslEnabled = 0;

        foreach ($connections as $config) {
            $sslMode = $config['sslmode'] ?? $config['options'][PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] ?? null;
            if (! empty($sslMode) && $sslMode !== false) {
                $sslEnabled++;
            }
        }

        return $sslEnabled;
    }

    /**
     * Determine if connection pooling should be used
     */
    private function shouldUseConnectionPooling(): bool
    {
        return app()->environment('production') &&
               config('database.connections.'.config('database.default').'.driver') !== 'sqlite';
    }

    /**
     * Check if connection pooling is configured
     */
    private function hasConnectionPooling(): bool
    {
        // This would check for connection pooling configuration
        // Implementation depends on your specific setup (e.g., PgBouncer, ProxySQL)
        return config('database.pool.enabled', false);
    }

    /**
     * Determine severity based on issues and security score
     */
    private function determineSeverity(array $issues, int $securityScore): string
    {
        // Critical if security score is very low or has critical issues
        if ($securityScore < 50) {
            return 'critical';
        }

        // Check for specific critical issues
        $criticalKeywords = ['weak password', 'no ssl', 'production'];
        foreach ($issues as $issue) {
            foreach ($criticalKeywords as $keyword) {
                if (str_contains(mb_strtolower($issue), $keyword)) {
                    return 'critical';
                }
            }
        }

        // Warning if score is moderate
        if ($securityScore < 80) {
            return 'warning';
        }

        return 'error';
    }
}

/*
 * Usage Example:
 *
 * 1. Save this file as app/SafeguardRules/AdvancedDatabaseSecurityRule.php
 *
 * 2. Add to your safeguard configuration:
 *    'rules' => [
 *        'advanced-database-security' => true,
 *    ],
 *
 * 3. Add to environment configuration:
 *    'environments' => [
 *        'production' => [
 *            'advanced-database-security',
 *        ],
 *    ],
 *
 * 4. Run the check:
 *    php artisan safeguard:check
 *
 * This rule will validate:
 * - Password strength
 * - SSL/TLS configuration
 * - Default port usage
 * - Username security
 * - Host restrictions
 * - Environment-specific requirements
 * - Connection pooling (if applicable)
 */
