<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard\Rules\Security;

use DateTimeImmutable;
use Exception;
use Grazulex\LaravelSafeguard\Contracts\SafeguardRule;
use Grazulex\LaravelSafeguard\SafeguardResult;
use Illuminate\Support\Facades\File;

class ComposerPackageSecurity implements SafeguardRule
{
    public function id(): string
    {
        return 'composer-package-security';
    }

    public function description(): string
    {
        return 'Audits Composer packages for security vulnerabilities, outdated versions, and abandoned packages';
    }

    public function check(): SafeguardResult
    {
        $issues = [];
        $recommendations = [];
        $packageAudit = [];

        // Check if composer.lock exists
        if (! $this->checkComposerLockExists($issues)) {
            return SafeguardResult::fail(
                'Composer lock file not found',
                'critical',
                [
                    'issues' => $issues,
                    'recommendations' => ['Run composer install to generate composer.lock file'],
                ]
            );
        }

        // Analyze installed packages
        $packages = $this->getInstalledPackages();
        if ($packages === []) {
            return SafeguardResult::pass(
                'No packages to audit',
                ['packages_analyzed' => 0]
            );
        }

        // Check for security vulnerabilities
        $this->checkSecurityAdvisories($packages, $issues, $recommendations, $packageAudit);

        // Check for outdated packages
        $this->checkOutdatedPackages($packages, $issues, $recommendations, $packageAudit);

        // Check for abandoned packages
        $this->checkAbandonedPackages($packages, $issues, $recommendations, $packageAudit);

        // Check for development packages in production
        $this->checkDevPackagesInProduction($packages, $issues, $recommendations, $packageAudit);

        // Check for critical Laravel framework version
        $this->checkLaravelVersion($packages, $issues, $recommendations, $packageAudit);

        if ($issues !== []) {
            $severity = $this->determineSeverity($issues);

            return SafeguardResult::fail(
                'Composer package security issues detected',
                $severity,
                [
                    'issues' => $issues,
                    'recommendations' => $recommendations,
                    'package_audit' => $packageAudit,
                    'total_packages' => count($packages),
                    'total_issues' => count($issues),
                ]
            );
        }

        return SafeguardResult::pass(
            'Composer packages appear secure and up-to-date',
            [
                'package_audit' => $packageAudit,
                'total_packages' => count($packages),
                'security_level' => $this->getSecurityLevel($packageAudit),
            ]
        );
    }

    public function severity(): string
    {
        return 'warning';
    }

    public function appliesToEnvironment(string $environment): bool
    {
        return true; // Applies to all environments
    }

    private function checkComposerLockExists(array &$issues): bool
    {
        $lockPath = base_path('composer.lock');

        if (! File::exists($lockPath)) {
            $issues[] = [
                'type' => 'missing_composer_lock',
                'severity' => 'critical',
                'message' => 'composer.lock file not found',
                'description' => 'The composer.lock file is required for reproducible builds and security auditing',
                'file' => 'composer.lock',
            ];

            return false;
        }

        return true;
    }

    private function getInstalledPackages(): array
    {
        $lockPath = base_path('composer.lock');

        if (! File::exists($lockPath)) {
            return [];
        }

        try {
            $lockContent = json_decode(File::get($lockPath), true);
            $packages = [];

            // Get production packages
            if (isset($lockContent['packages'])) {
                foreach ($lockContent['packages'] as $package) {
                    $packages[$package['name']] = [
                        'version' => $package['version'] ?? 'unknown',
                        'type' => 'production',
                        'description' => $package['description'] ?? '',
                        'time' => $package['time'] ?? null,
                        'abandoned' => $package['abandoned'] ?? false,
                        'source' => $package['source'] ?? [],
                    ];
                }
            }

            // Get development packages
            if (isset($lockContent['packages-dev'])) {
                foreach ($lockContent['packages-dev'] as $package) {
                    $packages[$package['name']] = [
                        'version' => $package['version'] ?? 'unknown',
                        'type' => 'development',
                        'description' => $package['description'] ?? '',
                        'time' => $package['time'] ?? null,
                        'abandoned' => $package['abandoned'] ?? false,
                        'source' => $package['source'] ?? [],
                    ];
                }
            }

            return $packages;
        } catch (Exception $e) {
            return [];
        }
    }

    private function checkSecurityAdvisories(array $packages, array &$issues, array &$recommendations, array &$packageAudit): void
    {
        // Check against known security advisories
        $knownVulnerabilities = $this->getKnownVulnerabilities();

        foreach ($packages as $name => $package) {
            if (isset($knownVulnerabilities[$name])) {
                foreach ($knownVulnerabilities[$name] as $vulnerability) {
                    if ($this->isVersionAffected($package['version'], $vulnerability['affected_versions'])) {
                        $issues[] = [
                            'type' => 'security_vulnerability',
                            'severity' => $vulnerability['severity'] ?? 'high',
                            'package' => $name,
                            'version' => $package['version'],
                            'vulnerability' => $vulnerability['title'],
                            'description' => $vulnerability['description'],
                            'cve' => $vulnerability['cve'] ?? null,
                            'fixed_in' => $vulnerability['fixed_in'] ?? null,
                        ];

                        $packageAudit[$name]['vulnerabilities'][] = $vulnerability;
                    }
                }
            }
        }

        if ($issues !== []) {
            $recommendations[] = 'Update vulnerable packages to their latest secure versions';
            $recommendations[] = 'Run composer audit to check for additional security advisories';
        }
    }

    private function checkOutdatedPackages(array $packages, array &$issues, array &$recommendations, array &$packageAudit): void
    {
        foreach ($packages as $name => $package) {
            $timeSinceUpdate = $this->getTimeSinceLastUpdate($package['time']);

            // Check if package is very outdated (more than 2 years)
            if ($timeSinceUpdate > 730) { // 2 years in days
                $issues[] = [
                    'type' => 'very_outdated_package',
                    'severity' => 'warning',
                    'package' => $name,
                    'version' => $package['version'],
                    'last_update' => $package['time'],
                    'days_since_update' => $timeSinceUpdate,
                    'message' => "Package {$name} hasn't been updated in {$timeSinceUpdate} days",
                ];

                $packageAudit[$name]['outdated'] = true;
                $packageAudit[$name]['days_since_update'] = $timeSinceUpdate;
            }
            // Check if package is moderately outdated (more than 1 year)
            elseif ($timeSinceUpdate > 365) {
                $packageAudit[$name]['potentially_outdated'] = true;
                $packageAudit[$name]['days_since_update'] = $timeSinceUpdate;
            }
        }

        if (array_filter($issues, fn ($issue): bool => $issue['type'] === 'very_outdated_package') !== []) {
            $recommendations[] = 'Review and update packages that haven\'t been updated in over 2 years';
            $recommendations[] = 'Consider finding alternative packages for very outdated dependencies';
        }
    }

    private function checkAbandonedPackages(array $packages, array &$issues, array &$recommendations, array &$packageAudit): void
    {
        foreach ($packages as $name => $package) {
            if ($package['abandoned']) {
                $issues[] = [
                    'type' => 'abandoned_package',
                    'severity' => 'warning',
                    'package' => $name,
                    'version' => $package['version'],
                    'message' => "Package {$name} has been abandoned by its maintainer",
                    'replacement' => is_string($package['abandoned']) ? $package['abandoned'] : null,
                ];

                $packageAudit[$name]['abandoned'] = true;
                $packageAudit[$name]['replacement'] = is_string($package['abandoned']) ? $package['abandoned'] : null;
            }
        }

        if (array_filter($issues, fn ($issue): bool => $issue['type'] === 'abandoned_package') !== []) {
            $recommendations[] = 'Replace abandoned packages with maintained alternatives';
            $recommendations[] = 'Fork abandoned packages if no alternatives exist and they are critical';
        }
    }

    private function checkDevPackagesInProduction(array $packages, array &$issues, array &$recommendations, array &$packageAudit): void
    {
        if (app()->environment('production')) {
            $devPackages = array_filter($packages, fn ($package): bool => $package['type'] === 'development');

            if ($devPackages !== []) {
                $issues[] = [
                    'type' => 'dev_packages_in_production',
                    'severity' => 'warning',
                    'packages' => array_keys($devPackages),
                    'count' => count($devPackages),
                    'message' => 'Development packages detected in production environment',
                ];

                $packageAudit['dev_packages_in_production'] = array_keys($devPackages);

                $recommendations[] = 'Use composer install --no-dev in production to exclude development packages';
            }
        }
    }

    private function checkLaravelVersion(array $packages, array &$issues, array &$recommendations, array &$packageAudit): void
    {
        // Check Laravel framework version
        if (isset($packages['laravel/framework'])) {
            $laravelVersion = $packages['laravel/framework']['version'];
            $versionInfo = $this->analyzeLaravelVersion($laravelVersion);

            if (! $versionInfo['is_supported']) {
                $issues[] = [
                    'type' => 'unsupported_laravel_version',
                    'severity' => 'high',
                    'package' => 'laravel/framework',
                    'version' => $laravelVersion,
                    'message' => "Laravel {$laravelVersion} is no longer supported",
                    'latest_lts' => $versionInfo['latest_lts'],
                    'latest_stable' => $versionInfo['latest_stable'],
                ];

                $packageAudit['laravel/framework']['unsupported'] = true;
                $packageAudit['laravel/framework']['version_info'] = $versionInfo;

                $recommendations[] = "Upgrade Laravel to a supported version (latest LTS: {$versionInfo['latest_lts']})";
            } elseif (! $versionInfo['is_latest_lts']) {
                $packageAudit['laravel/framework']['upgrade_available'] = true;
                $packageAudit['laravel/framework']['version_info'] = $versionInfo;
            }
        }
    }

    private function getKnownVulnerabilities(): array
    {
        // In a real implementation, this would fetch from security advisories API
        // For now, return some common known vulnerabilities as examples
        return [
            'symfony/http-kernel' => [
                [
                    'title' => 'HTTP Header Injection',
                    'description' => 'Possible HTTP header injection when using user input in response headers',
                    'affected_versions' => ['<4.4.13', '>=5.0,<5.1.5'],
                    'fixed_in' => ['4.4.13', '5.1.5'],
                    'severity' => 'high',
                    'cve' => 'CVE-2020-15094',
                ],
            ],
            'symfony/mime' => [
                [
                    'title' => 'CSV injection',
                    'description' => 'CSV injection in EmailMimeMessage',
                    'affected_versions' => ['>=4.4.0,<4.4.7', '>=5.0.0,<5.0.7'],
                    'fixed_in' => ['4.4.7', '5.0.7'],
                    'severity' => 'medium',
                    'cve' => 'CVE-2020-5255',
                ],
            ],
        ];
    }

    private function isVersionAffected(string $version, array $affectedVersions): bool
    {
        foreach ($affectedVersions as $constraint) {
            if ($this->versionMatchesConstraint($version, $constraint)) {
                return true;
            }
        }

        return false;
    }

    private function versionMatchesConstraint(string $version, string $constraint): bool
    {
        // Simplified version constraint checking
        // In a real implementation, use composer/semver for proper version constraint checking

        if (str_starts_with($constraint, '<')) {
            $targetVersion = mb_ltrim($constraint, '<');

            return version_compare($version, $targetVersion, '<');
        }

        if (str_starts_with($constraint, '>=')) {
            $parts = explode(',', $constraint);
            if (count($parts) === 2) {
                $minVersion = mb_ltrim($parts[0], '>=');
                $maxVersion = mb_ltrim($parts[1], '<');

                return version_compare($version, $minVersion, '>=') && version_compare($version, $maxVersion, '<');
            }
        }

        return false;
    }

    private function getTimeSinceLastUpdate(?string $updateTime): int
    {
        if ($updateTime === null || $updateTime === '' || $updateTime === '0') {
            return 9999; // Very old if no time available
        }

        try {
            $updateDate = new DateTimeImmutable($updateTime);
            $now = new DateTimeImmutable();

            return $now->diff($updateDate)->days;
        } catch (Exception $e) {
            return 9999;
        }
    }

    private function analyzeLaravelVersion(string $version): array
    {
        // Extract major.minor version
        preg_match('/^v?(\d+)\.(\d+)/', $version, $matches);
        $majorMinor = isset($matches[1], $matches[2]) ? "{$matches[1]}.{$matches[2]}" : $version;

        // Define Laravel version support status (as of 2025)
        $supportedVersions = ['11.0', '10.0']; // Current LTS versions
        $latestLts = '11.0';
        $latestStable = '11.0';

        return [
            'is_supported' => in_array($majorMinor, $supportedVersions) || version_compare($majorMinor, '11.0', '>='),
            'is_latest_lts' => version_compare($majorMinor, $latestLts, '>='),
            'latest_lts' => $latestLts,
            'latest_stable' => $latestStable,
            'major_minor' => $majorMinor,
        ];
    }

    private function determineSeverity(array $issues): string
    {
        $hasCritical = false;
        $hasHigh = false;

        foreach ($issues as $issue) {
            switch ($issue['severity']) {
                case 'critical':
                    $hasCritical = true;
                    break;
                case 'high':
                    $hasHigh = true;
                    break;
            }
        }

        if ($hasCritical) {
            return 'critical';
        }
        if ($hasHigh) {
            return 'high';
        }

        return 'warning';

    }

    private function getSecurityLevel(array $packageAudit): string
    {
        $vulnerabilityCount = 0;
        $outdatedCount = 0;
        $abandonedCount = 0;

        foreach ($packageAudit as $audit) {
            if (isset($audit['vulnerabilities'])) {
                $vulnerabilityCount += count($audit['vulnerabilities']);
            }
            if (isset($audit['outdated']) && $audit['outdated']) {
                $outdatedCount++;
            }
            if (isset($audit['abandoned']) && $audit['abandoned']) {
                $abandonedCount++;
            }
        }

        if ($vulnerabilityCount > 0) {
            return 'vulnerable';
        }
        if ($outdatedCount > 5 || $abandonedCount > 2) {
            return 'needs_attention';
        }
        if ($outdatedCount > 0 || $abandonedCount > 0) {
            return 'good';
        }

        return 'excellent';

    }
}
