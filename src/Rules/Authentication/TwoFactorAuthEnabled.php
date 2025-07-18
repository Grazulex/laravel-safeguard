<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard\Rules\Authentication;

use Exception;
use Grazulex\LaravelSafeguard\Contracts\SafeguardRule;
use Grazulex\LaravelSafeguard\SafeguardResult;

class TwoFactorAuthEnabled implements SafeguardRule
{
    public function id(): string
    {
        return 'two-factor-auth-enabled';
    }

    public function description(): string
    {
        return 'Verifies that two-factor authentication is properly configured and encouraged';
    }

    public function check(): SafeguardResult
    {
        $currentEnv = app()->environment();
        $issues = [];
        $recommendations = [];
        $twoFactorStatus = [];

        // Check for popular 2FA packages
        $this->checkTwoFactorPackages($issues, $recommendations, $twoFactorStatus);

        // Check 2FA configuration
        $this->checkTwoFactorConfiguration($issues, $recommendations, $twoFactorStatus);

        // Check 2FA enforcement policies
        $this->checkTwoFactorEnforcement($issues, $recommendations, $twoFactorStatus, $currentEnv);

        if ($issues !== []) {
            $severity = $this->determineSeverity($issues, $currentEnv);

            return SafeguardResult::fail(
                'Two-factor authentication configuration issues detected',
                $severity,
                [
                    'issues' => $issues,
                    'recommendations' => $recommendations,
                    'two_factor_status' => $twoFactorStatus,
                    'environment' => $currentEnv,
                ]
            );
        }

        return SafeguardResult::pass(
            'Two-factor authentication is properly configured',
            [
                'two_factor_status' => $twoFactorStatus,
                'environment' => $currentEnv,
                'security_level' => $this->getTwoFactorSecurityLevel($twoFactorStatus),
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

    private function checkTwoFactorPackages(array &$issues, array &$recommendations, array &$twoFactorStatus): void
    {
        $packageChecks = [
            'laravel/fortify' => 'Laravel Fortify',
            'pragmarx/google2fa-laravel' => 'Google2FA Laravel',
            'bacon/bacon-qr-code' => 'QR Code Generator',
            'laravel/jetstream' => 'Laravel Jetstream',
            'spatie/laravel-google2fa' => 'Spatie Google2FA',
        ];

        $installedPackages = [];

        foreach ($packageChecks as $package => $name) {
            if ($this->isPackageInstalled($package)) {
                $installedPackages[] = $name;
                $twoFactorStatus['installed_packages'][] = $package;
            }
        }

        if ($installedPackages === []) {
            $issues[] = [
                'type' => 'no_2fa_package',
                'severity' => 'warning',
                'message' => 'No two-factor authentication package detected',
                'risk' => 'Application lacks 2FA capability',
            ];
            $recommendations[] = 'Install a 2FA package like Laravel Fortify or pragmarx/google2fa-laravel';
            $twoFactorStatus['package_installed'] = false;
        } else {
            $twoFactorStatus['package_installed'] = true;
            $twoFactorStatus['detected_packages'] = $installedPackages;
        }
    }

    private function checkTwoFactorConfiguration(array &$issues, array &$recommendations, array &$twoFactorStatus): void
    {
        // Check Fortify configuration
        if ($this->isPackageInstalled('laravel/fortify')) {
            $this->checkFortifyTwoFactor($issues, $recommendations, $twoFactorStatus);
        }

        // Check Google2FA configuration
        if ($this->isPackageInstalled('pragmarx/google2fa-laravel')) {
            $this->checkGoogle2FAConfig($issues, $recommendations, $twoFactorStatus);
        }

        // Check for custom 2FA implementation
        $this->checkCustomTwoFactorImplementation($twoFactorStatus);
    }

    private function checkFortifyTwoFactor(array &$issues, array &$recommendations, array &$twoFactorStatus): void
    {
        $fortifyFeatures = config('fortify.features', []);

        if (! in_array('two-factor-authentication', $fortifyFeatures)) {
            $issues[] = [
                'type' => 'fortify_2fa_disabled',
                'severity' => 'error',
                'message' => 'Fortify two-factor authentication feature is disabled',
            ];
            $recommendations[] = 'Enable two-factor-authentication in fortify.features configuration';
            $twoFactorStatus['fortify_2fa_enabled'] = false;
        } else {
            $twoFactorStatus['fortify_2fa_enabled'] = true;
        }

        // Check QR code configuration
        if (! $this->isPackageInstalled('bacon/bacon-qr-code') &&
            ! $this->isPackageInstalled('simplesoftwareio/simple-qrcode')) {
            $issues[] = [
                'type' => 'no_qr_generator',
                'severity' => 'warning',
                'message' => 'No QR code generator package found',
                'risk' => 'Users cannot easily set up 2FA via QR codes',
            ];
            $recommendations[] = 'Install bacon/bacon-qr-code or simplesoftwareio/simple-qrcode';
        }
    }

    private function checkGoogle2FAConfig(array &$issues, array &$recommendations, array &$twoFactorStatus): void
    {
        $google2faConfig = config('google2fa');

        if (empty($google2faConfig)) {
            $issues[] = [
                'type' => 'google2fa_not_configured',
                'severity' => 'warning',
                'message' => 'Google2FA package installed but not configured',
            ];
            $recommendations[] = 'Publish and configure Google2FA configuration';
            $twoFactorStatus['google2fa_configured'] = false;
        } else {
            $twoFactorStatus['google2fa_configured'] = true;

            // Check window tolerance
            $window = $google2faConfig['window'] ?? 1;
            if ($window > 2) {
                $issues[] = [
                    'type' => 'large_2fa_window',
                    'severity' => 'warning',
                    'message' => "Large 2FA time window: {$window} (recommended: 1-2)",
                ];
                $recommendations[] = 'Reduce Google2FA window to 1 or 2 for better security';
            }
        }
    }

    private function checkCustomTwoFactorImplementation(array &$twoFactorStatus): void
    {
        // Check for custom 2FA columns in users table
        $hasTwoFactorColumns = $this->hasCustomTwoFactorColumns();

        $twoFactorStatus['custom_implementation'] = $hasTwoFactorColumns;
    }

    private function checkTwoFactorEnforcement(array &$issues, array &$recommendations, array &$twoFactorStatus, string $environment): void
    {
        // In production, 2FA should be strongly encouraged or enforced
        if ($environment === 'production') {
            if (! ($twoFactorStatus['package_installed'] ?? false)) {
                $issues[] = [
                    'type' => 'no_2fa_in_production',
                    'severity' => 'critical',
                    'message' => 'No two-factor authentication available in production',
                    'risk' => 'Accounts vulnerable to credential compromise',
                ];
                $recommendations[] = 'Implement 2FA before deploying to production';
            }

            // Check for 2FA enforcement middleware
            if (! $this->hasTwoFactorMiddleware()) {
                $issues[] = [
                    'type' => 'no_2fa_enforcement',
                    'severity' => 'warning',
                    'message' => 'No 2FA enforcement middleware detected',
                    'risk' => 'Users may not be required to enable 2FA',
                ];
                $recommendations[] = 'Create middleware to encourage or enforce 2FA for sensitive operations';
            }
        }
    }

    private function isPackageInstalled(string $package): bool
    {
        $composerLock = base_path('composer.lock');

        if (! file_exists($composerLock)) {
            return false;
        }

        $content = file_get_contents($composerLock);

        return str_contains($content, '"name": "'.$package.'"');
    }

    private function hasCustomTwoFactorColumns(): bool
    {
        // This is a simplified check - in practice, you might check the database schema
        // or look for specific migration files
        $userModel = config('auth.providers.users.model', 'App\\Models\\User');

        if (! class_exists($userModel)) {
            return false;
        }

        try {
            $instance = new $userModel();
            $fillable = $instance->getFillable();

            return in_array('two_factor_secret', $fillable) ||
                   in_array('two_factor_recovery_codes', $fillable) ||
                   in_array('two_factor_confirmed_at', $fillable);
        } catch (Exception $e) {
            return false;
        }
    }

    private function hasTwoFactorMiddleware(): bool
    {
        // Check for common 2FA middleware names
        $middlewareAliases = config('app.middleware_aliases', []);
        $middlewareGroups = config('app.middleware_groups', []);

        $twoFactorMiddleware = [
            'auth.2fa',
            'twofactor',
            'two-factor',
            'require.2fa',
        ];

        foreach ($twoFactorMiddleware as $middleware) {
            if (isset($middlewareAliases[$middleware]) ||
                $this->middlewareExistsInGroups($middleware, $middlewareGroups)) {
                return true;
            }
        }

        return false;
    }

    private function middlewareExistsInGroups(string $middleware, array $groups): bool
    {
        foreach ($groups as $group) {
            if (in_array($middleware, $group)) {
                return true;
            }
        }

        return false;
    }

    private function getTwoFactorSecurityLevel(array $twoFactorStatus): string
    {
        if (! ($twoFactorStatus['package_installed'] ?? false)) {
            return 'none';
        }

        if ($twoFactorStatus['fortify_2fa_enabled'] ?? false) {
            return 'high';
        }

        if ($twoFactorStatus['google2fa_configured'] ?? false) {
            return 'medium';
        }

        if ($twoFactorStatus['custom_implementation'] ?? false) {
            return 'medium';
        }

        return 'low';
    }

    private function determineSeverity(array $issues, string $environment): string
    {
        foreach ($issues as $issue) {
            if ($issue['severity'] === 'critical') {
                return 'critical';
            }
        }

        if ($environment === 'production') {
            foreach ($issues as $issue) {
                if ($issue['severity'] === 'error') {
                    return 'error';
                }
            }
        }

        return 'warning';
    }
}
