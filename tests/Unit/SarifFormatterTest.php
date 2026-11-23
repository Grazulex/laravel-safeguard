<?php

declare(strict_types=1);

use Grazulex\LaravelSafeguard\Baseline\BaselineEntry;
use Grazulex\LaravelSafeguard\Formatters\SarifFormatter;
use Grazulex\LaravelSafeguard\SafeguardResult;
use Illuminate\Support\Collection;

function sarifSampleResults(): Collection
{
    return collect([
        [
            'rule' => 'app-key-is-set',
            'description' => 'Verifies that APP_KEY is set',
            'severity' => 'critical',
            'result' => SafeguardResult::pass('APP_KEY is set'),
        ],
        [
            'rule' => 'app-debug-false-in-production',
            'description' => 'Ensures debug is off',
            'severity' => 'critical',
            'result' => SafeguardResult::critical('APP_DEBUG is enabled <script> & "more"', [
                'recommendation' => 'Set APP_DEBUG=false',
                'issues' => ['first <issue>', 'second'],
            ]),
        ],
        [
            'rule' => 'database-connection-encrypted',
            'description' => 'Checks database encryption',
            'severity' => 'error',
            'result' => SafeguardResult::warning('Database connection not encrypted'),
        ],
    ]);
}

function sarifRun(Collection $results, string $environment = 'production'): array
{
    $decoded = json_decode((new SarifFormatter)->format($results, $environment), true, 512, JSON_THROW_ON_ERROR);

    return $decoded['runs'][0];
}

function sarifSingleResult(SafeguardResult $result, array $extra = []): array
{
    return sarifRun(collect([array_merge([
        'rule' => 'fixture-rule',
        'description' => 'Fixture rule',
        'severity' => 'error',
        'result' => $result,
    ], $extra)]))['results'][0];
}

it('formats results as a SARIF 2.1.0 log', function () {
    $decoded = json_decode((new SarifFormatter)->format(sarifSampleResults(), 'production'), true);

    expect($decoded['version'])->toBe('2.1.0')
        ->and($decoded['$schema'])->toBe('https://json.schemastore.org/sarif-2.1.0.json')
        ->and($decoded['runs'])->toHaveCount(1);

    $run = $decoded['runs'][0];

    expect($run['tool']['driver']['name'])->toBe('Laravel Safeguard')
        ->and($run['tool']['driver']['informationUri'])->toBe('https://github.com/Grazulex/laravel-safeguard')
        ->and($run['automationDetails']['id'])->toBe('laravel-safeguard/production')
        ->and($run['invocations'][0]['executionSuccessful'])->toBeTrue()
        ->and($run['invocations'][0]['endTimeUtc'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/')
        ->and($run['properties']['environment'])->toBe('production')
        ->and($run['properties']['summary'])->toBe(['total' => 3, 'passed' => 1, 'errors' => 1, 'warnings' => 1])
        ->and($run['properties']['baseline'])->toBe(['accepted' => 0, 'expired' => 0]);
});

it('declares every executed rule in the SARIF tool driver', function () {
    $rules = sarifRun(sarifSampleResults())['tool']['driver']['rules'];

    expect($rules)->toHaveCount(3)
        ->and(array_column($rules, 'id'))->toBe([
            'app-key-is-set',
            'app-debug-false-in-production',
            'database-connection-encrypted',
        ])
        ->and($rules[0]['shortDescription']['text'])->toBe('Verifies that APP_KEY is set')
        ->and($rules[0]['defaultConfiguration']['level'])->toBe('error')
        ->and($rules[0]['properties'])->toBe([
            'tags' => ['security'],
            'severity' => 'critical',
            'security-severity' => '9.0',
        ])
        ->and($rules[2]['properties']['security-severity'])->toBe('7.0');
});

it('reports only failed rules as SARIF results', function () {
    $results = sarifRun(sarifSampleResults())['results'];

    expect($results)->toHaveCount(2);

    $critical = $results[0];

    expect($critical['ruleId'])->toBe('app-debug-false-in-production')
        ->and($critical['ruleIndex'])->toBe(1)
        ->and($critical['level'])->toBe('error')
        ->and($critical['message']['text'])->toStartWith('APP_DEBUG is enabled <script> & "more"')
        ->and($critical['message']['text'])->toContain('Recommendation: Set APP_DEBUG=false')
        ->and($critical['message']['text'])->toContain('  - first <issue>')
        ->and($critical['properties'])->toBe(['severity' => 'critical', 'status' => 'failed'])
        ->and($critical)->not->toHaveKey('suppressions');

    $warning = $results[1];

    expect($warning['ruleId'])->toBe('database-connection-encrypted')
        ->and($warning['ruleIndex'])->toBe(2)
        ->and($warning['level'])->toBe('warning')
        ->and($warning['message']['text'])->toBe('Database connection not encrypted')
        ->and($warning['properties'])->toBe(['severity' => 'warning', 'status' => 'warning']);
});

it('maps severities to SARIF levels', function (string $severity, string $level) {
    expect(sarifSingleResult(SafeguardResult::fail('Failure', $severity))['level'])->toBe($level);
})->with([
    ['critical', 'error'],
    ['error', 'error'],
    ['warning', 'warning'],
    ['info', 'note'],
    ['custom', 'error'],
]);

it('uses a default location for findings that are not tied to a file', function () {
    $result = sarifSingleResult(SafeguardResult::fail('Failure', 'error', ['recommendation' => 'Fix it']));

    expect($result['locations'])->toBe([
        ['physicalLocation' => ['artifactLocation' => ['uri' => 'composer.json']]],
    ]);
});

it('extracts file locations from the result details', function () {
    $result = sarifSingleResult(SafeguardResult::critical('Secrets found', [
        'findings' => [
            ['file' => 'app/Services/Payment Gateway.php', 'line' => 12, 'pattern' => 'sk_live'],
            ['file' => base_path('config/services.php'), 'line' => 3],
            ['file' => 'app/Services/Payment Gateway.php', 'line' => 12, 'pattern' => 'duplicate'],
            ['file' => './routes/web.php', 'line' => 0],
        ],
        'file_path' => base_path('.env'),
    ]));

    expect($result['locations'])->toBe([
        ['physicalLocation' => ['artifactLocation' => ['uri' => '.env']]],
        ['physicalLocation' => [
            'artifactLocation' => ['uri' => 'app/Services/Payment%20Gateway.php'],
            'region' => ['startLine' => 12],
        ]],
        ['physicalLocation' => [
            'artifactLocation' => ['uri' => 'config/services.php'],
            'region' => ['startLine' => 3],
        ]],
        ['physicalLocation' => ['artifactLocation' => ['uri' => 'routes/web.php']]],
    ]);
});

it('ignores files outside of the project', function () {
    $result = sarifSingleResult(SafeguardResult::fail('Failure', 'error', [
        'file' => dirname(base_path()).'/outside-of-the-project.php',
        'nested' => ['file' => '../../etc/passwd', 'line' => 1],
    ]));

    expect($result['locations'])->toBe([
        ['physicalLocation' => ['artifactLocation' => ['uri' => 'composer.json']]],
    ]);
});

it('resolves paths that are relative to a scanned directory', function () {
    $directory = base_path('safeguard-sarif-scan');
    @mkdir($directory);
    file_put_contents($directory.'/Leaky.php', '<?php');
    config(['safeguard.scan_paths' => [$directory]]);

    try {
        $result = sarifSingleResult(SafeguardResult::critical('Secrets found', [
            'findings' => [['file' => 'Leaky.php', 'line' => 1]],
        ]));
    } finally {
        @unlink($directory.'/Leaky.php');
        @rmdir($directory);
    }

    expect($result['locations'])->toBe([
        ['physicalLocation' => [
            'artifactLocation' => ['uri' => 'safeguard-sarif-scan/Leaky.php'],
            'region' => ['startLine' => 1],
        ]],
    ]);
});

it('keeps at most ten locations per result', function () {
    $findings = [];

    for ($line = 1; $line <= 15; $line++) {
        $findings[] = ['file' => 'app/Secrets.php', 'line' => $line];
    }

    $result = sarifSingleResult(SafeguardResult::critical('Secrets found', ['findings' => $findings]));

    expect($result['locations'])->toHaveCount(10)
        ->and($result['locations'][9]['physicalLocation']['region']['startLine'])->toBe(10);
});

it('reports findings accepted in the baseline as suppressed notes', function () {
    $results = collect([[
        'rule' => 'csrf-enabled',
        'description' => 'CSRF protection',
        'severity' => 'error',
        'result' => SafeguardResult::fail('CSRF protection is disabled'),
        'baseline' => [
            'status' => BaselineEntry::STATUS_ACCEPTED,
            'reason' => 'Legacy payment webhook',
            'expires' => '2999-12-31',
        ],
    ]]);

    $run = sarifRun($results);
    $result = $run['results'][0];

    expect($run['properties']['summary']['errors'])->toBe(0)
        ->and($run['properties']['baseline'])->toBe(['accepted' => 1, 'expired' => 0])
        ->and($result['level'])->toBe('note')
        ->and($result['message']['text'])->toContain('Accepted in baseline until 2999-12-31: Legacy payment webhook')
        ->and($result['suppressions'])->toBe([
            ['kind' => 'external', 'status' => 'accepted', 'justification' => 'Legacy payment webhook'],
        ])
        ->and($result['properties']['status'])->toBe('accepted')
        ->and($result['properties']['baseline']['status'])->toBe(BaselineEntry::STATUS_ACCEPTED);
});

it('keeps findings whose baseline exception has expired as SARIF errors', function () {
    $result = sarifSingleResult(SafeguardResult::fail('CSRF protection is disabled'), [
        'baseline' => [
            'status' => BaselineEntry::STATUS_EXPIRED,
            'reason' => 'Old risk',
            'expires' => '2000-01-01',
        ],
    ]);

    expect($result['level'])->toBe('error')
        ->and($result['message']['text'])->toContain('Baseline exception expired on 2000-01-01: Old risk')
        ->and($result)->not->toHaveKey('suppressions')
        ->and($result['properties']['status'])->toBe('failed')
        ->and($result['properties']['baseline']['status'])->toBe(BaselineEntry::STATUS_EXPIRED);
});

it('produces a SARIF log for an empty result set', function () {
    $output = (new SarifFormatter)->format(collect(), 'testing');
    $run = json_decode($output, true)['runs'][0];

    expect($run['tool']['driver']['rules'])->toBe([])
        ->and($run['results'])->toBe([])
        ->and($output)->toContain('"rules": []')
        ->and($output)->toContain('"results": []');
});

it('falls back to the rule id when a rule has no description', function () {
    $run = sarifRun(collect([[
        'rule' => 'fixture-rule',
        'description' => '',
        'severity' => 'warning',
        'result' => SafeguardResult::pass('Fine'),
    ]]));

    expect($run['tool']['driver']['rules'][0]['shortDescription']['text'])->toBe('fixture-rule')
        ->and($run['tool']['driver']['rules'][0]['defaultConfiguration']['level'])->toBe('warning')
        ->and($run['results'])->toBe([]);
});
