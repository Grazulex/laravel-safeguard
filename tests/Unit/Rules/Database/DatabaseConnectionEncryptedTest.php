<?php

declare(strict_types=1);

use Grazulex\LaravelSafeguard\Rules\Database\DatabaseConnectionEncrypted;

beforeEach(function () {
    $this->rule = new DatabaseConnectionEncrypted();
});

it('returns the correct id', function () {
    expect($this->rule->id())->toBe('database-connection-encrypted');
});

it('returns the correct description', function () {
    expect($this->rule->description())->toBe('Verifies that database connections use SSL/TLS encryption');
});

it('returns correct severity', function () {
    expect($this->rule->severity())->toBe('critical');
});

it('applies to all environments', function () {
    expect($this->rule->appliesToEnvironment('local'))->toBeTrue();
    expect($this->rule->appliesToEnvironment('testing'))->toBeTrue();
    expect($this->rule->appliesToEnvironment('production'))->toBeTrue();
});

it('passes when mysql connection has SSL configured', function () {
    // Clear all existing database connections first
    config(['database.connections' => []]);

    config([
        'database.connections.mysql' => [
            'driver' => 'mysql',
            'options' => [
                PDO::MYSQL_ATTR_SSL_CA => '/path/to/ca.pem',
            ],
        ],
    ]);

    $result = $this->rule->check();

    expect($result->passed())->toBeTrue();
    expect($result->message())->toContain('properly encrypted');
});

it('fails when mysql connection has no SSL', function () {
    config([
        'database.connections.mysql' => [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'test',
        ],
    ]);

    $result = $this->rule->check();

    expect($result->passed())->toBeFalse();
    expect($result->message())->toContain('without proper encryption');
    expect($result->severity())->toBe('critical');
});

it('passes when postgres connection has SSL configured', function () {
    // Clear all existing database connections first
    config(['database.connections' => []]);

    config([
        'database.connections.pgsql' => [
            'driver' => 'pgsql',
            'host' => 'localhost',
            'sslmode' => 'require',
        ],
    ]);

    $result = $this->rule->check();

    expect($result->passed())->toBeTrue();
});

it('fails when postgres connection has no SSL', function () {
    config([
        'database.connections.pgsql' => [
            'driver' => 'pgsql',
            'host' => 'localhost',
            'database' => 'test',
        ],
    ]);

    $result = $this->rule->check();

    expect($result->passed())->toBeFalse();
});

it('passes for sqlite connections', function () {
    // Clear all existing database connections first
    config(['database.connections' => []]);

    config([
        'database.connections.sqlite' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ],
    ]);

    $result = $this->rule->check();

    expect($result->passed())->toBeTrue();
});
