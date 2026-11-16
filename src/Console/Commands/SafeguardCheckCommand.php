<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard\Console\Commands;

use Grazulex\LaravelSafeguard\Formatters\AbstractFormatter;
use Grazulex\LaravelSafeguard\Formatters\FormatterInterface;
use Grazulex\LaravelSafeguard\Formatters\HtmlFormatter;
use Grazulex\LaravelSafeguard\Formatters\JsonFormatter;
use Grazulex\LaravelSafeguard\Formatters\JunitFormatter;
use Grazulex\LaravelSafeguard\SafeguardResult;
use Grazulex\LaravelSafeguard\Services\SafeguardManager;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use JsonException;
use Symfony\Component\Console\Output\OutputInterface;

class SafeguardCheckCommand extends Command
{
    protected $signature = 'safeguard:check
                            {--env= : Specific environment to check rules for}
                            {--format=cli : Output format (cli, json, junit, html)}
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

        $results = $useEnvRules
            ? $manager->runChecksForEnvironment($environment)
            : $manager->runChecks($environment);

        $formatter = match ($format) {
            'json' => new JsonFormatter,
            'junit' => new JunitFormatter,
            'html' => new HtmlFormatter,
            default => null,
        };

        if ($formatter instanceof FormatterInterface) {
            return $this->outputFormatted($formatter, $results);
        }

        if (! $ciMode) {
            $this->showHeader();
        }

        return $this->outputCli($results, $ciMode);
    }

    private function showHeader(): void
    {
        // Force l'output même dans des contextes programmatiques
        $this->output->writeln('🔐 Laravel Safeguard Security Check', OutputInterface::VERBOSITY_NORMAL);
        $this->output->writeln('═══════════════════════════════════════', OutputInterface::VERBOSITY_NORMAL);
        $this->output->writeln('', OutputInterface::VERBOSITY_NORMAL);
        $this->output->writeln('<comment>Environment: '.app()->environment().'</comment>', OutputInterface::VERBOSITY_NORMAL);
        $this->output->writeln('', OutputInterface::VERBOSITY_NORMAL);
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
                // Force l'output avec writeIn pour assurer la visibilité
                $this->output->writeln($icon.' '.$result->message(), OutputInterface::VERBOSITY_NORMAL);

                // Show details if requested
                if (($showDetails && ! $result->passed()) || $showAll) {
                    $this->showResultDetails($result);
                }
            } else {
                $status = $result->passed() ? 'PASS' : 'FAIL';
                $this->output->writeln("[{$status}] {$check['rule']}: {$result->message()}", OutputInterface::VERBOSITY_NORMAL);
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

    /**
     * Render the results with a machine-readable formatter (json, junit, html).
     * Exits with 1 when errors/critical issues are found; warnings alone do not fail.
     */
    private function outputFormatted(FormatterInterface $formatter, Collection $results): int
    {
        try {
            $content = $formatter->format($results, app()->environment());
        } catch (JsonException $e) {
            $this->output->writeln('<error>Failed to encode JSON output: '.$e->getMessage().'</error>', OutputInterface::VERBOSITY_NORMAL);

            return 1;
        }

        // Raw output: the content must not be interpreted as console style tags
        $this->output->writeln(mb_rtrim($content, "\n"), OutputInterface::OUTPUT_RAW);

        return AbstractFormatter::summarize($results)['errors'] > 0 ? 1 : 0;
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
        $this->output->writeln('', OutputInterface::VERBOSITY_NORMAL);
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
                'recommendation' => '💡',
                'security_impact' => '⚠️',
                'current_setting' => '⚙️',
                default => '📌'
            };

            if (is_array($value)) {
                $this->output->writeln("<comment>   📋 {$label}:</comment>", OutputInterface::VERBOSITY_NORMAL);
                foreach ($value as $item) {
                    if (is_string($item)) {
                        $this->output->writeln("     • {$item}", OutputInterface::VERBOSITY_NORMAL);
                    }
                }
            } else {
                $this->output->writeln("   {$icon} {$label}: {$value}", OutputInterface::VERBOSITY_NORMAL);
            }
        }
    }
}
