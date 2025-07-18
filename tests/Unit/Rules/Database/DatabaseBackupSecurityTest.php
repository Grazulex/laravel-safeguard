<?php

declare(strict_types=1);

use Grazulex\LaravelSafeguard\Rules\Database\DatabaseBackupSecurity;

beforeEach(function () {
    $this->rule = new DatabaseBackupSecurity();
});

it('returns the correct id', function () {
    expect($this->rule->id())->toBe('database-backup-security');
});

it('returns the correct description', function () {
    expect($this->rule->description())->toBe('Verifies that database backup configurations are secure');
});

it('returns correct severity', function () {
    expect($this->rule->severity())->toBe('error');
});

it('applies to all environments', function () {
    expect($this->rule->appliesToEnvironment('production'))->toBeTrue();
    expect($this->rule->appliesToEnvironment('staging'))->toBeTrue();
    expect($this->rule->appliesToEnvironment('local'))->toBeTrue();
    expect($this->rule->appliesToEnvironment('testing'))->toBeTrue();
});

it('passes when no backup configuration exists', function () {
    $result = $this->rule->check();

    expect($result->passed())->toBeTrue();
});
