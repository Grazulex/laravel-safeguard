<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard\Rules\Encryption;

use Grazulex\LaravelSafeguard\Contracts\SafeguardRule;
use Grazulex\LaravelSafeguard\SafeguardResult;
use Illuminate\Support\Facades\File;

class EncryptionKeyRotation implements SafeguardRule
{
    public function id(): string
    {
        return 'encryption-key-rotation';
    }

    public function description(): string
    {
        return 'Verifies encryption key management and rotation practices';
    }

    public function check(): SafeguardResult
    {
        $issues = [];
        $recommendations = [];
        $keyStatus = [];

        // Check APP_KEY configuration
        $this->checkAppKeyConfiguration($issues, $recommendations, $keyStatus);

        // Check key rotation implementation
        $this->checkKeyRotationImplementation($issues, $recommendations, $keyStatus);

        // Check key storage and backup
        $this->checkKeyStorageAndBackup($issues, $recommendations, $keyStatus);

        // Check encryption configuration
        $this->checkEncryptionConfiguration($issues, $recommendations, $keyStatus);

        if ($issues !== []) {
            $severity = $this->determineSeverity($issues);

            return SafeguardResult::fail(
                'Encryption key management issues detected',
                $severity,
                [
                    'issues' => $issues,
                    'recommendations' => $recommendations,
                    'key_status' => $keyStatus,
                    'total_issues' => count($issues),
                ]
            );
        }

        return SafeguardResult::pass(
            'Encryption key management is properly configured',
            [
                'key_status' => $keyStatus,
                'security_level' => $this->getKeySecurityLevel($keyStatus),
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

    private function checkAppKeyConfiguration(array &$issues, array &$recommendations, array &$keyStatus): void
    {
        $appKey = config('app.key');
        $keyStatus['app_key_set'] = ! empty($appKey);

        if (empty($appKey)) {
            $issues[] = [
                'type' => 'missing_app_key',
                'severity' => 'critical',
                'message' => 'APP_KEY is not set',
                'risk' => 'Encryption and decryption will fail',
            ];
            $recommendations[] = 'Generate APP_KEY: php artisan key:generate';

            return;
        }

        // Check key format and strength
        $this->validateAppKeyFormat($appKey, $issues, $recommendations, $keyStatus);

        // Check if key appears to be default or weak
        $this->checkForWeakKeys($appKey, $issues, $recommendations, $keyStatus);
    }

    private function validateAppKeyFormat(string $appKey, array &$issues, array &$recommendations, array &$keyStatus): void
    {
        // Laravel keys should start with 'base64:' for base64 encoded keys
        if (str_starts_with($appKey, 'base64:')) {
            $keyData = base64_decode(mb_substr($appKey, 7));
            $keyLength = mb_strlen($keyData);
            $keyStatus['key_format'] = 'base64';
            $keyStatus['key_length'] = $keyLength;

            // AES-256 requires 32 bytes, AES-128 requires 16 bytes
            if ($keyLength < 16) {
                $issues[] = [
                    'type' => 'weak_key_length',
                    'severity' => 'critical',
                    'message' => "Encryption key too short: {$keyLength} bytes",
                    'current_length' => $keyLength,
                    'risk' => 'Weak encryption vulnerable to brute force attacks',
                ];
                $recommendations[] = 'Generate a new key with proper length: php artisan key:generate';
            } elseif ($keyLength < 32) {
                $issues[] = [
                    'type' => 'suboptimal_key_length',
                    'severity' => 'warning',
                    'message' => "Encryption key uses AES-128: {$keyLength} bytes",
                    'current_length' => $keyLength,
                ];
                $recommendations[] = 'Consider using AES-256 for stronger encryption';
            }
        } else {
            // Plain text key
            $keyLength = mb_strlen($appKey);
            $keyStatus['key_format'] = 'plain';
            $keyStatus['key_length'] = $keyLength;

            if ($keyLength < 32) {
                $issues[] = [
                    'type' => 'plain_text_key_too_short',
                    'severity' => 'error',
                    'message' => "Plain text encryption key too short: {$keyLength} characters",
                    'risk' => 'Weak encryption key compromises security',
                ];
                $recommendations[] = 'Generate a proper base64 encoded key: php artisan key:generate';
            }
        }
    }

    private function checkForWeakKeys(string $appKey, array &$issues, array &$recommendations, array &$keyStatus): void
    {
        $weakPatterns = [
            'SomeRandomString',
            'your-secret-key',
            'laravel',
            'password',
            '123456',
            'secret',
            'key',
        ];

        $normalizedKey = mb_strtolower($appKey);
        $keyStatus['appears_secure'] = true;

        foreach ($weakPatterns as $pattern) {
            if (str_contains($normalizedKey, mb_strtolower($pattern))) {
                $issues[] = [
                    'type' => 'weak_key_pattern',
                    'severity' => 'critical',
                    'message' => 'APP_KEY contains predictable patterns',
                    'risk' => 'Predictable keys can be easily guessed by attackers',
                ];
                $recommendations[] = 'Generate a new cryptographically secure key: php artisan key:generate';
                $keyStatus['appears_secure'] = false;
                break;
            }
        }

        // Check for repeated characters or patterns
        if ($this->hasRepeatedPatterns($appKey)) {
            $issues[] = [
                'type' => 'repeated_key_patterns',
                'severity' => 'warning',
                'message' => 'APP_KEY contains repeated patterns',
            ];
            $recommendations[] = 'Generate a new random key to avoid patterns';
            $keyStatus['appears_secure'] = false;
        }
    }

    private function checkKeyRotationImplementation(array &$issues, array &$recommendations, array &$keyStatus): void
    {
        // Check for key rotation implementation
        $hasKeyRotation = $this->hasKeyRotationImplementation();
        $keyStatus['rotation_implemented'] = $hasKeyRotation;

        if (! $hasKeyRotation) {
            $issues[] = [
                'type' => 'no_key_rotation',
                'severity' => 'warning',
                'message' => 'No encryption key rotation implementation detected',
                'risk' => 'Long-lived keys increase security risk over time',
            ];
            $recommendations[] = 'Implement encryption key rotation strategy';
            $recommendations[] = 'Consider using packages like "pragmarx/recovery" for key rotation';
        }

        // Check for multiple key support
        $hasMultipleKeys = $this->hasMultipleKeySupport();
        $keyStatus['multiple_keys_supported'] = $hasMultipleKeys;

        if (! $hasMultipleKeys && $hasKeyRotation) {
            $issues[] = [
                'type' => 'no_multiple_key_support',
                'severity' => 'warning',
                'message' => 'Key rotation implemented but no multiple key support',
                'risk' => 'Key rotation may break existing encrypted data',
            ];
            $recommendations[] = 'Implement multiple key support for gradual rotation';
        }
    }

    private function checkKeyStorageAndBackup(array &$issues, array &$recommendations, array &$keyStatus): void
    {
        // Check if .env file is properly protected
        $envPath = base_path('.env');
        $keyStatus['env_file_exists'] = File::exists($envPath);

        if (File::exists($envPath)) {
            $permissions = mb_substr(sprintf('%o', fileperms($envPath)), -3);
            $keyStatus['env_file_permissions'] = $permissions;

            if ($permissions !== '600' && $permissions !== '644') {
                $issues[] = [
                    'type' => 'insecure_env_permissions',
                    'severity' => 'error',
                    'message' => "Insecure .env file permissions: {$permissions}",
                    'current_permissions' => $permissions,
                    'risk' => 'Encryption keys may be readable by unauthorized users',
                ];
                $recommendations[] = 'Set .env file permissions to 600: chmod 600 .env';
            }
        }

        // Check for key backup strategy
        $hasKeyBackup = $this->hasKeyBackupStrategy();
        $keyStatus['backup_strategy'] = $hasKeyBackup;

        if (! $hasKeyBackup) {
            $issues[] = [
                'type' => 'no_key_backup',
                'severity' => 'warning',
                'message' => 'No encryption key backup strategy detected',
                'risk' => 'Key loss could result in permanent data loss',
            ];
            $recommendations[] = 'Implement secure key backup and recovery procedures';
            $recommendations[] = 'Store key backups in secure, separate locations';
        }

        // Check for key versioning
        $hasKeyVersioning = $this->hasKeyVersioning();
        $keyStatus['key_versioning'] = $hasKeyVersioning;

        if (! $hasKeyVersioning) {
            $issues[] = [
                'type' => 'no_key_versioning',
                'severity' => 'info',
                'message' => 'No encryption key versioning system detected',
            ];
            $recommendations[] = 'Consider implementing key versioning for better management';
        }
    }

    private function checkEncryptionConfiguration(array &$issues, array &$recommendations, array &$keyStatus): void
    {
        $cipher = config('app.cipher', 'AES-256-CBC');
        $keyStatus['cipher'] = $cipher;

        // Check cipher strength
        $strongCiphers = ['AES-256-CBC', 'AES-256-GCM'];
        $weakCiphers = ['AES-128-CBC', 'DES', '3DES'];

        if (in_array($cipher, $weakCiphers)) {
            $issues[] = [
                'type' => 'weak_cipher',
                'severity' => 'error',
                'message' => "Weak encryption cipher: {$cipher}",
                'current_cipher' => $cipher,
                'risk' => 'Weak ciphers can be broken by modern attacks',
            ];
            $recommendations[] = 'Use AES-256-CBC or AES-256-GCM for strong encryption';
        } elseif (! in_array($cipher, $strongCiphers)) {
            $issues[] = [
                'type' => 'unknown_cipher',
                'severity' => 'warning',
                'message' => "Unknown or untested cipher: {$cipher}",
                'current_cipher' => $cipher,
            ];
            $recommendations[] = 'Verify cipher security and consider using standard AES-256-CBC';
        }

        // Check for additional encryption libraries
        $this->checkEncryptionLibraries($issues, $recommendations, $keyStatus);
    }

    private function checkEncryptionLibraries(array &$issues, array &$recommendations, array &$keyStatus): void
    {
        $encryptionPackages = [
            'defuse/php-encryption' => 'Defuse PHP Encryption',
            'paragonie/halite' => 'Halite Cryptography Library',
            'spomky-labs/jose' => 'JOSE Framework',
        ];

        $installedPackages = [];

        foreach ($encryptionPackages as $package => $name) {
            if ($this->isPackageInstalled($package)) {
                $installedPackages[] = $name;
            }
        }

        $keyStatus['additional_encryption_libraries'] = $installedPackages;

        if ($installedPackages === []) {
            $issues[] = [
                'type' => 'no_additional_encryption',
                'severity' => 'info',
                'message' => 'No additional encryption libraries detected',
            ];
            $recommendations[] = 'Consider additional encryption libraries for specific use cases';
        }
    }

    private function hasRepeatedPatterns(string $key): bool
    {
        // Simple check for repeated characters
        $normalized = preg_replace('/[^a-zA-Z0-9]/', '', $key);
        $length = mb_strlen($normalized);

        if ($length < 4) {
            return false;
        }

        // Check for sequences like 'aaaa' or '1111'
        for ($i = 0; $i < $length - 3; $i++) {
            if ($normalized[$i] === $normalized[$i + 1] &&
                $normalized[$i] === $normalized[$i + 2] &&
                $normalized[$i] === $normalized[$i + 3]) {
                return true;
            }
        }

        return false;
    }

    private function hasKeyRotationImplementation(): bool
    {
        // Check for common key rotation implementations
        if (File::exists(app_path('Services/KeyRotationService.php'))) {
            return true;
        }
        if (File::exists(app_path('Console/Commands/RotateEncryptionKey.php'))) {
            return true;
        }
        if ($this->isPackageInstalled('pragmarx/recovery')) {
            return true;
        }

        return (bool) config('app.encryption.rotation_enabled', false);
    }

    private function hasMultipleKeySupport(): bool
    {
        // Check for multiple key support
        if (config('app.encryption.previous_keys') !== null) {
            return true;
        }
        if (File::exists(app_path('Services/MultiKeyEncryption.php'))) {
            return true;
        }

        return $this->isPackageInstalled('paragonie/halite');
    }

    private function hasKeyBackupStrategy(): bool
    {
        // Check for key backup implementations
        if (File::exists(storage_path('keys/'))) {
            return true;
        }
        if (config('app.key_backup_location') !== null) {
            return true;
        }

        return File::exists(app_path('Console/Commands/BackupEncryptionKeys.php'));
    }

    private function hasKeyVersioning(): bool
    {
        // Check for key versioning system
        return config('app.encryption.key_version') !== null ||
               File::exists(app_path('Services/KeyVersioningService.php'));
    }

    private function isPackageInstalled(string $package): bool
    {
        $composerLock = base_path('composer.lock');

        if (! File::exists($composerLock)) {
            return false;
        }

        $content = File::get($composerLock);

        return str_contains($content, '"name": "'.$package.'"');
    }

    private function getKeySecurityLevel(array $keyStatus): string
    {
        $score = 0;

        // Basic key security (0-4 points)
        if ($keyStatus['app_key_set'] ?? false) {
            $score += 1;
        }
        if (($keyStatus['appears_secure'] ?? false)) {
            $score += 2;
        }
        if (($keyStatus['key_length'] ?? 0) >= 32) {
            $score += 1;
        }

        // Advanced features (0-4 points)
        if ($keyStatus['rotation_implemented'] ?? false) {
            $score += 1;
        }
        if ($keyStatus['multiple_keys_supported'] ?? false) {
            $score += 1;
        }
        if ($keyStatus['backup_strategy'] ?? false) {
            $score += 1;
        }
        if ($keyStatus['key_versioning'] ?? false) {
            $score += 1;
        }

        return match (true) {
            $score >= 7 => 'excellent',
            $score >= 5 => 'good',
            $score >= 3 => 'fair',
            $score >= 1 => 'poor',
            default => 'critical',
        };
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
