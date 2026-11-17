<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard\Services;

use Grazulex\LaravelSafeguard\Baseline\Baseline;
use Grazulex\LaravelSafeguard\Baseline\InvalidBaselineException;
use Grazulex\LaravelSafeguard\Contracts\SafeguardRule;
use Grazulex\LaravelSafeguard\SafeguardResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use Throwable;

class SafeguardManager
{
    private Collection $rules;

    private ?Baseline $baseline = null;

    private bool $baselineResolved = false;

    public function __construct()
    {
        $this->rules = collect();
    }

    /**
     * Register a security rule.
     */
    public function registerRule(SafeguardRule $rule): self
    {
        $this->rules->put($rule->id(), $rule);

        return $this;
    }

    /**
     * Load custom rules from the configured path.
     */
    public function loadCustomRules(): self
    {
        $customRulesPath = config('safeguard.custom_rules_path');
        $namespace = config('safeguard.custom_rules_namespace', 'App\\SafeguardRules');

        if (! $customRulesPath || ! File::exists($customRulesPath)) {
            return $this;
        }

        $files = File::allFiles($customRulesPath);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $className = $namespace.'\\'.$file->getFilenameWithoutExtension();

            try {
                if (class_exists($className)) {
                    $reflection = new ReflectionClass($className);

                    if ($reflection->implementsInterface(SafeguardRule::class) &&
                        ! $reflection->isAbstract()) {
                        $this->registerRule(new $className());
                    }
                }
            } catch (Throwable $e) {
                // Log l'erreur mais continue le chargement des autres règles
                // On pourrait utiliser un logger ici si disponible
                continue;
            }
        }

        return $this;
    }

    /**
     * Get all registered rules.
     */
    public function getRules(): Collection
    {
        return $this->rules;
    }

    /**
     * Get a specific rule by ID.
     */
    public function getRule(string $id): ?SafeguardRule
    {
        return $this->rules->get($id);
    }

    /**
     * Get enabled rules from configuration.
     */
    public function getEnabledRules(): Collection
    {
        $enabledRuleIds = collect(config('safeguard.rules', []))
            ->filter(fn ($enabled): bool => $enabled === true)
            ->keys();

        return $this->rules->filter(
            fn (SafeguardRule $rule) => $enabledRuleIds->contains($rule->id())
        );
    }

    /**
     * Get rules that apply to a specific environment.
     */
    public function getRulesForEnvironment(string $environment): Collection
    {
        $envRules = config("safeguard.environments.{$environment}", []);

        if (empty($envRules)) {
            return $this->getEnabledRules();
        }

        return $this->rules->filter(function (SafeguardRule $rule) use ($envRules, $environment): bool {
            return in_array($rule->id(), $envRules) &&
                   $rule->appliesToEnvironment($environment) &&
                   (config("safeguard.rules.{$rule->id()}") ?? false);
        });
    }

    /**
     * Use a specific baseline of accepted findings (null disables the baseline).
     */
    public function setBaseline(?Baseline $baseline): self
    {
        $this->baseline = $baseline;
        $this->baselineResolved = true;

        return $this;
    }

    /**
     * Forget the current baseline: it is loaded again from the configuration on next use.
     */
    public function resetBaseline(): self
    {
        $this->baseline = null;
        $this->baselineResolved = false;

        return $this;
    }

    /**
     * Get the baseline of accepted findings. Unless set explicitly, it is
     * loaded from the safeguard.baseline configuration on first use.
     *
     * @throws InvalidBaselineException When the configured baseline file is invalid.
     */
    public function getBaseline(): ?Baseline
    {
        if (! $this->baselineResolved) {
            $this->baseline = Baseline::fromConfig();
            $this->baselineResolved = true;
        }

        return $this->baseline;
    }

    /**
     * Run all enabled rules or rules for specific environment.
     *
     * Failed checks covered by the baseline get a "baseline" key
     * ({status: accepted|expired, reason, expires}).
     */
    public function runChecks(?string $environment = null): Collection
    {
        // Always use enabled rules unless explicitly requested environment filtering
        $rulesToRun = $this->getEnabledRules();

        return $this->applyBaseline($rulesToRun->map(function (SafeguardRule $rule): array {
            try {
                $result = $rule->check();

                return [
                    'rule' => $rule->id(),
                    'description' => $rule->description(),
                    'severity' => $rule->severity(),
                    'result' => $result,
                ];
            } catch (Throwable $e) {
                // En cas d'erreur lors de l'exécution d'une règle, créer un résultat d'échec
                return [
                    'rule' => $rule->id(),
                    'description' => $rule->description(),
                    'severity' => 'error',
                    'result' => SafeguardResult::fail(
                        "Rule execution failed: {$e->getMessage()}",
                        'error',
                        ['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]
                    ),
                ];
            }
        }));
    }

    /**
     * Run rules specific to an environment.
     */
    public function runChecksForEnvironment(string $environment): Collection
    {
        $rulesToRun = $this->getRulesForEnvironment($environment);

        return $this->applyBaseline($rulesToRun->map(function (SafeguardRule $rule): array {
            try {
                $result = $rule->check();

                return [
                    'rule' => $rule->id(),
                    'description' => $rule->description(),
                    'severity' => $rule->severity(),
                    'result' => $result,
                ];
            } catch (Throwable $e) {
                // En cas d'erreur lors de l'exécution d'une règle, créer un résultat d'échec
                return [
                    'rule' => $rule->id(),
                    'description' => $rule->description(),
                    'severity' => 'error',
                    'result' => SafeguardResult::fail(
                        "Rule execution failed: {$e->getMessage()}",
                        'error',
                        ['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]
                    ),
                ];
            }
        }));
    }

    private function applyBaseline(Collection $results): Collection
    {
        $baseline = $this->getBaseline();

        return $baseline instanceof Baseline ? $baseline->apply($results) : $results;
    }
}
