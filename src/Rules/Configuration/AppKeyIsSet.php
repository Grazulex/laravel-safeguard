<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard\Rules\Configuration;

use Grazulex\LaravelSafeguard\Rules\AbstractSafeguardRule;
use Grazulex\LaravelSafeguard\SafeguardResult;

class AppKeyIsSet extends AbstractSafeguardRule
{
    public function id(): string
    {
        return 'app-key-is-set';
    }

    public function description(): string
    {
        return 'Verifies that Laravel APP_KEY is generated and not empty';
    }

    public function check(): SafeguardResult
    {
        $appKey = config('app.key');

        if (empty($appKey)) {
            return SafeguardResult::critical(
                'APP_KEY is not set - application encryption will not work',
                [
                    'recommendation' => 'Run "php artisan key:generate" to generate an application key',
                    'security_impact' => 'Without APP_KEY, sessions and encrypted data cannot be secured',
                ]
            );
        }

        if ($appKey === 'base64:' || mb_strlen($appKey) < 10) {
            return SafeguardResult::fail(
                'APP_KEY appears to be invalid or too short',
                'error',
                [
                    'current_key_length' => mb_strlen($appKey),
                    'recommendation' => 'Generate a new key with "php artisan key:generate"',
                ]
            );
        }

        // Check if it looks like a default/example key
        $suspiciousKeys = [
            'SomeRandomString',
            'YourAppKeyHere',
            'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
        ];

        if (in_array($appKey, $suspiciousKeys)) {
            return SafeguardResult::fail(
                'APP_KEY appears to be a default/example value',
                'error',
                [
                    'recommendation' => 'Generate a unique key with "php artisan key:generate"',
                ]
            );
        }

        return SafeguardResult::pass(
            'APP_KEY is properly configured',
            [
                'key_length' => mb_strlen($appKey),
                'has_base64_prefix' => str_starts_with($appKey, 'base64:'),
            ]
        );
    }

    public function appliesToEnvironment(string $environment): bool
    {
        // This rule applies to all environments
        return true;
    }

    public function severity(): string
    {
        return 'critical';
    }
}
