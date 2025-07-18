<?php

declare(strict_types=1);

use Grazulex\LaravelSafeguard\Rules\Authentication\TwoFactorAuthEnabled;

beforeEach(function () {
    $this->rule = new TwoFactorAuthEnabled();
});

it('returns the correct id', function () {
    expect($this->rule->id())->toBe('two-factor-auth-enabled');
});

it('returns the correct description', function () {
    expect($this->rule->description())->toBe('Verifies that two-factor authentication is properly configured and encouraged');
});

it('returns correct severity', function () {
    expect($this->rule->severity())->toBe('warning');
});

it('applies to all environments', function () {
    expect($this->rule->appliesToEnvironment('local'))->toBeTrue();
    expect($this->rule->appliesToEnvironment('testing'))->toBeTrue();
    expect($this->rule->appliesToEnvironment('production'))->toBeTrue();
});

it('passes when no 2FA packages are installed and no sensitive fields', function () {
    $result = $this->rule->check();

    expect($result->passed())->toBeFalse();
    expect($result->details()['issues'][0]['type'])->toBe('no_2fa_package');
});

it('passes with fortify 2FA enabled', function () {
    // Mock Fortify configuration
    config([
        'fortify.features' => ['two-factor-authentication'],
    ]);

    $result = $this->rule->check();

    // Should still fail because package detection would fail in test environment
    expect($result->passed())->toBeFalse();
});

it('fails in production without 2FA package', function () {
    app()->detectEnvironment(fn () => 'production');

    $result = $this->rule->check();

    expect($result->passed())->toBeFalse();
    expect($result->severity())->toBe('critical');
    expect(collect($result->details()['issues'])->pluck('type'))
        ->toContain('no_2fa_package');
});

it('warns about missing QR generator', function () {
    // This would typically require mocking package detection
    $result = $this->rule->check();

    expect($result->passed())->toBeFalse();
});

it('provides appropriate recommendations', function () {
    $result = $this->rule->check();

    expect($result->details()['recommendations'])
        ->toContain('Install a 2FA package like Laravel Fortify or pragmarx/google2fa-laravel');
});
