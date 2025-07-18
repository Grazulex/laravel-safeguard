<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard\Rules\Environment;

use Grazulex\LaravelSafeguard\Rules\AbstractSafeguardRule;
use Grazulex\LaravelSafeguard\SafeguardResult;

class AppDebugFalseInProduction extends AbstractSafeguardRule
{
    public function id(): string
    {
        return 'app-debug-false-in-production';
    }

    public function description(): string
    {
        return 'Ensures APP_DEBUG is false in production environment';
    }

    public function check(): SafeguardResult
    {
        $currentEnv = app()->environment();
        $debugEnabled = config('app.debug', false);

        if ($currentEnv === 'production' && $debugEnabled) {
            return SafeguardResult::critical(
                'APP_DEBUG is enabled in production environment',
                [
                    'current_env' => $currentEnv,
                    'debug_value' => $debugEnabled,
                    'recommendation' => 'Set APP_DEBUG=false in your .env file for production',
                ]
            );
        }

        if ($debugEnabled && in_array($currentEnv, ['staging', 'prod'])) {
            return SafeguardResult::warning(
                "APP_DEBUG is enabled in {$currentEnv} environment",
                [
                    'current_env' => $currentEnv,
                    'debug_value' => $debugEnabled,
                ]
            );
        }

        return SafeguardResult::pass(
            'APP_DEBUG is properly configured for this environment',
            [
                'current_environment' => $currentEnv,
                'debug_status' => $debugEnabled ? 'enabled' : 'disabled',
            ]
        );
    }

    public function appliesToEnvironment(string $environment): bool
    {
        return in_array($environment, ['production', 'staging', 'prod']);
    }

    public function severity(): string
    {
        return 'critical';
    }
}
