<?php

declare(strict_types=1);

use Grazulex\LaravelSafeguard\Rules\Security\ComposerPackageSecurity;
use Illuminate\Support\Facades\Process;

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
// Real `composer audit --locked --format=json` output (Composer 2.11) against
// a lock file containing symfony/http-kernel 5.1.0 — the fixture the mapping
// must actually handle (advisories keyed by package name, no installed
// version, no "latest" field).
const REAL_AUDIT_FIXTURE = <<<'JSON'
{
    "advisories": {
        "symfony/http-kernel": [
            {
                "advisoryId": "PKSA-8knv-7jsn-12w8",
                "packageName": "symfony/http-kernel",
                "affectedVersions": ">=4.3.0,<4.4.0|>=4.4.0,<4.4.13|>=5.0.0,<5.1.0|>=5.1.0,<5.1.5",
                "title": "CVE-2020-15094: Prevent RCE when calling untrusted remote with CachingHttpClient",
                "cve": "CVE-2020-15094",
                "link": "https://symfony.com/cve-2020-15094",
                "reportedAt": "2020-09-02T08:00:00+00:00",
                "sources": [],
                "severity": "high"
            }
        ]
    },
    "abandoned": [],
    "filter": [],
    "cooldown": []
}
JSON;

it('maps real composer audit advisories into issue entries', function () {
    $rule = new ComposerPackageSecurity();

    $audit = json_decode(REAL_AUDIT_FIXTURE, true);
    $packages = [
        'symfony/http-kernel' => ['version' => 'v5.1.0', 'type' => 'production'],
    ];

    $issues = $rule->mapAdvisoriesToIssues($audit, $packages);

    expect($issues)->toHaveCount(1)
        ->and($issues[0]['type'])->toBe('security_vulnerability')
        ->and($issues[0]['package'])->toBe('symfony/http-kernel')
        ->and($issues[0]['version'])->toBe('v5.1.0')
        ->and($issues[0]['vulnerability'])->toBe('CVE-2020-15094: Prevent RCE when calling untrusted remote with CachingHttpClient')
        ->and($issues[0]['cve'])->toBe('CVE-2020-15094')
        ->and($issues[0]['severity'])->toBe('high')
        ->and($issues[0]['link'])->toBe('https://symfony.com/cve-2020-15094')
        ->and($issues[0]['affected_versions'])->toBe('>=4.3.0,<4.4.0|>=4.4.0,<4.4.13|>=5.0.0,<5.1.0|>=5.1.0,<5.1.5')
        ->and($issues[0])->not->toHaveKey('fixed_in');
});

it('reports an unknown version when the audited package is not in the lock file', function () {
    $rule = new ComposerPackageSecurity();

    $audit = ['advisories' => ['vendor/pkg' => [['title' => 'Something is wrong', 'severity' => 'low']]]];

    $issues = $rule->mapAdvisoriesToIssues($audit, []);

    expect($issues)->toHaveCount(1)->and($issues[0]['version'])->toBe('unknown');
});

it('returns no advisory issues for a clean audit payload', function () {
    $rule = new ComposerPackageSecurity();

    $clean = ['advisories' => [], 'abandoned' => [], 'filter' => [], 'cooldown' => []];

    expect($rule->mapAdvisoriesToIssues($clean, []))->toBe([])
        ->and($rule->mapAdvisoriesToIssues([], []))->toBe([]);
});

it('defaults an unknown advisory severity to high', function () {
    $rule = new ComposerPackageSecurity();

    $issues = $rule->mapAdvisoriesToIssues(
        ['advisories' => ['vendor/pkg' => [['title' => 'Untitled advisory']]]],
        []
    );

    expect($issues)->toHaveCount(1)->and($issues[0]['severity'])->toBe('high');
});

it('ignores malformed entries in the composer audit payload', function () {
    $rule = new ComposerPackageSecurity();

    $issues = $rule->mapAdvisoriesToIssues([
        'advisories' => [
            'not-a-list',
            'vendor/pkg' => 'also-not-a-list',
            'other/pkg' => ['not-an-advisory', 'string'],
        ],
    ], []);

    expect($issues)->toBe([]);
});

it('accepts every composer audit exit code that carries a JSON payload', function () {
    Process::fake([
        'composer audit*' => Process::result(exitCode: 3, output: '{"advisories":[],"abandoned":[],"filter":[],"cooldown":[]}'),
    ]);

    $rule = new ComposerPackageSecurity();
    $method = new ReflectionMethod($rule, 'runComposerAudit');
    $method->setAccessible(true);

    expect($method->invoke($rule))->toBe(['advisories' => [], 'abandoned' => [], 'filter' => [], 'cooldown' => []]);

    Process::assertRan(fn ($process, $result) => str_starts_with($process->command, 'composer audit --locked')
        && str_contains($process->command, '--no-plugins'));
});

it('rejects exit codes where composer audit itself failed', function () {
    Process::fake([
        'composer audit*' => Process::result(exitCode: 127, output: 'sh: composer: command not found'),
    ]);

    $rule = new ComposerPackageSecurity();
    $method = new ReflectionMethod($rule, 'runComposerAudit');
    $method->setAccessible(true);

    expect($method->invoke($rule))->toBeNull();
});

it('rejects payloads that are not valid json', function () {
    Process::fake([
        'composer audit*' => Process::result(exitCode: 1, output: 'Warning: The lock file is not up to date with the latest changes in composer.json.'),
    ]);

    $rule = new ComposerPackageSecurity();
    $method = new ReflectionMethod($rule, 'runComposerAudit');
    $method->setAccessible(true);

    expect($method->invoke($rule))->toBeNull();
});

it('detects a missing composer binary', function () {
    Process::fake([
        'composer --version*' => Process::result(exitCode: 127, output: 'sh: composer: command not found'),
    ]);

    $rule = new ComposerPackageSecurity();
    $method = new ReflectionMethod($rule, 'composerBinaryAvailable');
    $method->setAccessible(true);

    expect($method->invoke($rule))->toBeFalse();
});

it('detects an available composer binary', function () {
    Process::fake([
        'composer --version*' => Process::result(exitCode: 0, output: 'Composer version 2.11.1 2026-10-10 12:00:00'),
    ]);

    $rule = new ComposerPackageSecurity();
    $method = new ReflectionMethod($rule, 'composerBinaryAvailable');
    $method->setAccessible(true);

    expect($method->invoke($rule))->toBeTrue();
});

it('skips the advisory scan with a distinct reason when composer is missing', function () {
    Process::fake([
        'composer --version*' => Process::result(exitCode: 127),
    ]);

    $rule = new ComposerPackageSecurity();
    $method = new ReflectionMethod($rule, 'checkSecurityAdvisories');
    $method->setAccessible(true);

    $packages = [];
    $issues = [];
    $recommendations = [];
    $packageAudit = [];
    $status = $method->invokeArgs($rule, [&$packages, &$issues, &$recommendations, &$packageAudit]);

    expect($status)->toBe('skipped (composer not available)')
        ->and($recommendations)->not->toBeEmpty()
        ->and($packageAudit)->toBe([]);
});

it('skips the advisory scan with a distinct reason when the audit cannot run', function () {
    Process::fake([
        'composer --version*' => Process::result(exitCode: 0, output: 'Composer version 2.11.1'),
        'composer audit*' => Process::result(exitCode: 1, output: 'not json'),
    ]);

    $rule = new ComposerPackageSecurity();
    $method = new ReflectionMethod($rule, 'checkSecurityAdvisories');
    $method->setAccessible(true);

    $packages = [];
    $issues = [];
    $recommendations = [];
    $packageAudit = [];
    $status = $method->invokeArgs($rule, [&$packages, &$issues, &$recommendations, &$packageAudit]);

    expect($status)->toBe('skipped (composer audit could not run)')
        ->and($packageAudit)->toBe([]);
});

it('maps advisories from a completed audit into the package audit', function () {
    Process::fake([
        'composer --version*' => Process::result(exitCode: 0, output: 'Composer version 2.11.1'),
        'composer audit*' => Process::result(exitCode: 1, output: REAL_AUDIT_FIXTURE),
    ]);

    $rule = new ComposerPackageSecurity();
    $method = new ReflectionMethod($rule, 'checkSecurityAdvisories');
    $method->setAccessible(true);

    $issues = [];
    $recommendations = [];
    $packageAudit = [];
    $packages = ['symfony/http-kernel' => ['version' => 'v5.1.0', 'type' => 'production']];
    $status = $method->invokeArgs($rule, [&$packages, &$issues, &$recommendations, &$packageAudit]);

    expect($status)->toBe('completed')
        ->and($issues)->toHaveCount(1)
        ->and($issues[0]['version'])->toBe('v5.1.0')
        ->and($packageAudit['symfony/http-kernel']['vulnerabilities'][0]['cve'])->toBe('CVE-2020-15094')
        ->and($packageAudit['symfony/http-kernel']['vulnerabilities'][0]['affected_versions'])->toBe('>=4.3.0,<4.4.0|>=4.4.0,<4.4.13|>=5.0.0,<5.1.0|>=5.1.0,<5.1.5')
        ->and($packageAudit['symfony/http-kernel']['vulnerabilities'][0])->not->toHaveKey('fixed_in')
        ->and($packageAudit)->not->toHaveKey('advisory_scan');
});
