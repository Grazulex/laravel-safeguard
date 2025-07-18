<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard\Rules\Security;

use Grazulex\LaravelSafeguard\Rules\AbstractSafeguardRule;
use Grazulex\LaravelSafeguard\SafeguardResult;
use ReflectionClass;
use ReflectionException;

class CsrfEnabled extends AbstractSafeguardRule
{
    public function id(): string
    {
        return 'csrf-enabled';
    }

    public function description(): string
    {
        return 'Verifies that CSRF protection is enabled';
    }

    public function check(): SafeguardResult
    {
        // Check if CSRF protection is enabled by examining middleware configuration
        $webMiddleware = config('app.middleware_groups.web', []);
        $laravelCsrfMiddleware = \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class;

        $hasCsrf = in_array($laravelCsrfMiddleware, $webMiddleware) ||
                   in_array('csrf', $webMiddleware) ||
                   $this->hasCustomCsrfMiddleware($webMiddleware);

        if (! $hasCsrf) {
            return SafeguardResult::fail(
                'CSRF protection is disabled',
                'error',
                [
                    'current_setting' => 'disabled',
                    'recommendation' => 'Enable CSRF protection in your application configuration',
                    'security_impact' => 'Without CSRF protection, your application is vulnerable to cross-site request forgery attacks',
                ]
            );
        }

        return SafeguardResult::pass(
            'CSRF protection is properly enabled',
            [
                'csrf_status' => 'enabled',
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
     * Check if there's a custom CSRF middleware that extends the base VerifyCsrfToken
     */
    private function hasCustomCsrfMiddleware(array $middleware): bool
    {
        foreach ($middleware as $middlewareClass) {
            if (is_string($middlewareClass) && class_exists($middlewareClass)) {
                try {
                    $reflection = new ReflectionClass($middlewareClass);
                    if ($reflection->isSubclassOf(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)) {
                        return true;
                    }
                } catch (ReflectionException $e) {
                    // Skip invalid classes
                    continue;
                }
            }
        }

        return false;
    }
}
