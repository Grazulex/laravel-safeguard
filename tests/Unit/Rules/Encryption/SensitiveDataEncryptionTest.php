<?php

declare(strict_types=1);

use Grazulex\LaravelSafeguard\Rules\Encryption\SensitiveDataEncryption;

beforeEach(function () {
    $this->rule = new SensitiveDataEncryption();
});

it('returns the correct id', function () {
    expect($this->rule->id())->toBe('sensitive-data-encryption');
});

it('returns the correct description', function () {
    expect($this->rule->description())->toBe('Scans models for sensitive fields that should be encrypted');
});

it('returns correct severity', function () {
    expect($this->rule->severity())->toBe('error');
});

it('applies to all environments', function () {
    expect($this->rule->appliesToEnvironment('local'))->toBeTrue();
    expect($this->rule->appliesToEnvironment('testing'))->toBeTrue();
    expect($this->rule->appliesToEnvironment('production'))->toBeTrue();
});

it('can execute check method without errors', function () {
    $result = $this->rule->check();

    expect($result)->toBeInstanceOf(Grazulex\LaravelSafeguard\SafeguardResult::class);
    expect($result->details())->toBeArray();
    // The result may pass or fail depending on models found in the test environment
});

it('has proper rule interface implementation', function () {
    expect($this->rule)->toBeInstanceOf(Grazulex\LaravelSafeguard\Contracts\SafeguardRule::class);
    expect($this->rule->id())->toBeString();
    expect($this->rule->description())->toBeString();
    expect($this->rule->severity())->toBeString();
});
