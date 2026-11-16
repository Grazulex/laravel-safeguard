<?php

declare(strict_types=1);

use Grazulex\LaravelSafeguard\Console\Commands\SafeguardCheckCommand;
use Grazulex\LaravelSafeguard\Formatters\AbstractFormatter;
use Grazulex\LaravelSafeguard\Services\SafeguardManager;
use Illuminate\Support\Facades\Artisan;

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

it('outputs JSON when --format=json is used', function () {
    $exitCode = Artisan::call('safeguard:check', ['--format' => 'json']);
    $decoded = json_decode(Artisan::output(), true);
    $summary = AbstractFormatter::summarize($this->manager->runChecks());

    expect($decoded)->toBeArray()
        ->and($decoded['environment'])->toBe('testing')
        ->and($decoded['summary']['total'])->toBe($summary['total'])
        ->and($exitCode)->toBe($summary['errors'] > 0 ? 1 : 0);
});

it('outputs JUnit XML when --format=junit is used', function () {
    $exitCode = Artisan::call('safeguard:check', ['--format' => 'junit']);
    $output = Artisan::output();
    $summary = AbstractFormatter::summarize($this->manager->runChecks());

    expect($output)->toStartWith('<?xml version="1.0" encoding="UTF-8"?>')
        ->and($output)->not->toContain('Laravel Safeguard Security Check');

    $document = simplexml_load_string($output);

    expect($document)->not->toBeFalse()
        ->and((string) $document['tests'])->toBe((string) $summary['total'])
        ->and((string) $document['failures'])->toBe((string) $summary['errors'])
        ->and((string) $document->testsuite['name'])->toBe('Laravel Safeguard (testing)')
        ->and($exitCode)->toBe($summary['errors'] > 0 ? 1 : 0);
});

it('outputs an HTML report when --format=html is used', function () {
    $exitCode = Artisan::call('safeguard:check', ['--format' => 'html']);
    $output = Artisan::output();
    $summary = AbstractFormatter::summarize($this->manager->runChecks());

    expect($output)->toStartWith('<!DOCTYPE html>')
        ->and($output)->toContain('Laravel Safeguard Security Report')
        ->and($output)->toContain('Environment: <strong>testing</strong>')
        ->and($output)->toContain('</html>')
        ->and($exitCode)->toBe($summary['errors'] > 0 ? 1 : 0);
});

it('falls back to CLI output for unknown formats', function () {
    $this->artisan(SafeguardCheckCommand::class, ['--format' => 'unknown'])
        ->expectsOutputToContain('Laravel Safeguard Security Check')
        ->assertExitCode(0);
});

it('runs environment-specific rules only', function () {
    $this->artisan(SafeguardCheckCommand::class, ['--env-rules' => true])
        ->expectsOutputToContain('Laravel Safeguard Security Check')
        ->assertExitCode(0);
});
