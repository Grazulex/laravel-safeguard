<?php

declare(strict_types=1);

it('passes basic assertions', function () {
    expect(true)->toBeTrue();
    expect(1 + 1)->toBe(2);
    expect('Laravel Safeguard')->toBeString();
});

it('has PHP requirements', function () {
    expect(PHP_VERSION_ID)->toBeGreaterThan(80300); // PHP 8.3+
    expect(extension_loaded('json'))->toBeTrue();
    expect(function_exists('mb_strtoupper'))->toBeTrue();
});

it('can access composer autoload', function () {
    expect(class_exists('Composer\\Autoload\\ClassLoader'))->toBeTrue();
});

it('validates basic Laravel functionality', function () {
    expect(function_exists('config'))->toBeTrue();
    expect(function_exists('app'))->toBeTrue();
});
