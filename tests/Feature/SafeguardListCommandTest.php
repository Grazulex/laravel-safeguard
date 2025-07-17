<?php

declare(strict_types=1);

use Grazulex\LaravelSafeguard\Console\Commands\SafeguardListCommand;
use Grazulex\LaravelSafeguard\Services\SafeguardManager;

beforeEach(function () {
    $this->manager = app(SafeguardManager::class);
});

it('displays all available rules', function () {
    $this->artisan(SafeguardListCommand::class)
        ->expectsOutputToContain('app-key-is-set')
        ->expectsOutputToContain('no-secrets-in-code')
        ->expectsOutputToContain('csrf-enabled')
        ->expectsOutputToContain('env-file-permissions')
        ->expectsOutputToContain('app-debug-false-in-production')
        ->assertExitCode(0);
});

it('filters rules by environment', function () {
    $this->artisan(SafeguardListCommand::class, ['--environment' => 'production'])
        ->expectsOutput('Available Safeguard Rules (production environment):')
        ->assertExitCode(0);
});

it('filters rules by severity', function () {
    $this->artisan(SafeguardListCommand::class, ['--severity' => 'error'])
        ->expectsOutput('Available Safeguard Rules (error severity):')
        ->assertExitCode(0);
});
