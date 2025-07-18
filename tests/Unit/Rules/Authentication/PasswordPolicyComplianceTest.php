<?php

declare(strict_types=1);

use Grazulex\LaravelSafeguard\Rules\Authentication\PasswordPolicyCompliance;

beforeEach(function () {
    $this->rule = new PasswordPolicyCompliance();
});

it('returns the correct id', function () {
    expect($this->rule->id())->toBe('password-policy-compliance');
});

it('returns the correct description', function () {
    expect($this->rule->description())->toBe('Verifies that password policy configuration meets security standards');
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
});

it('detects missing password validation rules by default', function () {
    // Default Laravel configuration usually lacks proper password validation
    $result = $this->rule->check();

    expect($result->passed())->toBeFalse();
    expect($result->details()['issues'])->toBeArray();
    expect($result->details()['issues'][0]['type'])->toBe('no_minimum_length');
});

it('has proper rule interface implementation', function () {
    expect($this->rule)->toBeInstanceOf(Grazulex\LaravelSafeguard\Contracts\SafeguardRule::class);
    expect($this->rule->id())->toBeString();
    expect($this->rule->description())->toBeString();
    expect($this->rule->severity())->toBeString();
});
