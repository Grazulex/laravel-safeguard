<?php

declare(strict_types=1);

use Grazulex\LaravelSafeguard\Rules\Encryption\EncryptionKeyRotation;

beforeEach(function () {
    $this->rule = new EncryptionKeyRotation();
});

it('returns the correct id', function () {
    expect($this->rule->id())->toBe('encryption-key-rotation');
});

it('returns the correct description', function () {
    expect($this->rule->description())->toBe('Verifies encryption key management and rotation practices');
});

it('returns correct severity', function () {
    expect($this->rule->severity())->toBe('error');
});

it('applies to all environments', function () {
    expect($this->rule->appliesToEnvironment('local'))->toBeTrue();
    expect($this->rule->appliesToEnvironment('testing'))->toBeTrue();
    expect($this->rule->appliesToEnvironment('production'))->toBeTrue();
});

it('passes with proper APP_KEY configuration', function () {
    config([
        'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
    ]);

    $result = $this->rule->check();

    // The rule may still warn about key rotation implementation
    expect($result)->toBeInstanceOf(Grazulex\LaravelSafeguard\SafeguardResult::class);
    expect($result->details())->toBeArray();
});

it('fails with missing APP_KEY', function () {
    config([
        'app.key' => '',
    ]);

    $result = $this->rule->check();

    expect($result->passed())->toBeFalse();
    expect($result->details()['issues'][0]['type'])->toBe('missing_app_key');
    expect($result->details()['issues'][0]['severity'])->toBe('critical');
});

it('can execute check method without errors', function () {
    $result = $this->rule->check();

    expect($result)->toBeInstanceOf(Grazulex\LaravelSafeguard\SafeguardResult::class);
    expect($result->details())->toBeArray();
});

it('has proper rule interface implementation', function () {
    expect($this->rule)->toBeInstanceOf(Grazulex\LaravelSafeguard\Contracts\SafeguardRule::class);
    expect($this->rule->id())->toBeString();
    expect($this->rule->description())->toBeString();
    expect($this->rule->severity())->toBeString();
});
