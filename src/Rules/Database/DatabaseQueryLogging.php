<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard\Rules\Database;

use Grazulex\LaravelSafeguard\Rules\AbstractSafeguardRule;
use Grazulex\LaravelSafeguard\SafeguardResult;

class DatabaseQueryLogging extends AbstractSafeguardRule
{
    public function id(): string
    {
        return 'database-query-logging';
    }

    public function description(): string
    {
        return 'Verifies that database query logging is appropriately configured for security';
    }

    public function check(): SafeguardResult
    {
        $currentEnv = app()->environment();
        $issues = [];
        $recommendations = [];

        // Check if query logging is enabled
        $queryLogging = config('database.log', false);

        if ($currentEnv === 'production' && $queryLogging) {
            $issues[] = [
                'type' => 'query_logging_in_production',
                'severity' => 'warning',
                'message' => 'Database query logging is enabled in production environment',
                'risk' => 'Query logs may contain sensitive data and impact performance',
            ];
            $recommendations[] = 'Disable query logging in production or ensure logs are properly secured';
        }

        // Check Laravel Debugbar
        if ($this->hasDebugbarEnabled()) {
            $issues[] = [
                'type' => 'debugbar_enabled',
                'severity' => $currentEnv === 'production' ? 'critical' : 'warning',
                'message' => 'Laravel Debugbar is enabled which logs database queries',
                'risk' => 'Debugbar exposes sensitive information including database queries',
            ];
            $recommendations[] = 'Disable Laravel Debugbar in production environments';
        }

        // Check Telescope
        if ($this->hasTelescopeEnabled($currentEnv)) {
            $issues[] = [
                'type' => 'telescope_enabled',
                'severity' => $currentEnv === 'production' ? 'critical' : 'info',
                'message' => 'Laravel Telescope is enabled which records database queries',
                'risk' => 'Telescope stores detailed application data including sensitive queries',
            ];

            if ($currentEnv === 'production') {
                $recommendations[] = 'Secure Telescope access or disable in production';
            } else {
                $recommendations[] = 'Ensure Telescope data is regularly cleaned and access is restricted';
            }
        }

        // Check slow query logging
        $this->checkSlowQueryLogging($recommendations);

        if ($issues !== []) {
            $severity = $this->determineSeverity($issues, $currentEnv);

            return SafeguardResult::fail(
                'Database query logging configuration has security implications',
                $severity,
                [
                    'environment' => $currentEnv,
                    'issues' => $issues,
                    'recommendations' => $recommendations,
                    'query_logging_enabled' => $queryLogging,
                ]
            );
        }

        return SafeguardResult::pass(
            'Database query logging is appropriately configured',
            [
                'environment' => $currentEnv,
                'query_logging_enabled' => $queryLogging,
                'security_status' => 'appropriate_for_environment',
            ]
        );
    }

    public function appliesToEnvironment(string $environment): bool
    {
        return true;
    }

    public function severity(): string
    {
        return 'warning';
    }

    private function hasDebugbarEnabled(): bool
    {
        // Check if Debugbar is installed and enabled
        $debugbarEnabled = config('debugbar.enabled', false);
        $appDebug = config('app.debug', false);

        return $debugbarEnabled || ($appDebug && class_exists('Barryvdh\Debugbar\ServiceProvider'));
    }

    private function hasTelescopeEnabled(string $environment): bool
    {
        // Check if Telescope is installed and enabled
        return config('telescope.enabled', false) ||
               (class_exists('Laravel\Telescope\TelescopeServiceProvider') && $environment !== 'production');
    }

    private function checkSlowQueryLogging(array &$recommendations): void
    {
        $connections = config('database.connections', []);

        foreach ($connections as $config) {
            $driver = $config['driver'] ?? '';

            if ($driver === 'mysql') {
                // Note: We can't directly check MySQL slow query log without database connection
                // This is more of a recommendation check
                $recommendations[] = 'Ensure MySQL slow query log is enabled and monitored for performance and security';
            }
        }
    }

    private function determineSeverity(array $issues, string $environment): string
    {
        if ($environment === 'production') {
            foreach ($issues as $issue) {
                if ($issue['severity'] === 'critical') {
                    return 'critical';
                }
            }

            return 'error';
        }

        return 'warning';
    }
}
