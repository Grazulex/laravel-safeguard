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
        $webMiddleware = $this->webMiddleware();

        $hasCsrf = in_array('csrf', $webMiddleware, true)
            || $this->hasCsrfMiddleware($webMiddleware);

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
     * Resolve the "web" middleware group from the router, falling back to config.
     *
     * @return array<int, string>
     */
    private function webMiddleware(): array
    {
        $groups = app()->bound('router') ? app('router')->getMiddlewareGroups() : [];
        $web = $groups['web'] ?? config('app.middleware_groups.web', []);

        return array_values(array_filter($web, fn ($middleware): bool => is_string($middleware)));
    }

    /**
     * Laravel's CSRF middleware classes: PreventRequestForgery (Laravel 13+) and
     * VerifyCsrfToken (Laravel <= 12, kept as a deprecated alias in 13).
     *
     * @return array<int, class-string>
     */
    private function csrfMiddlewareClasses(): array
    {
        return array_values(array_filter([
            'Illuminate\Foundation\Http\Middleware\PreventRequestForgery',
            'Illuminate\Foundation\Http\Middleware\VerifyCsrfToken',
        ], 'class_exists'));
    }

    /**
     * Check whether the group contains Laravel's CSRF middleware or a subclass of it.
     */
    private function hasCsrfMiddleware(array $middleware): bool
    {
        $csrfClasses = $this->csrfMiddlewareClasses();

        foreach ($middleware as $middlewareClass) {
            if (in_array($middlewareClass, $csrfClasses, true)) {
                return true;
            }

            if (! class_exists($middlewareClass)) {
                continue;
            }

            try {
                $reflection = new ReflectionClass($middlewareClass);
                foreach ($csrfClasses as $csrfClass) {
                    if ($reflection->isSubclassOf($csrfClass)) {
                        return true;
                    }
                }
            } catch (ReflectionException) {
                continue;
            }
        }

        return false;
    }
}
