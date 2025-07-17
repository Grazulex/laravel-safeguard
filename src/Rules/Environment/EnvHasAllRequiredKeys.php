<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard\Rules\Environment;

use Grazulex\LaravelSafeguard\Contracts\SafeguardRule;
use Grazulex\LaravelSafeguard\SafeguardResult;

class EnvHasAllRequiredKeys implements SafeguardRule
{
    public function id(): string
    {
        return 'env-has-all-required-keys';
    }

    public function description(): string
    {
        return 'Verifies that all required environment variables are present';
    }

    public function check(): SafeguardResult
    {
        $requiredVars = config('safeguard.required_env_vars', []);
        $missingVars = [];

        foreach ($requiredVars as $var) {
            // Use config to check for variables instead of env()
            $configKey = $this->getConfigKey($var);
            if ($configKey && config($configKey) === null) {
                $missingVars[] = $var;
            } elseif ($configKey === null || $configKey === '' || $configKey === '0') {
                // For variables not mapped to config, check if they exist in $_ENV
                if (! isset($_ENV[$var]) || $_ENV[$var] === '') {
                    $missingVars[] = $var;
                }
            }
        }

        if ($missingVars !== []) {
            return SafeguardResult::fail(
                'Missing required environment variables: '.implode(', ', $missingVars),
                'error',
                [
                    'missing_variables' => $missingVars,
                    'total_required' => count($requiredVars),
                    'recommendation' => 'Add these variables to your .env file',
                ]
            );
        }

        return SafeguardResult::pass(
            'All required environment variables are present',
            [
                'required_variables' => $requiredVars,
                'all_present' => true,
            ]
        );
    }

    public function appliesToEnvironment(string $environment): bool
    {
        return true;
    }

    public function severity(): string
    {
        return 'error';
    }

    /**
     * Map environment variable names to their corresponding config keys
     */
    private function getConfigKey(string $envVar): ?string
    {
        $mapping = [
            'APP_NAME' => 'app.name',
            'APP_ENV' => 'app.env',
            'APP_KEY' => 'app.key',
            'APP_DEBUG' => 'app.debug',
            'APP_URL' => 'app.url',
            'DB_CONNECTION' => 'database.default',
            'DB_HOST' => 'database.connections.mysql.host',
            'DB_PORT' => 'database.connections.mysql.port',
            'DB_DATABASE' => 'database.connections.mysql.database',
            'DB_USERNAME' => 'database.connections.mysql.username',
            'DB_PASSWORD' => 'database.connections.mysql.password',
            'MAIL_MAILER' => 'mail.default',
            'MAIL_HOST' => 'mail.mailers.smtp.host',
            'MAIL_PORT' => 'mail.mailers.smtp.port',
            'MAIL_USERNAME' => 'mail.mailers.smtp.username',
            'MAIL_PASSWORD' => 'mail.mailers.smtp.password',
        ];

        return $mapping[$envVar] ?? null;
    }
}
