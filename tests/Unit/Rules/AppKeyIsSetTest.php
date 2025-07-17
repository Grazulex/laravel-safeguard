<?php

declare(strict_types=1);

use Grazulex\LaravelSafeguard\Rules\Configuration\AppKeyIsSet;

it('checks that APP_KEY is set', function () {
    $rule = new AppKeyIsSet();

    // Test with empty APP_KEY
    config(['app.key' => '']);
    $result = $rule->check();

    expect($result->passed())->toBeFalse();
    expect($result->severity())->toBe('critical');
    expect($result->message())->toContain('APP_KEY is not set');
});

it('detects invalid APP_KEY', function () {
    $rule = new AppKeyIsSet();

    // Test with invalid APP_KEY
    config(['app.key' => 'base64:']);
    $result = $rule->check();

    expect($result->passed())->toBeFalse();
    expect($result->message())->toContain('invalid or too short');
});

it('detects default APP_KEY values', function () {
    $rule = new AppKeyIsSet();

    // Test with suspicious key
    config(['app.key' => 'SomeRandomString']);
    $result = $rule->check();

    expect($result->passed())->toBeFalse();
    expect($result->message())->toContain('default/example value');
});

it('passes with valid APP_KEY', function () {
    $rule = new AppKeyIsSet();

    // Test with valid APP_KEY
    config(['app.key' => 'base64:'.base64_encode(str_repeat('x', 32))]);
    $result = $rule->check();

    expect($result->passed())->toBeTrue();
    expect($result->message())->toContain('properly configured');
});
