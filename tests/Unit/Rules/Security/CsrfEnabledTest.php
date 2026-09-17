<?php

declare(strict_types=1);

use Grazulex\LaravelSafeguard\Rules\Security\CsrfEnabled;
use Grazulex\LaravelSafeguard\SafeguardResult;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

final class CustomCsrfMiddlewareForTest extends VerifyCsrfToken {}

beforeEach(function () {
    $this->rule = new CsrfEnabled();
});

it('returns the correct id', function () {
    expect($this->rule->id())->toBe('csrf-enabled');
});

it('passes when the web group uses PreventRequestForgery (Laravel 13)', function () {
    Route::middlewareGroup('web', [PreventRequestForgery::class]);

    $result = $this->rule->check();

    expect($result)->toBeInstanceOf(SafeguardResult::class)
        ->and($result->passed())->toBeTrue();
})->skip(! class_exists(PreventRequestForgery::class), 'PreventRequestForgery only exists on Laravel 13+');

it('passes when the web group uses VerifyCsrfToken (Laravel 12)', function () {
    Route::middlewareGroup('web', [VerifyCsrfToken::class]);

    expect($this->rule->check()->passed())->toBeTrue();
});

it('passes when the web group uses a subclass of the CSRF middleware', function () {
    Route::middlewareGroup('web', [CustomCsrfMiddlewareForTest::class]);

    expect($this->rule->check()->passed())->toBeTrue();
});

it('passes when the web group uses the csrf alias', function () {
    Route::middlewareGroup('web', ['csrf']);

    expect($this->rule->check()->passed())->toBeTrue();
});

it('fails when the web group has no CSRF middleware', function () {
    Route::middlewareGroup('web', []);

    $result = $this->rule->check();

    expect($result->passed())->toBeFalse()
        ->and($result->message())->toContain('CSRF protection is disabled');
});
