<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard\Rules\Authentication;

use Grazulex\LaravelSafeguard\Rules\AbstractSafeguardRule;
use Grazulex\LaravelSafeguard\SafeguardResult;

class PasswordPolicyCompliance extends AbstractSafeguardRule
{
    public function id(): string
    {
        return 'password-policy-compliance';
    }

    public function description(): string
    {
        return 'Verifies that password policy configuration meets security standards';
    }

    public function check(): SafeguardResult
    {
        $issues = [];
        $recommendations = [];

        // Check password validation rules
        $this->checkPasswordValidationRules($issues, $recommendations);

        // Check password hashing configuration
        $this->checkPasswordHashingConfig($issues, $recommendations);

        // Check password reset configuration
        $this->checkPasswordResetConfig($issues, $recommendations);

        if ($issues !== []) {
            $severity = $this->determineSeverity($issues);

            return SafeguardResult::fail(
                'Password policy compliance issues detected',
                $severity,
                [
                    'issues' => $issues,
                    'recommendations' => $recommendations,
                    'total_issues' => count($issues),
                ]
            );
        }

        return SafeguardResult::pass(
            'Password policy configuration meets security standards',
            [
                'compliant_policies' => [
                    'validation_rules' => 'configured',
                    'hashing_algorithm' => config('hashing.driver', 'bcrypt'),
                    'password_reset' => 'secure',
                ],
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

    private function checkPasswordValidationRules(array &$issues, array &$recommendations): void
    {
        // Check if password validation rules are configured
        $authConfig = config('auth.password_validation', []);

        if (empty($authConfig)) {
            // Check for Laravel's default validation in User model or form requests
            $hasMinLength = $this->hasPasswordMinLength();
            $hasComplexity = $this->hasPasswordComplexity();

            if (! $hasMinLength) {
                $issues[] = [
                    'type' => 'no_minimum_length',
                    'severity' => 'error',
                    'message' => 'No minimum password length requirement found',
                    'risk' => 'Weak passwords can be easily compromised',
                ];
                $recommendations[] = 'Implement minimum password length of 8-12 characters';
            }

            if (! $hasComplexity) {
                $issues[] = [
                    'type' => 'no_complexity_rules',
                    'severity' => 'warning',
                    'message' => 'No password complexity requirements found',
                    'risk' => 'Simple passwords are vulnerable to dictionary attacks',
                ];
                $recommendations[] = 'Add complexity rules (uppercase, lowercase, numbers, symbols)';
            }
        }

        // Check password confirmation requirement
        if (! $this->hasPasswordConfirmation()) {
            $issues[] = [
                'type' => 'no_password_confirmation',
                'severity' => 'warning',
                'message' => 'Password confirmation not enforced',
            ];
            $recommendations[] = 'Require password confirmation during registration/changes';
        }
    }

    private function checkPasswordHashingConfig(array &$issues, array &$recommendations): void
    {
        $hashingDriver = config('hashing.driver');
        $hashingConfig = config('hashing.drivers', []);

        // Check hashing algorithm
        if ($hashingDriver !== 'bcrypt' && $hashingDriver !== 'argon2id') {
            $issues[] = [
                'type' => 'weak_hashing_algorithm',
                'severity' => 'critical',
                'message' => "Weak password hashing algorithm: {$hashingDriver}",
                'current_driver' => $hashingDriver,
            ];
            $recommendations[] = 'Use bcrypt or argon2id for password hashing';
        }

        // Check bcrypt rounds
        if ($hashingDriver === 'bcrypt') {
            $rounds = $hashingConfig['bcrypt']['rounds'] ?? 10;
            if ($rounds < 12) {
                $issues[] = [
                    'type' => 'low_bcrypt_rounds',
                    'severity' => 'warning',
                    'message' => "Low bcrypt rounds: {$rounds} (recommended: 12+)",
                    'current_rounds' => $rounds,
                ];
                $recommendations[] = 'Increase bcrypt rounds to 12 or higher for better security';
            }
        }

        // Check Argon2 configuration
        if ($hashingDriver === 'argon2id') {
            $argonConfig = $hashingConfig['argon'] ?? [];
            $memory = $argonConfig['memory'] ?? 1024;
            $time = $argonConfig['time'] ?? 2;
            $threads = $argonConfig['threads'] ?? 2;

            if ($memory < 65536) { // 64MB minimum recommended
                $issues[] = [
                    'type' => 'low_argon_memory',
                    'severity' => 'warning',
                    'message' => "Low Argon2 memory cost: {$memory}KB (recommended: 65536KB+)",
                ];
                $recommendations[] = 'Increase Argon2 memory cost to 65536KB or higher';
            }
        }
    }

    private function checkPasswordResetConfig(array &$issues, array &$recommendations): void
    {
        $resetConfig = config('auth.passwords.users', []);

        // Check reset token expiry
        $expire = $resetConfig['expire'] ?? 60;
        if ($expire > 60) {
            $issues[] = [
                'type' => 'long_reset_expiry',
                'severity' => 'warning',
                'message' => "Password reset tokens expire in {$expire} minutes (recommended: 60 or less)",
                'current_expiry' => $expire,
            ];
            $recommendations[] = 'Set password reset token expiry to 60 minutes or less';
        }

        // Check throttling configuration
        $throttle = $resetConfig['throttle'] ?? 60;
        if ($throttle < 60) {
            $issues[] = [
                'type' => 'low_reset_throttle',
                'severity' => 'warning',
                'message' => "Password reset throttle too low: {$throttle} seconds",
                'current_throttle' => $throttle,
            ];
            $recommendations[] = 'Set password reset throttle to at least 60 seconds';
        }
    }

    private function hasPasswordMinLength(): bool
    {
        // This is a simplified check - in practice, you might scan validation rules
        // in form requests, models, or custom validation rules
        $validationRules = config('validation.password_rules', []);

        return ! empty($validationRules) &&
               (str_contains(implode('|', $validationRules), 'min:') ||
                str_contains(implode('|', $validationRules), 'min_length:'));
    }

    private function hasPasswordComplexity(): bool
    {
        $validationRules = config('validation.password_rules', []);

        return ! empty($validationRules) &&
               (str_contains(implode('|', $validationRules), 'regex:') ||
                str_contains(implode('|', $validationRules), 'complexity'));
    }

    private function hasPasswordConfirmation(): bool
    {
        $validationRules = config('validation.password_rules', []);

        return ! empty($validationRules) &&
               str_contains(implode('|', $validationRules), 'confirmed');
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
