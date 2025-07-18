<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard\Rules\Database;

use Grazulex\LaravelSafeguard\Rules\AbstractSafeguardRule;
use Grazulex\LaravelSafeguard\SafeguardResult;

class DatabaseCredentialsNotDefault extends AbstractSafeguardRule
{
    private const DEFAULT_CREDENTIALS = [
        'root' => ['', 'root', 'password', 'admin', 'toor'],
        'admin' => ['admin', 'password', '', '123456'],
        'sa' => ['', 'sa', 'password', 'admin'],
        'postgres' => ['', 'postgres', 'password'],
        'mysql' => ['', 'mysql', 'password'],
        'test' => ['test', 'password', ''],
        'demo' => ['demo', 'password', ''],
        'guest' => ['guest', 'password', ''],
    ];

    private const WEAK_PASSWORDS = [
        'password', '123456', 'admin', 'root', 'test', 'demo',
        'guest', 'user', 'pass', '1234', '12345', '123456789',
        'qwerty', 'abc123', 'password123', 'admin123',
    ];

    public function id(): string
    {
        return 'database-credentials-not-default';
    }

    public function description(): string
    {
        return 'Detects default or weak database credentials that pose security risks';
    }

    public function check(): SafeguardResult
    {
        $connections = config('database.connections', []);
        $vulnerableConnections = [];
        $issues = [];

        foreach ($connections as $name => $config) {
            $username = $config['username'] ?? '';
            $password = $config['password'] ?? '';

            if ($this->hasDefaultCredentials($username, $password)) {
                $vulnerableConnections[] = $name;
                $issues[] = [
                    'connection' => $name,
                    'type' => 'default_credentials',
                    'username' => $username,
                    'severity' => 'critical',
                    'message' => "Default credentials detected for user '{$username}'",
                ];
            } elseif ($this->hasWeakPassword($password)) {
                $vulnerableConnections[] = $name;
                $issues[] = [
                    'connection' => $name,
                    'type' => 'weak_password',
                    'username' => $username,
                    'severity' => 'error',
                    'message' => "Weak password detected for user '{$username}'",
                ];
            } elseif ($this->isPasswordTooShort($password)) {
                $vulnerableConnections[] = $name;
                $issues[] = [
                    'connection' => $name,
                    'type' => 'short_password',
                    'username' => $username,
                    'severity' => 'warning',
                    'message' => "Password too short for user '{$username}' (minimum 12 characters recommended)",
                ];
            }
        }

        if ($vulnerableConnections !== []) {
            $severity = $this->determineSeverity($issues);

            return SafeguardResult::fail(
                'Vulnerable database credentials detected',
                $severity,
                [
                    'vulnerable_connections' => array_unique($vulnerableConnections),
                    'issues' => $issues,
                    'total_connections' => count($connections),
                    'recommendations' => [
                        'Use strong, unique passwords for all database users',
                        'Avoid default usernames like root, admin, sa',
                        'Use environment variables for credentials',
                        'Consider using database-specific authentication methods',
                        'Implement password rotation policies',
                    ],
                ]
            );
        }

        return SafeguardResult::pass(
            'Database credentials appear secure',
            [
                'checked_connections' => count($connections),
                'all_secure' => true,
            ]
        );
    }

    public function appliesToEnvironment(string $environment): bool
    {
        return true;
    }

    public function severity(): string
    {
        return 'critical';
    }

    private function hasDefaultCredentials(string $username, string $password): bool
    {
        $username = mb_strtolower($username);

        if (! isset(self::DEFAULT_CREDENTIALS[$username])) {
            return false;
        }

        return in_array($password, self::DEFAULT_CREDENTIALS[$username], true);
    }

    private function hasWeakPassword(string $password): bool
    {
        return in_array(mb_strtolower($password), self::WEAK_PASSWORDS, true);
    }

    private function isPasswordTooShort(string $password): bool
    {
        return mb_strlen($password) > 0 && mb_strlen($password) < 12;
    }

    private function determineSeverity(array $issues): string
    {
        foreach ($issues as $issue) {
            if ($issue['severity'] === 'critical') {
                return 'critical';
            }
        }

        foreach ($issues as $issue) {
            if ($issue['severity'] === 'error') {
                return 'error';
            }
        }

        return 'warning';
    }
}
