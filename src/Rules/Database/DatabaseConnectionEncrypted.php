<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard\Rules\Database;

use Grazulex\LaravelSafeguard\Contracts\SafeguardRule;
use Grazulex\LaravelSafeguard\SafeguardResult;
use PDO;

class DatabaseConnectionEncrypted implements SafeguardRule
{
    public function id(): string
    {
        return 'database-connection-encrypted';
    }

    public function description(): string
    {
        return 'Verifies that database connections use SSL/TLS encryption';
    }

    public function check(): SafeguardResult
    {
        $connections = config('database.connections', []);
        $vulnerableConnections = [];
        $secureConnections = [];

        foreach ($connections as $name => $config) {
            if (! $this->isConnectionSecure($config)) {
                $vulnerableConnections[] = [
                    'connection' => $name,
                    'driver' => $config['driver'] ?? 'unknown',
                    'reason' => $this->getSecurityIssue($config),
                ];
            } else {
                $secureConnections[] = $name;
            }
        }

        if ($vulnerableConnections !== []) {
            return SafeguardResult::critical(
                'Database connections without proper encryption detected',
                [
                    'vulnerable_connections' => $vulnerableConnections,
                    'secure_connections' => $secureConnections,
                    'recommendation' => 'Enable SSL/TLS for all database connections in production environments',
                    'security_impact' => 'Unencrypted database connections expose sensitive data to network interception',
                ]
            );
        }

        return SafeguardResult::pass(
            'All database connections are properly encrypted',
            [
                'secure_connections' => $secureConnections,
                'total_connections' => count($connections),
            ]
        );
    }

    public function appliesToEnvironment(string $environment): bool
    {
        // More critical in production, but should be checked in all environments
        return true;
    }

    public function severity(): string
    {
        return 'critical';
    }

    private function isConnectionSecure(array $config): bool
    {
        $driver = $config['driver'] ?? '';

        return match ($driver) {
            'mysql' => $this->isMysqlSecure($config),
            'pgsql' => $this->isPostgresSecure($config),
            'sqlsrv' => $this->isSqlServerSecure($config),
            'sqlite' => true, // SQLite is file-based, no network encryption needed
            default => false,
        };
    }

    private function isMysqlSecure(array $config): bool
    {
        $options = $config['options'] ?? [];

        // Check for SSL options
        return isset($options[PDO::MYSQL_ATTR_SSL_CA]) ||
               isset($options[PDO::MYSQL_ATTR_SSL_CERT]) ||
               isset($options[PDO::MYSQL_ATTR_SSL_KEY]) ||
               ($config['sslmode'] ?? '') === 'require';
    }

    private function isPostgresSecure(array $config): bool
    {
        $sslmode = $config['sslmode'] ?? '';

        return in_array($sslmode, ['require', 'verify-ca', 'verify-full']);
    }

    private function isSqlServerSecure(array $config): bool
    {
        return ($config['encrypt'] ?? false) === true ||
               ($config['TrustServerCertificate'] ?? true) === false;
    }

    private function getSecurityIssue(array $config): string
    {
        $driver = $config['driver'] ?? 'unknown';

        return match ($driver) {
            'mysql' => 'No SSL options configured (missing sslmode or PDO SSL attributes)',
            'pgsql' => 'SSL mode not set to require, verify-ca, or verify-full',
            'sqlsrv' => 'Encryption not enabled or TrustServerCertificate not properly configured',
            default => 'Unknown driver or encryption not configured',
        };
    }
}
