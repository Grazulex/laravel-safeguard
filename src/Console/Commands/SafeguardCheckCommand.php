<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard\Console\Commands;

use Grazulex\LaravelSafeguard\SafeguardResult;
use Grazulex\LaravelSafeguard\Services\SafeguardManager;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SafeguardCheckCommand extends Command
{
    protected $signature = 'safeguard:check
                            {--env= : Specific environment to check rules for}
                            {--format=cli : Output format (cli, json)}
                            {--fail-on-error : Exit with error code if any rule fails}
                            {--ci : CI-friendly output (no colors, compact)}
                            {--env-rules : Use environment-specific rules only}
                            {--details : Show detailed information for failed checks}
                            {--show-all : Show detailed information for all checks}';

    protected $description = 'Run Laravel Safeguard security checks. Use --details to show additional information for failed checks.';

    public function handle(SafeguardManager $manager): int
    {
        $environment = $this->option('env') ?: app()->environment();
        $format = $this->option('format');
        $ciMode = $this->option('ci');
        $useEnvRules = $this->option('env-rules');

        if (! $ciMode) {
            $this->showHeader();
        }

        $results = $useEnvRules
            ? $manager->runChecksForEnvironment($environment)
            : $manager->runChecks($environment);

        if ($format === 'json') {
            return $this->outputJson($results);
        }

        return $this->outputCli($results, $ciMode);
    }

    private function showHeader(): void
    {
        $this->line('');
        $this->info('🔐 Laravel Safeguard Security Check');
        $this->info('═══════════════════════════════════════');
        $this->line('');
        $this->comment('Environment: '.app()->environment());
        $this->line('');
    }

    private function outputCli(Collection $results, bool $ciMode): int
    {
        $passed = 0;
        $failed = 0;
        $warnings = 0;
        $showDetails = $this->option('details');
        $showAll = $this->option('show-all');

        foreach ($results as $check) {
            $result = $check['result'];
            $icon = $this->getStatusIcon($result->passed(), $result->severity());

            if (! $ciMode) {
                $this->line($icon.' '.$result->message());

                // Show details if requested
                if (($showDetails && ! $result->passed()) || $showAll) {
                    $this->showResultDetails($result);
                }
            } else {
                $status = $result->passed() ? 'PASS' : 'FAIL';
                $this->line("[{$status}] {$check['rule']}: {$result->message()}");
            }

            if ($result->passed()) {
                $passed++;
            } elseif ($result->severity() === 'warning') {
                $warnings++;
            } else {
                $failed++;
            }
        }

        if (! $ciMode) {
            $this->showSummary($passed, $failed, $warnings);
        }

        if ($this->option('fail-on-error') && $failed > 0) {
            return 1;
        }

        return 0;
    }

    private function outputJson(Collection $results): int
    {
        $passed = $results->filter(fn ($check) => $check['result']->passed())->count();
        $failed = $results->filter(fn ($check): bool => ! $check['result']->passed())->count();

        $output = [
            'status' => $failed > 0 ? 'failed' : 'passed',
            'environment' => app()->environment(),
            'summary' => [
                'total' => $results->count(),
                'passed' => $passed,
                'failed' => $failed,
            ],
            'results' => $results->map(function (array $check): array {
                return [
                    'rule' => $check['rule'],
                    'description' => $check['description'],
                    'status' => $check['result']->passed() ? 'passed' : 'failed',
                    'message' => $check['result']->message(),
                    'severity' => $check['result']->severity(),
                    'details' => $check['result']->details(),
                ];
            })->values()->all(),
        ];

        $this->line(json_encode($output, JSON_PRETTY_PRINT));

        return $failed > 0 && $this->option('fail-on-error') ? 1 : 0;
    }

    private function getStatusIcon(bool $passed, string $severity): string
    {
        if ($passed) {
            return '<fg=green>✅</>';
        }

        return match ($severity) {
            'critical' => '<fg=red>🚨</>',
            'error' => '<fg=red>❌</>',
            'warning' => '<fg=yellow>⚠️</>',
            default => '<fg=red>❌</>',
        };
    }

    private function showSummary(int $passed, int $failed, int $warnings): void
    {
        $this->line('');
        $this->info('═══════════════════════════════════════');

        if ($failed === 0 && $warnings === 0) {
            $this->info("🎯 All checks passed! ({$passed} checks)");
        } else {
            $issues = $failed + $warnings;
            $this->comment("🎯 {$issues} issues found, {$passed} checks passed");
        }
    }

    private function showResultDetails(SafeguardResult $result): void
    {
        $details = $result->details();

        if ($details === []) {
            return;
        }

        // Format special keys with better labels
        $formatMap = [
            'current_setting' => 'Current Setting',
            'recommendation' => 'Recommendation',
            'security_impact' => 'Security Impact',
            'issues' => 'Issues Found',
            'recommendations' => 'Recommendations',
            'vulnerable_packages' => 'Vulnerable Packages',
            'outdated_packages' => 'Outdated Packages',
            'abandoned_packages' => 'Abandoned Packages',
            'file_path' => 'File Path',
            'current_permissions' => 'Current Permissions',
            'recommended_permissions' => 'Recommended Permissions',
            'detected_secrets' => 'Detected Secrets',
            'csrf_status' => 'CSRF Status',
            'packages_analyzed' => 'Packages Analyzed',
        ];

        foreach ($details as $key => $value) {
            $label = $formatMap[$key] ?? ucwords(str_replace('_', ' ', $key));

            if (is_array($value)) {
                $this->comment("   📋 {$label}:");
                foreach ($value as $item) {
                    if (is_array($item)) {
                        $this->line('     • '.json_encode($item));
                    } else {
                        $this->line("     • {$item}");
                    }
                }
            } else {
                $icon = match ($key) {
                    'recommendation' => '💡',
                    'security_impact' => '⚠️',
                    'current_setting' => '⚙️',
                    'file_path' => '📁',
                    default => '📌'
                };
                $this->comment("   {$icon} {$label}: {$value}");
            }
        }

        $this->line('');
    }
}
