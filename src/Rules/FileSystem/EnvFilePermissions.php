<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard\Rules\FileSystem;

use Grazulex\LaravelSafeguard\Contracts\SafeguardRule;
use Grazulex\LaravelSafeguard\SafeguardResult;
use Illuminate\Support\Facades\File;

class EnvFilePermissions implements SafeguardRule
{
    public function id(): string
    {
        return 'env-file-permissions';
    }

    public function description(): string
    {
        return 'Checks that .env file has appropriate permissions (not world-readable)';
    }

    public function check(): SafeguardResult
    {
        $envFile = base_path('.env');

        if (! File::exists($envFile)) {
            return SafeguardResult::warning(
                '.env file not found',
                [
                    'file_path' => $envFile,
                    'recommendation' => 'Create a .env file from .env.example',
                ]
            );
        }

        $permissions = mb_substr(sprintf('%o', fileperms($envFile)), -4);
        $isWorldReadable = (octdec($permissions) & 0004) !== 0;
        $isGroupReadable = (octdec($permissions) & 0040) !== 0;

        $issues = [];

        if ($isWorldReadable) {
            $issues[] = 'File is world-readable';
        }

        if ($isGroupReadable && posix_getgid() !== posix_getegid()) {
            $issues[] = 'File is group-readable';
        }

        if ($issues !== []) {
            return SafeguardResult::warning(
                'Environment file has overly permissive permissions',
                [
                    'current_permissions' => $permissions,
                    'recommended_permissions' => '600 (rw-------)',
                    'security_risk' => 'Environment variables may be readable by other users',
                    'recommendation' => 'Run: chmod 600 '.$envFile,
                ]
            );
        }

        return SafeguardResult::pass(
            'Environment file permissions are secure',
            [
                'permissions' => $permissions,
                'file_path' => $envFile,
            ]
        );
    }

    public function appliesToEnvironment(string $environment): bool
    {
        // Only check in non-Windows environments
        return PHP_OS_FAMILY !== 'Windows';
    }

    public function severity(): string
    {
        return 'error';
    }
}
