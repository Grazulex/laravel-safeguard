<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard\Rules\Authentication;

use Grazulex\LaravelSafeguard\Contracts\SafeguardRule;
use Grazulex\LaravelSafeguard\SafeguardResult;

class SessionSecuritySettings implements SafeguardRule
{
    public function id(): string
    {
        return 'session-security-settings';
    }

    public function description(): string
    {
        return 'Verifies that session security settings are properly configured';
    }

    public function check(): SafeguardResult
    {
        $currentEnv = app()->environment();
        $issues = [];
        $recommendations = [];
        $sessionConfig = [];

        // Check session driver security
        $this->checkSessionDriver($issues, $recommendations, $sessionConfig);

        // Check session lifetime settings
        $this->checkSessionLifetime($issues, $recommendations, $sessionConfig);

        // Check session cookie security
        $this->checkSessionCookieSecurity($issues, $recommendations, $sessionConfig, $currentEnv);

        // Check session encryption and integrity
        $this->checkSessionEncryption($issues, $recommendations, $sessionConfig);

        // Check session regeneration settings
        $this->checkSessionRegeneration($issues, $recommendations, $sessionConfig);

        if ($issues !== []) {
            $severity = $this->determineSeverity($issues, $currentEnv);

            return SafeguardResult::fail(
                'Session security configuration issues detected',
                $severity,
                [
                    'issues' => $issues,
                    'recommendations' => $recommendations,
                    'session_config' => $sessionConfig,
                    'environment' => $currentEnv,
                ]
            );
        }

        return SafeguardResult::pass(
            'Session security settings are properly configured',
            [
                'session_config' => $sessionConfig,
                'environment' => $currentEnv,
                'security_level' => $this->getSessionSecurityLevel($sessionConfig),
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

    private function checkSessionDriver(array &$issues, array &$recommendations, array &$sessionConfig): void
    {
        $driver = config('session.driver');
        $sessionConfig['driver'] = $driver;

        $secureDrivers = ['database', 'redis', 'memcached', 'dynamodb'];
        $insecureDrivers = ['file', 'cookie', 'array'];

        if (in_array($driver, $insecureDrivers)) {
            $severity = ($driver === 'cookie') ? 'critical' : 'warning';
            $issues[] = [
                'type' => 'insecure_session_driver',
                'severity' => $severity,
                'message' => "Insecure session driver: {$driver}",
                'current_driver' => $driver,
                'risk' => $this->getDriverRisk($driver),
            ];
            $recommendations[] = 'Switch to a more secure session driver like database, redis, or memcached';
        }

        $sessionConfig['driver_security'] = in_array($driver, $secureDrivers) ? 'secure' : 'insecure';
    }

    private function checkSessionLifetime(array &$issues, array &$recommendations, array &$sessionConfig): void
    {
        $lifetime = config('session.lifetime', 120);
        $sessionConfig['lifetime'] = $lifetime;

        // Check if lifetime is too long
        if ($lifetime > 480) { // 8 hours
            $issues[] = [
                'type' => 'excessive_session_lifetime',
                'severity' => 'warning',
                'message' => "Session lifetime too long: {$lifetime} minutes",
                'current_lifetime' => $lifetime,
                'risk' => 'Long session lifetimes increase risk of session hijacking',
            ];
            $recommendations[] = 'Reduce session lifetime to 480 minutes (8 hours) or less';
        }

        // Check if lifetime is reasonable for the application type
        if ($lifetime < 30) {
            $issues[] = [
                'type' => 'very_short_session_lifetime',
                'severity' => 'info',
                'message' => "Very short session lifetime: {$lifetime} minutes",
                'current_lifetime' => $lifetime,
            ];
            $recommendations[] = 'Consider if 30+ minute session lifetime would improve user experience';
        }
    }

    private function checkSessionCookieSecurity(array &$issues, array &$recommendations, array &$sessionConfig, string $environment): void
    {
        // Check secure cookie setting
        $secure = config('session.secure');
        $sessionConfig['secure'] = $secure;

        if ($environment === 'production' && ! $secure) {
            $issues[] = [
                'type' => 'insecure_session_cookie',
                'severity' => 'critical',
                'message' => 'Session cookies not marked as secure in production',
                'risk' => 'Session cookies can be intercepted over unencrypted connections',
            ];
            $recommendations[] = 'Set SESSION_SECURE_COOKIE=true in production environment';
        }

        // Check HttpOnly setting
        $httpOnly = config('session.http_only', true);
        $sessionConfig['http_only'] = $httpOnly;

        if (! $httpOnly) {
            $issues[] = [
                'type' => 'session_cookie_not_http_only',
                'severity' => 'error',
                'message' => 'Session cookies not marked as HttpOnly',
                'risk' => 'Session cookies vulnerable to XSS attacks',
            ];
            $recommendations[] = 'Enable HttpOnly for session cookies';
        }

        // Check SameSite setting
        $sameSite = config('session.same_site');
        $sessionConfig['same_site'] = $sameSite;

        if ($sameSite !== 'strict' && $sameSite !== 'lax') {
            $issues[] = [
                'type' => 'weak_same_site_policy',
                'severity' => 'warning',
                'message' => "Weak SameSite cookie policy: {$sameSite}",
                'current_same_site' => $sameSite,
                'risk' => 'Cookies vulnerable to CSRF attacks',
            ];
            $recommendations[] = 'Set SameSite to "strict" or "lax" for better CSRF protection';
        }

        // Check cookie path
        $path = config('session.path', '/');
        $sessionConfig['path'] = $path;

        if ($path !== '/') {
            $issues[] = [
                'type' => 'custom_cookie_path',
                'severity' => 'info',
                'message' => "Custom session cookie path: {$path}",
            ];
        }

        // Check cookie domain
        $domain = config('session.domain');
        $sessionConfig['domain'] = $domain;

        if (! empty($domain) && ! str_starts_with($domain, '.')) {
            $issues[] = [
                'type' => 'specific_cookie_domain',
                'severity' => 'info',
                'message' => "Specific session cookie domain: {$domain}",
            ];
        }
    }

    private function checkSessionEncryption(array &$issues, array &$recommendations, array &$sessionConfig): void
    {
        $encrypt = config('session.encrypt', false);
        $sessionConfig['encrypt'] = $encrypt;

        if (! $encrypt) {
            $issues[] = [
                'type' => 'session_not_encrypted',
                'severity' => 'warning',
                'message' => 'Session data not encrypted',
                'risk' => 'Session data can be read if storage is compromised',
            ];
            $recommendations[] = 'Enable session encryption for sensitive applications';
        }

        // Check if APP_KEY is set (required for encryption)
        $appKey = config('app.key');
        if ($encrypt && empty($appKey)) {
            $issues[] = [
                'type' => 'missing_encryption_key',
                'severity' => 'critical',
                'message' => 'Session encryption enabled but APP_KEY not set',
                'risk' => 'Application will fail to encrypt/decrypt session data',
            ];
            $recommendations[] = 'Generate and set APP_KEY: php artisan key:generate';
        }
    }

    private function checkSessionRegeneration(array &$issues, array &$recommendations, array &$sessionConfig): void
    {
        // Check if session regeneration is configured
        $regenerateOnLogin = $this->hasSessionRegenerationOnLogin();
        $sessionConfig['regenerate_on_login'] = $regenerateOnLogin;

        if (! $regenerateOnLogin) {
            $issues[] = [
                'type' => 'no_session_regeneration',
                'severity' => 'warning',
                'message' => 'Session ID not regenerated on login',
                'risk' => 'Vulnerable to session fixation attacks',
            ];
            $recommendations[] = 'Implement session regeneration on user authentication';
        }

        // Check lottery configuration for garbage collection
        $lottery = config('session.lottery', [2, 100]);
        $sessionConfig['lottery'] = $lottery;

        if (is_array($lottery) && count($lottery) === 2) {
            [$chance, $total] = $lottery;
            $percentage = ($chance / $total) * 100;

            if ($percentage < 1) {
                $issues[] = [
                    'type' => 'low_session_gc_probability',
                    'severity' => 'warning',
                    'message' => "Low session garbage collection probability: {$percentage}%",
                    'risk' => 'Old sessions may accumulate and not be cleaned up regularly',
                ];
                $recommendations[] = 'Increase session garbage collection probability to at least 1%';
            }
        }
    }

    private function getDriverRisk(string $driver): string
    {
        return match ($driver) {
            'cookie' => 'Session data stored in user-controlled cookies, highly vulnerable',
            'file' => 'Session files on disk, vulnerable if file system is compromised',
            'array' => 'Sessions only in memory, lost between requests (testing only)',
            default => 'Unknown driver risks',
        };
    }

    private function hasSessionRegenerationOnLogin(): bool
    {
        // This is a simplified check - in practice, you might scan controllers,
        // middleware, or authentication event listeners for session regeneration

        // Check if Laravel's default authentication methods are used
        $authConfig = config('auth.guards', []);

        // If using session-based authentication, Laravel handles this by default
        foreach ($authConfig as $guard) {
            if (($guard['driver'] ?? '') === 'session') {
                return true;
            }
        }

        return false;
    }

    private function getSessionSecurityLevel(array $sessionConfig): string
    {
        $score = 0;

        // Driver security (0-3 points)
        if (($sessionConfig['driver_security'] ?? '') === 'secure') {
            $score += 3;
        } elseif (($sessionConfig['driver'] ?? '') === 'file') {
            $score += 1;
        }

        // Cookie security (0-3 points)
        if ($sessionConfig['secure'] ?? false) {
            $score += 1;
        }
        if ($sessionConfig['http_only'] ?? false) {
            $score += 1;
        }
        if (in_array($sessionConfig['same_site'] ?? '', ['strict', 'lax'])) {
            $score += 1;
        }

        // Encryption (0-2 points)
        if ($sessionConfig['encrypt'] ?? false) {
            $score += 2;
        }

        // Session management (0-2 points)
        if ($sessionConfig['regenerate_on_login'] ?? false) {
            $score += 1;
        }
        if (($sessionConfig['lifetime'] ?? 0) <= 480) {
            $score += 1;
        }

        return match (true) {
            $score >= 9 => 'excellent',
            $score >= 7 => 'good',
            $score >= 5 => 'fair',
            $score >= 3 => 'poor',
            default => 'critical',
        };
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
