<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard;

use Grazulex\LaravelSafeguard\Console\Commands\MakeRuleCommand;
use Grazulex\LaravelSafeguard\Console\Commands\SafeguardCheckCommand;
use Grazulex\LaravelSafeguard\Console\Commands\SafeguardListCommand;
use Grazulex\LaravelSafeguard\Rules\Configuration\AppKeyIsSet;
use Grazulex\LaravelSafeguard\Rules\Environment\AppDebugFalseInProduction;
use Grazulex\LaravelSafeguard\Rules\Environment\EnvHasAllRequiredKeys;
use Grazulex\LaravelSafeguard\Rules\FileSystem\EnvFilePermissions;
use Grazulex\LaravelSafeguard\Rules\Security\CsrfEnabled;
use Grazulex\LaravelSafeguard\Rules\Security\NoSecretsInCode;
use Grazulex\LaravelSafeguard\Services\SafeguardManager;
use Illuminate\Support\ServiceProvider;

final class LaravelSafeguardServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/Config/safeguard.php' => config_path('safeguard.php'),
        ], 'safeguard-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                SafeguardCheckCommand::class,
                SafeguardListCommand::class,
                MakeRuleCommand::class,
            ]);
        }

        $this->registerDefaultRules();
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/Config/safeguard.php', 'safeguard');

        $this->app->singleton(SafeguardManager::class, function (): \Grazulex\LaravelSafeguard\Services\SafeguardManager {
            return new SafeguardManager();
        });
    }

    /**
     * Register the default security rules.
     */
    private function registerDefaultRules(): void
    {
        $manager = $this->app->make(SafeguardManager::class);

        // Environment Rules
        $manager->registerRule(new AppDebugFalseInProduction());
        $manager->registerRule(new EnvHasAllRequiredKeys());

        // Configuration Rules
        $manager->registerRule(new AppKeyIsSet());

        // Security Rules
        $manager->registerRule(new NoSecretsInCode());
        $manager->registerRule(new CsrfEnabled());

        // File System Rules
        $manager->registerRule(new EnvFilePermissions());

        // Load custom rules from the configured path
        $manager->loadCustomRules();
    }
}
