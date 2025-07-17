<?php

declare(strict_types=1);

namespace Grazulex\LaravelSafeguard;

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

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'safeguard-migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\MakeStateMachineCommand::class,
                Console\Commands\GenerateCommand::class,
                Console\Commands\ListCommand::class,
                Console\Commands\ShowCommand::class,
                Console\Commands\ExportCommand::class,
                Console\Commands\ValidateCommand::class,
            ]);
        }
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/Config/safeguard.php', 'safeguard');
    }
}
