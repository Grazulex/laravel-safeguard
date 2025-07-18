<?php

declare(strict_types=1);

use Grazulex\LaravelSafeguard\Rules\Database\DatabaseQueryLogging;

beforeEach(function () {
    $this->rule = new DatabaseQueryLogging();
});

it('returns the correct id', function () {
    expect($this->rule->id())->toBe('database-query-logging');
});

it('returns the correct description', function () {
    expect($this->rule->description())->toBe('Verifies that database query logging is appropriately configured for security');
});

it('returns correct severity', function () {
    expect($this->rule->severity())->toBe('warning');
});

it('applies to all environments', function () {
    expect($this->rule->appliesToEnvironment('local'))->toBeTrue();
    expect($this->rule->appliesToEnvironment('testing'))->toBeTrue();
    expect($this->rule->appliesToEnvironment('production'))->toBeTrue();
});

it('passes in production with proper logging configuration', function () {
    app()->detectEnvironment(fn () => 'production');

    config([
        'database.connections.mysql.options' => [
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET slow_query_log = 1',
        ],
    ]);

    $result = $this->rule->check();

    expect($result->passed())->toBeTrue();
});

it('fails when debugbar is enabled in production', function () {
    app()->detectEnvironment(fn () => 'production');

    config([
        'debugbar.enabled' => true,
    ]);

    $result = $this->rule->check();

    expect($result->passed())->toBeFalse();
    expect($result->message())->toContain('Database query logging configuration has security implications');
    expect($result->details()['issues'][0]['type'])->toBe('debugbar_enabled');
});

it('fails when telescope is enabled in production', function () {
    app()->detectEnvironment(fn () => 'production');

    config([
        'telescope.enabled' => true,
    ]);

    $result = $this->rule->check();

    expect($result->passed())->toBeFalse();
    expect($result->details()['issues'][0]['type'])->toBe('telescope_enabled');
});

it('passes in production with no debugging tools enabled', function () {
    app()->detectEnvironment(fn () => 'production');

    config([
        'debugbar.enabled' => false,
        'telescope.enabled' => false,
        'database.log' => false,
    ]);

    $result = $this->rule->check();

    expect($result->passed())->toBeTrue();
});

it('warns about debugging tools in local environment', function () {
    app()->detectEnvironment(fn () => 'local');

    config([
        'debugbar.enabled' => true,
        'telescope.enabled' => true,
    ]);

    $result = $this->rule->check();

    expect($result->passed())->toBeFalse();
    expect($result->severity())->toBe('warning');
    expect($result->details()['issues'])->toHaveCount(2);
    expect($result->details()['issues'][0]['severity'])->toBe('warning');
    expect($result->details()['issues'][1]['severity'])->toBe('info');
});

it('passes in local environment with no debugging tools', function () {
    app()->detectEnvironment(fn () => 'local');

    config([
        'debugbar.enabled' => false,
        'telescope.enabled' => false,
        'database.log' => false,
    ]);

    $result = $this->rule->check();

    expect($result->passed())->toBeTrue();
});

it('detects multiple issues in production', function () {
    app()->detectEnvironment(fn () => 'production');

    config([
        'debugbar.enabled' => true,
        'telescope.enabled' => true,
    ]);

    $result = $this->rule->check();

    expect($result->passed())->toBeFalse();
    expect($result->details()['issues'])->toHaveCount(2);
    expect(collect($result->details()['issues'])->pluck('type'))
        ->toContain('debugbar_enabled')
        ->toContain('telescope_enabled');
});
