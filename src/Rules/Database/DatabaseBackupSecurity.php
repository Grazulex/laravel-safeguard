<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard\Rules\Database;

use Grazulex\LaravelSafeguard\Rules\AbstractSafeguardRule;
use Grazulex\LaravelSafeguard\SafeguardResult;
use Illuminate\Support\Facades\File;

class DatabaseBackupSecurity extends AbstractSafeguardRule
{
    public function id(): string
    {
        return 'database-backup-security';
    }

    public function description(): string
    {
        return 'Verifies that database backup configurations are secure';
    }

    public function check(): SafeguardResult
    {
        $issues = [];
        $recommendations = [];

        // Check spatie/laravel-backup configuration if available
        if ($this->hasLaravelBackupPackage()) {
            $backupConfig = config('backup', []);
            $this->checkBackupConfiguration($backupConfig, $issues, $recommendations);
        }

        // Check for common backup locations
        $this->checkCommonBackupLocations($issues, $recommendations);

        // Check for backup scripts
        $this->checkBackupScripts($issues, $recommendations);

        if ($issues !== []) {
            return SafeguardResult::warning(
                'Database backup security issues detected',
                [
                    'issues' => $issues,
                    'recommendations' => $recommendations,
                    'security_impact' => 'Insecure backups can expose sensitive data if compromised',
                ]
            );
        }

        return SafeguardResult::pass(
            'Database backup configuration appears secure',
            [
                'has_backup_package' => $this->hasLaravelBackupPackage(),
                'recommendations' => [
                    'Regularly test backup restoration procedures',
                    'Monitor backup success/failure notifications',
                    'Implement backup retention policies',
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

    private function hasLaravelBackupPackage(): bool
    {
        return File::exists(base_path('vendor/spatie/laravel-backup'));
    }

    private function checkBackupConfiguration(array $config, array &$issues, array &$recommendations): void
    {
        // Check backup destinations
        $destinations = $config['backup']['destination']['disks'] ?? [];

        if (in_array('local', $destinations)) {
            $issues[] = [
                'type' => 'local_backup_destination',
                'severity' => 'warning',
                'message' => 'Backups are stored locally - consider offsite storage',
            ];
            $recommendations[] = 'Use cloud storage (S3, Google Cloud, etc.) for backup destinations';
        }

        // Check encryption
        $encryptBackups = $config['backup']['backup']['password_protect'] ?? false;
        if (! $encryptBackups) {
            $issues[] = [
                'type' => 'unencrypted_backups',
                'severity' => 'error',
                'message' => 'Backups are not password protected/encrypted',
            ];
            $recommendations[] = 'Enable backup encryption with a strong password';
        }

        // Check notification configuration
        $notifications = $config['backup']['notifications'] ?? [];
        if (empty($notifications['mail']['to'] ?? [])) {
            $issues[] = [
                'type' => 'no_backup_notifications',
                'severity' => 'warning',
                'message' => 'No email notifications configured for backup status',
            ];
            $recommendations[] = 'Configure email notifications for backup success/failure';
        }

        // Check cleanup configuration
        $cleanup = $config['backup']['cleanup'] ?? [];
        if (empty($cleanup['strategy'] ?? '')) {
            $issues[] = [
                'type' => 'no_cleanup_strategy',
                'severity' => 'warning',
                'message' => 'No backup cleanup strategy configured',
            ];
            $recommendations[] = 'Configure automatic cleanup of old backups';
        }
    }

    private function checkCommonBackupLocations(array &$issues, array &$recommendations): void
    {
        $vulnerableLocations = [
            'public/backups',
            'storage/app/public/backups',
            'public/db',
            'public/database',
        ];

        foreach ($vulnerableLocations as $location) {
            $fullPath = base_path($location);
            if (File::exists($fullPath)) {
                $files = File::files($fullPath);
                if (! empty($files)) {
                    $issues[] = [
                        'type' => 'public_backup_location',
                        'severity' => 'critical',
                        'message' => "Database backups found in publicly accessible location: {$location}",
                        'file_count' => count($files),
                    ];
                    $recommendations[] = "Move backups from {$location} to a secure, non-public location";
                }
            }
        }
    }

    private function checkBackupScripts(array &$issues, array &$recommendations): void
    {
        $scriptLocations = [
            'backup.sh',
            'scripts/backup.sh',
            'bin/backup.sh',
            'database/backup.sh',
        ];

        foreach ($scriptLocations as $script) {
            $fullPath = base_path($script);
            if (File::exists($fullPath)) {
                $content = File::get($fullPath);

                // Check for hardcoded credentials
                if ($this->hasHardcodedCredentials($content)) {
                    $issues[] = [
                        'type' => 'hardcoded_credentials_in_script',
                        'severity' => 'critical',
                        'message' => "Hardcoded credentials detected in backup script: {$script}",
                    ];
                    $recommendations[] = "Remove hardcoded credentials from {$script} and use environment variables";
                }

                // Check for insecure permissions
                $permissions = mb_substr(sprintf('%o', fileperms($fullPath)), -3);
                if ($permissions > '755') {
                    $issues[] = [
                        'type' => 'insecure_script_permissions',
                        'severity' => 'warning',
                        'message' => "Backup script has overly permissive permissions: {$script} ({$permissions})",
                    ];
                    $recommendations[] = "Set secure permissions for {$script} (chmod 755 or more restrictive)";
                }
            }
        }
    }

    private function hasHardcodedCredentials(string $content): bool
    {
        $patterns = [
            '/mysql.*-p["\']?[a-zA-Z0-9]+/',
            '/pg_dump.*password["\']?[a-zA-Z0-9]+/',
            '/MYSQL_PWD\s*=\s*["\']?[a-zA-Z0-9]+/',
            '/PGPASSWORD\s*=\s*["\']?[a-zA-Z0-9]+/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }
}
