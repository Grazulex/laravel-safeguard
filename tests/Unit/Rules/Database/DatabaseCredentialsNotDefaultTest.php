<?php

declare(strict_types=1);

use Grazulex\LaravelSafeguard\Rules\Database\DatabaseCredentialsNotDefault;

beforeEach(function () {
    $this->rule = new DatabaseCredentialsNotDefault();
});

it('returns the correct id', function () {
    expect($this->rule->id())->toBe('database-credentials-not-default');
});

it('returns the correct description', function () {
    expect($this->rule->description())->toBe('Detects default or weak database credentials that pose security risks');
});

it('returns correct severity', function () {
    expect($this->rule->severity())->toBe('critical');
});

it('applies to all environments', function () {
    expect($this->rule->appliesToEnvironment('local'))->toBeTrue();
    expect($this->rule->appliesToEnvironment('testing'))->toBeTrue();
    expect($this->rule->appliesToEnvironment('production'))->toBeTrue();
});

it('passes when using strong credentials', function () {
    // Clear all existing database connections first
    config(['database.connections' => []]);

    config([
        'database.connections.mysql' => [
            'driver' => 'mysql',
            'username' => 'app_user_2024',
            'password' => 'aB3!xYz9#Qm8$Lk7%Rt5^Wp2&Hd6@Fj4',
        ],
    ]);

    $result = $this->rule->check();

    expect($result->passed())->toBeTrue();
});

it('fails when using default root credentials', function () {
    config([
        'database.connections.mysql' => [
            'driver' => 'mysql',
            'username' => 'root',
            'password' => '',
        ],
    ]);

    $result = $this->rule->check();

    expect($result->passed())->toBeFalse();
    expect($result->message())->toContain('Vulnerable database credentials');
    expect($result->severity())->toBe('critical');
});

it('fails when using weak passwords', function () {
    config([
        'database.connections.mysql' => [
            'driver' => 'mysql',
            'username' => 'app_user',
            'password' => 'password',
        ],
    ]);

    $result = $this->rule->check();

    expect($result->passed())->toBeFalse();
    expect($result->details()['issues'][0]['type'])->toBe('weak_password');
});

it('warns about short passwords', function () {
    config([
        'database.connections.mysql' => [
            'driver' => 'mysql',
            'username' => 'app_user',
            'password' => 'short123',
        ],
    ]);

    $result = $this->rule->check();

    expect($result->passed())->toBeFalse();
    expect($result->details()['issues'][0]['type'])->toBe('short_password');
});

it('detects multiple vulnerability types', function () {
    config([
        'database.connections' => [
            'mysql1' => [
                'driver' => 'mysql',
                'username' => 'root',
                'password' => '',
            ],
            'mysql2' => [
                'driver' => 'mysql',
                'username' => 'app',
                'password' => 'password',
            ],
        ],
    ]);

    $result = $this->rule->check();

    expect($result->passed())->toBeFalse();
    expect($result->details()['issues'])->toHaveCount(2);
    expect($result->details()['vulnerable_connections'])->toContain('mysql1');
    expect($result->details()['vulnerable_connections'])->toContain('mysql2');
});
