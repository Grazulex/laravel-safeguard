<?php

declare(strict_types=1);

use Grazulex\LaravelSafeguard\Rules\Environment\EnvHasAllRequiredKeys;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->rule = new EnvHasAllRequiredKeys();
    $this->envFile = base_path('.env');
});

afterEach(function () {
    if (File::exists($this->envFile.'.backup')) {
        File::move($this->envFile.'.backup', $this->envFile);
    }
});

it('returns the correct id', function () {
    expect($this->rule->id())->toBe('env-has-all-required-keys');
});

it('returns the correct description', function () {
    expect($this->rule->description())->toBe('Verifies that all required environment variables are present');
});

it('passes when all required keys are present', function () {
    // Mock the config to require only a few variables
    config(['safeguard.required_env_vars' => ['APP_NAME', 'APP_ENV']]);

    // Set the environment variables using putenv for the test
    putenv('APP_NAME=Test');
    putenv('APP_ENV=testing');

    $result = $this->rule->check();

    expect($result->passed())->toBeTrue();

    // Clean up
    putenv('APP_NAME');
    putenv('APP_ENV');
});

it('fails when required keys are missing', function () {
    // Mock the config to require variables that don't exist
    config(['safeguard.required_env_vars' => ['NONEXISTENT_VAR', 'ANOTHER_MISSING_VAR']]);

    $result = $this->rule->check();

    expect($result->passed())->toBeFalse();
    expect($result->message())->toContain('Missing required environment variables');
});

it('applies to all environments', function () {
    expect($this->rule->appliesToEnvironment('local'))->toBeTrue();
    expect($this->rule->appliesToEnvironment('testing'))->toBeTrue();
    expect($this->rule->appliesToEnvironment('production'))->toBeTrue();
});

it('returns correct severity', function () {
    expect($this->rule->severity())->toBe('error');
});
