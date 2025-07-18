<?php

declare(strict_types=1);

use Grazulex\LaravelSafeguard\Rules\Security\ComposerPackageSecurity;

beforeEach(function () {
    $this->rule = new ComposerPackageSecurity();
});

it('returns the correct id', function () {
    expect($this->rule->id())->toBe('composer-package-security');
});

it('returns the correct description', function () {
    expect($this->rule->description())->toBe('Audits Composer packages for security vulnerabilities, outdated versions, and abandoned packages');
});

it('returns correct severity', function () {
    expect($this->rule->severity())->toBe('warning');
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

it('has proper rule interface implementation', function () {
    expect($this->rule)->toBeInstanceOf(Grazulex\LaravelSafeguard\Contracts\SafeguardRule::class);
    expect($this->rule->id())->toBeString();
    expect($this->rule->description())->toBeString();
    expect($this->rule->severity())->toBeString();
});

it('detects when composer.lock exists', function () {
    $result = $this->rule->check();

    // Should either pass (if composer.lock exists) or fail with missing_composer_lock
    if (! $result->passed()) {
        expect($result->details())->toHaveKey('issues');
    } else {
        expect($result->details())->toHaveKey('total_packages');
    }
});

it('analyzes Laravel version detection logic', function () {
    // Test the Laravel version analysis method indirectly
    $result = $this->rule->check();

    expect($result)->toBeInstanceOf(Grazulex\LaravelSafeguard\SafeguardResult::class);

    // If Laravel framework is detected, it should be in the audit results
    if ($result->passed() && isset($result->details()['package_audit'])) {
        $audit = $result->details()['package_audit'];
        expect($audit)->toBeArray();
    }
});

it('provides security recommendations when issues found', function () {
    $result = $this->rule->check();

    if (! $result->passed()) {
        expect($result->details())->toHaveKey('recommendations');
        expect($result->details()['recommendations'])->toBeArray();
    }
});

it('handles empty or missing composer.lock gracefully', function () {
    $result = $this->rule->check();

    // Should not throw exceptions
    expect($result)->toBeInstanceOf(Grazulex\LaravelSafeguard\SafeguardResult::class);
    expect($result->details())->toBeArray();
});
