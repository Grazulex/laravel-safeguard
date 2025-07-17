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
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/Config/safeguard.php', 'safeguard');
    }
}
