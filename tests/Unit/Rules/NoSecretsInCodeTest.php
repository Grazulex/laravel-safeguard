<?php

declare(strict_types=1);

use Grazulex\LaravelSafeguard\Rules\Security\NoSecretsInCode;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->rule = new NoSecretsInCode();
    $this->testDir = base_path('test-files');
    File::ensureDirectoryExists($this->testDir);
});

afterEach(function () {
    if (File::exists($this->testDir)) {
        File::deleteDirectory($this->testDir);
    }
});

it('returns the correct id', function () {
    expect($this->rule->id())->toBe('no-secrets-in-code');
});

it('returns the correct description', function () {
    expect($this->rule->description())->toBe('Scans codebase for potentially hardcoded secrets');
});

it('passes when no secrets are found', function () {
    File::put($this->testDir.'/clean.php', '<?php echo "Hello World";');

    config(['safeguard.scan_paths' => [$this->testDir]]);
    config(['safeguard.secret_patterns' => ['password', 'api_key', 'token', 'secret']]);

    $result = $this->rule->check();

    expect($result->passed())->toBeTrue();
});

it('fails when potential secrets are found', function () {
    File::put($this->testDir.'/bad.php', '<?php $password = "secret123";');

    config(['safeguard.scan_paths' => [$this->testDir]]);
    config(['safeguard.secret_patterns' => ['password', 'api_key', 'token', 'secret']]);

    $result = $this->rule->check();

    expect($result->passed())->toBeFalse();
    expect($result->message())->toContain('Potential secrets found in code');
});

it('detects various secret patterns', function () {
    config(['safeguard.scan_paths' => [$this->testDir]]);
    config(['safeguard.secret_patterns' => ['password', 'api_key', 'token', 'secret']]);

    $secretPatterns = [
        'password = "secret"',
        'api_key = "abc123"',
        'token = "xyz789"',
        'secret = "hidden"',
    ];

    foreach ($secretPatterns as $pattern) {
        File::put($this->testDir.'/test.php', "<?php $pattern;");

        $result = $this->rule->check();

        expect($result->passed())->toBeFalse();
    }
});

it('applies to all environments', function () {
    expect($this->rule->appliesToEnvironment('local'))->toBeTrue();
    expect($this->rule->appliesToEnvironment('testing'))->toBeTrue();
    expect($this->rule->appliesToEnvironment('production'))->toBeTrue();
});

it('returns correct severity', function () {
    expect($this->rule->severity())->toBe('critical');
});
