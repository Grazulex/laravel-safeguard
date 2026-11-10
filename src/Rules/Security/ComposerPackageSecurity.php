<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard\Rules\Security;

use DateTimeImmutable;
use Exception;
use Grazulex\LaravelSafeguard\Rules\AbstractSafeguardRule;
use Grazulex\LaravelSafeguard\SafeguardResult;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Throwable;

class ComposerPackageSecurity extends AbstractSafeguardRule
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

    /**
     * Map a `composer audit --format=json` payload to issue entries.
     * Public on purpose so the mapping can be unit-tested without shelling out.
     *
     * @param  array<string, mixed>  $audit
     * @return list<array<string, mixed>>
     */
    public function mapAdvisoriesToIssues(array $audit): array
    {
        $issues = [];

        foreach ($audit['packages'] ?? [] as $package) {
            if (! is_array($package) || ! isset($package['name'])) {
                continue;
            }

            foreach ($package['advisories'] ?? [] as $advisory) {
                if (! is_array($advisory)) {
                    continue;
                }

                $cve = $advisory['cve'] ?? null;

                $issues[] = [
                    'type' => 'security_vulnerability',
                    'severity' => $this->normalizeAdvisorySeverity($advisory['severity'] ?? null),
                    'package' => (string) $package['name'],
                    'version' => (string) ($package['version'] ?? 'unknown'),
                    'vulnerability' => (string) ($advisory['title'] ?? $advisory['advisoryId'] ?? 'Security advisory'),
                    'description' => is_string($cve) && $cve !== ''
                        ? 'Advisory '.$cve.' affects the installed version'
                        : 'Composer security advisory affects the installed version',
                    'cve' => $cve,
                    'link' => $advisory['link'] ?? null,
                    'affected_versions' => $advisory['affectedVersions'] ?? null,
                    'fixed_in' => is_string($latest = $package['latest'] ?? null) && $latest !== ''
                        ? $latest
                        : null,
                ];
            }
        }

        return $issues;
    }

    /**
     * Run `composer audit --format=json` in the project root and return the
     * decoded payload. Null when Composer is unavailable or the audit cannot
     * run - the rule then skips the live advisory scan rather than guessing.
     *
     * @return array<string, mixed>|null
     */
    protected function runComposerAudit(): ?array
    {
        try {
            $result = Process::timeout(120)->path(base_path())->run('composer audit --format=json --no-interaction');
        } catch (Throwable $e) {
            return null;
        }

        // 0 = clean, 1 = advisories found (payload still valid JSON);
        // anything else means Composer itself could not run the audit.
        if (! in_array($result->exitCode(), [0, 1], true)) {
            return null;
        }

        $payload = json_decode($result->output(), true);

        return is_array($payload) ? $payload : null;
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
        $audit = $this->runComposerAudit();

        if ($audit === null) {
            // Composer unavailable: skip the live advisory scan honestly
            // instead of falling back to a hardcoded example dataset (#36).
            $packageAudit['advisory_scan'] = 'skipped (composer not available)';
            $recommendations[] = 'Install Composer and keep it on PATH so this rule can run a live security advisory audit';

            return;
        }

        $advisoryIssues = $this->mapAdvisoriesToIssues($audit);

        foreach ($advisoryIssues as $issue) {
            $issues[] = $issue;

            $packageAudit[$issue['package']]['vulnerabilities'][] = [
                'title' => $issue['vulnerability'],
                'cve' => $issue['cve'],
                'severity' => $issue['severity'],
                'link' => $issue['link'],
                'fixed_in' => $issue['fixed_in'],
            ];
        }

        if ($advisoryIssues !== []) {
            $recommendations[] = 'Update vulnerable packages to their latest secure versions (composer update)';
        }
    }

    private function normalizeAdvisorySeverity(?string $severity): string
    {
        return in_array($severity, ['critical', 'high', 'medium', 'low'], true) ? $severity : 'high';
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

        if (array_filter($issues, fn (array $issue): bool => $issue['type'] === 'very_outdated_package') !== []) {
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

        if (array_filter($issues, fn (array $issue): bool => $issue['type'] === 'abandoned_package') !== []) {
            $recommendations[] = 'Replace abandoned packages with maintained alternatives';
            $recommendations[] = 'Fork abandoned packages if no alternatives exist and they are critical';
        }
    }

    private function checkDevPackagesInProduction(array $packages, array &$issues, array &$recommendations, array &$packageAudit): void
    {
        if (app()->environment('production')) {
            $devPackages = array_filter($packages, fn (array $package): bool => $package['type'] === 'development');

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

    private function getTimeSinceLastUpdate(?string $updateTime): int
    {
        if (in_array($updateTime, [null, '', '0'], true)) {
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
