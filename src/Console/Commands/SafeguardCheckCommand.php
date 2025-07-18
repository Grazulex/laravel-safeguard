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
        $errors = 0; // Erreurs/critiques seulement
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
                // Erreurs, critiques, etc.
                $errors++;
            }
        }

        if (! $ciMode) {
            $this->showSummary($passed, $errors, $warnings);
        }

        // En mode CI ou avec --fail-on-error, retourner un code d'erreur si des erreurs/critiques sont détectées
        // Ne pas échouer pour les warnings seulement
        if (($ciMode || $this->option('fail-on-error')) && $errors > 0) {
            return 1;
        }

        return 0;
    }

    private function outputJson(Collection $results): int
    {
        $passed = $results->filter(fn ($check) => $check['result']->passed())->count();
        $errors = $results->filter(fn ($check): bool => ! $check['result']->passed() && $check['result']->severity() !== 'warning')->count();
        $warnings = $results->filter(fn ($check): bool => ! $check['result']->passed() && $check['result']->severity() === 'warning')->count();

        $output = [
            'status' => $errors > 0 ? 'failed' : ($warnings > 0 ? 'warning' : 'passed'),
            'environment' => app()->environment(),
            'summary' => [
                'total' => $results->count(),
                'passed' => $passed,
                'errors' => $errors,
                'warnings' => $warnings,
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

        // Retourner un code d'erreur seulement pour les erreurs/critiques, pas pour les warnings
        return $errors > 0 ? 1 : 0;
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

    private function showSummary(int $passed, int $errors, int $warnings): void
    {
        $this->line('');
        $this->info('═══════════════════════════════════════');

        if ($errors === 0 && $warnings === 0) {
            $this->info("🎯 All checks passed! ({$passed} checks)");
        } else {
            $issues = $errors + $warnings;
            $this->comment("🎯 {$issues} issues found, {$passed} checks passed");

            if ($errors > 0 && $warnings > 0) {
                $this->comment("   ({$errors} errors/critical, {$warnings} warnings)");
            } elseif ($errors > 0) {
                $this->comment("   ({$errors} errors/critical)");
            } else {
                $this->comment("   ({$warnings} warnings)");
            }
        }
    }

    private function showResultDetails(SafeguardResult $result): void
    {
        // For now, use basic formatting. Future enhancement could integrate
        // with SafeguardManager to get rule instances for custom formatting.
        $this->showBasicResultDetails($result);
        $this->line('');
    }

    /**
     * Basic fallback formatting for rules that don't implement custom formatting.
     */
    private function showBasicResultDetails(SafeguardResult $result): void
    {
        $details = $result->details();

        if ($details === []) {
            return;
        }

        foreach ($details as $key => $value) {
            $label = ucwords(str_replace('_', ' ', $key));
            $icon = match ($key) {
                'recommendation' => '�',
                'security_impact' => '⚠️',
                'current_setting' => '⚙️',
                default => '📌'
            };

            if (is_array($value)) {
                $this->comment("   📋 {$label}:");
                foreach ($value as $item) {
                    if (is_string($item)) {
                        $this->line("     • {$item}");
                    }
                }
            } else {
                $this->comment("   {$icon} {$label}: {$value}");
            }
        }
    }
}
