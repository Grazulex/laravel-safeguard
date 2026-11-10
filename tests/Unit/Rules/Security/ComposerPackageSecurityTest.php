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
it('maps composer audit advisories into issue entries', function () {
    $rule = new ComposerPackageSecurity();

    $audit = [
        'packages' => [
            [
                'name' => 'example/package',
                'version' => '1.2.3',
                'latest' => '1.3.0',
                'advisories' => [
                    [
                        'advisoryId' => 'PKSA-0000-0000',
                        'cve' => 'CVE-2026-1234',
                        'title' => 'Bad thing happens when parsing crafted input',
                        'link' => 'https://example.org/advisory',
                        'affectedVersions' => '>=1.0,<1.2.4',
                        'severity' => 'medium',
                    ],
                ],
            ],
            ['name' => 'clean/package', 'version' => '2.0.0', 'advisories' => []],
        ],
    ];

    $issues = $rule->mapAdvisoriesToIssues($audit);

    expect($issues)->toHaveCount(1)
        ->and($issues[0]['type'])->toBe('security_vulnerability')
        ->and($issues[0]['package'])->toBe('example/package')
        ->and($issues[0]['version'])->toBe('1.2.3')
        ->and($issues[0]['cve'])->toBe('CVE-2026-1234')
        ->and($issues[0]['severity'])->toBe('medium')
        ->and($issues[0]['fixed_in'])->toBe('1.3.0');
});

it('returns no advisory issues when composer audit reports none', function () {
    $rule = new ComposerPackageSecurity();

    expect($rule->mapAdvisoriesToIssues(['packages' => []]))->toBe([]);
    expect($rule->mapAdvisoriesToIssues([]))->toBe([]);
});

it('defaults an unknown advisory severity to high', function () {
    $rule = new ComposerPackageSecurity();

    $issues = $rule->mapAdvisoriesToIssues([
        'packages' => [
            ['name' => 'vendor/pkg', 'version' => '1.0.0', 'advisories' => [['title' => 'Untitled advisory']]],
        ],
    ]);

    expect($issues)->toHaveCount(1)->and($issues[0]['severity'])->toBe('high');
});

it('ignores malformed entries in the composer audit payload', function () {
    $rule = new ComposerPackageSecurity();

    $issues = $rule->mapAdvisoriesToIssues([
        'packages' => [
            'not-a-package',
            ['version' => '1.0.0', 'advisories' => [['title' => 'no name, skipped']]],
            ['name' => 'ok/pkg', 'version' => '1.0.0', 'advisories' => ['not-an-advisory']],
        ],
    ]);

    expect($issues)->toBe([]);
});

it('runComposerAudit returns null or a decoded payload in the test environment', function () {
    $rule = new ComposerPackageSecurity();
    $method = new ReflectionMethod($rule, 'runComposerAudit');
    $method->setAccessible(true);

    $result = $method->invoke($rule);

    expect($result === null || is_array($result))->toBeTrue();
});
