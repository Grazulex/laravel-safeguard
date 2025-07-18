<?php

declare(strict_types=1);

use Grazulex\LaravelSafeguard\Rules\Authentication\SessionSecuritySettings;

beforeEach(function () {
    $this->rule = new SessionSecuritySettings();
});

it('returns the correct id', function () {
    expect($this->rule->id())->toBe('session-security-settings');
});

it('returns the correct description', function () {
    expect($this->rule->description())->toBe('Verifies that session security settings are properly configured');
});

it('returns correct severity', function () {
    expect($this->rule->severity())->toBe('error');
});

it('applies to all environments', function () {
    expect($this->rule->appliesToEnvironment('local'))->toBeTrue();
    expect($this->rule->appliesToEnvironment('testing'))->toBeTrue();
    expect($this->rule->appliesToEnvironment('production'))->toBeTrue();
});

it('passes with secure session configuration', function () {
    config([
        'session.driver' => 'database',
        'session.lifetime' => 120,
        'session.secure' => true,
        'session.http_only' => true,
        'session.same_site' => 'strict',
        'session.encrypt' => true,
        'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
    ]);

    $result = $this->rule->check();

    expect($result->passed())->toBeTrue();
});

it('fails with insecure session driver', function () {
    config([
        'session.driver' => 'cookie',
    ]);

    $result = $this->rule->check();

    expect($result->passed())->toBeFalse();
    expect($result->details()['issues'][0]['type'])->toBe('insecure_session_driver');
    expect($result->details()['issues'][0]['severity'])->toBe('critical');
});

it('warns about excessive session lifetime', function () {
    config([
        'session.driver' => 'database',
        'session.lifetime' => 600, // 10 hours
    ]);

    $result = $this->rule->check();

    expect($result->passed())->toBeFalse();
    expect($result->details()['issues'][0]['type'])->toBe('excessive_session_lifetime');
});

it('fails with insecure cookies in production', function () {
    app()->detectEnvironment(fn () => 'production');

    config([
        'session.driver' => 'database',
        'session.secure' => false,
    ]);

    $result = $this->rule->check();

    expect($result->passed())->toBeFalse();
    expect($result->details()['issues'][0]['type'])->toBe('insecure_session_cookie');
    expect($result->severity())->toBe('critical');
});

it('fails with non-HttpOnly cookies', function () {
    config([
        'session.driver' => 'database',
        'session.http_only' => false,
    ]);

    $result = $this->rule->check();

    expect($result->passed())->toBeFalse();
    expect($result->details()['issues'][0]['type'])->toBe('session_cookie_not_http_only');
});

it('warns about weak SameSite policy', function () {
    config([
        'session.driver' => 'database',
        'session.same_site' => null,
    ]);

    $result = $this->rule->check();

    expect($result->passed())->toBeFalse();
    expect($result->details()['issues'][0]['type'])->toBe('weak_same_site_policy');
});

it('warns about unencrypted sessions', function () {
    config([
        'session.driver' => 'database',
        'session.encrypt' => false,
    ]);

    $result = $this->rule->check();

    expect($result->passed())->toBeFalse();
    expect($result->details()['issues'][0]['type'])->toBe('session_not_encrypted');
});

it('fails with encryption enabled but no APP_KEY', function () {
    config([
        'session.driver' => 'database',
        'session.encrypt' => true,
        'app.key' => '',
    ]);

    $result = $this->rule->check();

    expect($result->passed())->toBeFalse();
    expect($result->details()['issues'][0]['type'])->toBe('missing_encryption_key');
    expect($result->details()['issues'][0]['severity'])->toBe('critical');
});
