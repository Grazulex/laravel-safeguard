<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard\Console\Commands;

use Grazulex\LaravelSafeguard\Services\SafeguardManager;
use Illuminate\Console\Command;

class SafeguardListCommand extends Command
{
    protected $signature = 'safeguard:list
                           {--enabled : Show only enabled rules}
                           {--disabled : Show only disabled rules}
                           {--environment= : Show rules for specific environment}
                           {--severity= : Show rules with specific severity}
                           {--env= : Show rules for specific environment (alias)}';

    protected $description = 'List all available Laravel Safeguard security rules';

    public function handle(SafeguardManager $manager): int
    {
        $this->showHeader();

        $rules = $manager->getRules();
        $config = config('safeguard.rules', []);
        $environment = $this->option('environment') ?? $this->option('env');
        $severity = $this->option('severity');

        if ($this->option('enabled')) {
            $rules = $rules->filter(fn ($rule) => $config[$rule->id()] ?? false);
        } elseif ($this->option('disabled')) {
            $rules = $rules->filter(fn ($rule): bool => ! ($config[$rule->id()] ?? false));
        }

        if ($environment) {
            $rules = $rules->filter(fn ($rule) => $rule->appliesToEnvironment($environment));
        }

        if ($severity) {
            $rules = $rules->filter(fn ($rule): bool => $rule->severity() === $severity);
        }

        if ($rules->isEmpty()) {
            $this->comment('No rules found matching your criteria.');

            return 0;
        }

        $this->displayRulesTable($rules, $config, $environment);
        $this->showSummary($rules, $config);

        return 0;
    }

    private function showHeader(): void
    {
        $title = 'Available Safeguard Rules';

        $filters = [];
        if ($env = $this->option('environment') ?? $this->option('env')) {
            $filters[] = "{$env} environment";
        }
        if ($severity = $this->option('severity')) {
            $filters[] = "{$severity} severity";
        }

        if ($filters !== []) {
            $title .= ' ('.implode(', ', $filters).')';
        }

        $this->line('');
        $this->info($title.':');
        $this->info('═══════════════════════════════════════');
        $this->line('');
    }

    private function displayRulesTable($rules, array $config, ?string $environment): void
    {
        $headers = ['Rule ID', 'Description', 'Severity', 'Status'];

        if ($environment !== null && $environment !== '' && $environment !== '0') {
            $headers[] = "Applies to {$environment}";
        }

        $rows = [];

        foreach ($rules as $rule) {
            $enabled = $config[$rule->id()] ?? false;
            $status = $enabled ? '<fg=green>✅ Enabled</>' : '<fg=red>❌ Disabled</>';

            $row = [
                $rule->id(),
                $this->truncateDescription($rule->description()),
                $this->formatSeverity($rule->severity()),
                $status,
            ];

            if ($environment !== null && $environment !== '' && $environment !== '0') {
                $applies = $rule->appliesToEnvironment($environment);
                $row[] = $applies ? '<fg=green>Yes</>' : '<fg=yellow>No</>';
            }

            $rows[] = $row;
        }

        $this->table($headers, $rows);
    }

    private function showSummary($rules, array $config): void
    {
        $total = $rules->count();
        $enabled = $rules->filter(fn ($rule) => $config[$rule->id()] ?? false)->count();
        $disabled = $total - $enabled;

        $this->line('');
        $this->info('Summary:');
        $this->comment("  Total rules: {$total}");
        $this->comment("  Enabled: {$enabled}");
        $this->comment("  Disabled: {$disabled}");
        $this->line('');
        $this->comment('💡 To run security checks: php artisan safeguard:check');
        $this->comment('💡 To create a custom rule: php artisan safeguard:make-rule MyCustomRule');
    }

    private function truncateDescription(string $description): string
    {
        return mb_strlen($description) > 50
            ? mb_substr($description, 0, 47).'...'
            : $description;
    }

    private function formatSeverity(string $severity): string
    {
        return match ($severity) {
            'critical' => '<fg=red;options=bold>CRITICAL</>',
            'error' => '<fg=red>ERROR</>',
            'warning' => '<fg=yellow>WARNING</>',
            'info' => '<fg=blue>INFO</>',
            default => $severity,
        };
    }
}
