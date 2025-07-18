<?php

declare(strict_types=1);

use Grazulex\LaravelSafeguard\Console\Commands\SafeguardCheckCommand;
use Grazulex\LaravelSafeguard\Services\SafeguardManager;

beforeEach(function () {
    $this->manager = app(SafeguardManager::class);
});

it('runs basic security check', function () {
    $this->artisan(SafeguardCheckCommand::class)
        ->expectsOutputToContain('Laravel Safeguard Security Check')
        ->expectsOutputToContain('Environment: testing')
        ->assertExitCode(0);
});

it('shows details for failed checks when --details option is used', function () {
    $this->artisan(SafeguardCheckCommand::class, ['--details' => true])
        ->expectsOutputToContain('Laravel Safeguard Security Check')
        ->expectsOutputToContain('Environment: testing')
        ->assertExitCode(0);
});

it('shows details for all checks when --show-all option is used', function () {
    $this->artisan(SafeguardCheckCommand::class, ['--show-all' => true])
        ->expectsOutputToContain('Laravel Safeguard Security Check')
        ->expectsOutputToContain('Environment: testing')
        ->assertExitCode(0);
});

it('outputs CI mode when requested', function () {
    $this->artisan(SafeguardCheckCommand::class, ['--ci' => true])
        ->expectsOutputToContain('[PASS]') // Le mode CI utilise un format différent
        ->assertExitCode(1); // Exit code 1 car il y a des erreurs détectées dans l'environnement de test
});

it('shows detailed output when issues found with details flag', function () {
    $this->artisan(SafeguardCheckCommand::class, ['--details' => true])
        ->expectsOutputToContain('Laravel Safeguard Security Check')
        ->assertExitCode(0);
});

it('fails when --fail-on-error is used and errors exist', function () {
    $this->artisan(SafeguardCheckCommand::class, ['--fail-on-error' => true])
        ->assertExitCode(1);
});

it('uses CI mode output format', function () {
    $this->artisan(SafeguardCheckCommand::class, ['--ci' => true])
        ->expectsOutputToContain('[PASS]')
        ->assertExitCode(1); // Exit code 1 car il y a des erreurs détectées dans l'environnement de test
});

it('runs environment-specific rules only', function () {
    $this->artisan(SafeguardCheckCommand::class, ['--env-rules' => true])
        ->expectsOutputToContain('Laravel Safeguard Security Check')
        ->assertExitCode(0);
});
