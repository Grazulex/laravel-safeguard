<?php

declare(strict_types=1);

use Grazulex\LaravelSafeguard\Formatters\AbstractFormatter;
use Grazulex\LaravelSafeguard\Formatters\FormatterInterface;
use Grazulex\LaravelSafeguard\Formatters\HtmlFormatter;
use Grazulex\LaravelSafeguard\Formatters\JsonFormatter;
use Grazulex\LaravelSafeguard\Formatters\JunitFormatter;
use Grazulex\LaravelSafeguard\SafeguardResult;

function formatterSampleResults(): Illuminate\Support\Collection
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
            'result' => SafeguardResult::critical('APP_DEBUG is enabled <script>alert("x")</script> & more', [
                'recommendation' => 'Set APP_DEBUG=false',
                'issues' => ['first <issue>', 'second'],
                'nested' => ['key' => 'value'],
                'enabled' => true,
            ]),
        ],
        [
            'rule' => 'database-connection-encrypted',
            'description' => 'Checks database encryption',
            'severity' => 'warning',
            'result' => SafeguardResult::warning('Database connection not encrypted', [
                'recommendation' => 'Enable SSL',
            ]),
        ],
    ]);
}

it('implements the common formatter interface', function (string $class) {
    expect(new $class)->toBeInstanceOf(FormatterInterface::class);
})->with([JsonFormatter::class, JunitFormatter::class, HtmlFormatter::class]);

it('summarizes results', function () {
    expect(AbstractFormatter::summarize(formatterSampleResults()))->toBe([
        'total' => 3,
        'passed' => 1,
        'errors' => 1,
        'warnings' => 1,
    ]);
});

it('formats results as JSON', function () {
    $decoded = json_decode((new JsonFormatter)->format(formatterSampleResults(), 'production'), true);

    expect($decoded['status'])->toBe('failed')
        ->and($decoded['environment'])->toBe('production')
        ->and($decoded['summary'])->toBe(['total' => 3, 'passed' => 1, 'errors' => 1, 'warnings' => 1])
        ->and($decoded['results'])->toHaveCount(3)
        ->and($decoded['results'][1]['rule'])->toBe('app-debug-false-in-production')
        ->and($decoded['results'][1]['status'])->toBe('failed')
        ->and($decoded['results'][1]['severity'])->toBe('critical');
});

it('formats results as valid JUnit XML', function () {
    $xml = (new JunitFormatter)->format(formatterSampleResults(), 'production');

    expect($xml)->toStartWith('<?xml version="1.0" encoding="UTF-8"?>');

    $document = simplexml_load_string($xml);

    expect($document)->not->toBeFalse()
        ->and($document->getName())->toBe('testsuites')
        ->and((string) $document['tests'])->toBe('3')
        ->and((string) $document['failures'])->toBe('1');

    $suite = $document->testsuite;

    expect((string) $suite['name'])->toBe('Laravel Safeguard (production)')
        ->and((string) $suite['tests'])->toBe('3')
        ->and((string) $suite['failures'])->toBe('1')
        ->and($suite->testcase)->toHaveCount(3);

    $passed = $suite->testcase[0];
    expect((string) $passed['name'])->toBe('app-key-is-set')
        ->and((string) $passed['classname'])->toBe('safeguard.production')
        ->and(isset($passed->failure))->toBeFalse();

    $failed = $suite->testcase[1];
    expect((string) $failed->failure['message'])->toBe('APP_DEBUG is enabled <script>alert("x")</script> & more')
        ->and((string) $failed->failure['type'])->toBe('critical')
        ->and((string) $failed->failure)->toContain('Recommendation: Set APP_DEBUG=false')
        ->and((string) $failed->failure)->toContain('  - first <issue>')
        ->and((string) $failed->failure)->toContain('Nested: {"key":"value"}')
        ->and((string) $failed->failure)->toContain('Enabled: true');

    $warning = $suite->testcase[2];
    expect(isset($warning->failure))->toBeFalse()
        ->and((string) $warning->{'system-out'})->toContain('[warning] Database connection not encrypted');
});

it('strips characters that are invalid in XML', function () {
    $results = collect([[
        'rule' => 'broken-rule',
        'description' => 'desc',
        'severity' => 'error',
        'result' => SafeguardResult::fail("Bad \x01 char \x1F here"),
    ]]);

    $document = simplexml_load_string((new JunitFormatter)->format($results, 'testing'));

    expect($document)->not->toBeFalse()
        ->and((string) $document->testsuite->testcase[0]->failure['message'])->toBe('Bad  char  here');
});

it('produces a JUnit report for an empty result set', function () {
    $document = simplexml_load_string((new JunitFormatter)->format(collect(), 'testing'));

    expect($document)->not->toBeFalse()
        ->and((string) $document['tests'])->toBe('0')
        ->and((string) $document['failures'])->toBe('0');
});

it('formats results as an escaped HTML report', function () {
    $html = (new HtmlFormatter)->format(formatterSampleResults(), 'production');

    expect($html)->toStartWith('<!DOCTYPE html>')
        ->and($html)->toContain('Laravel Safeguard Security Report')
        ->and($html)->toContain('Environment: <strong>production</strong>')
        ->and($html)->toContain('<code>app-key-is-set</code>')
        ->and($html)->toContain('<code>app-debug-false-in-production</code>')
        ->and($html)->toContain('badge-passed')
        ->and($html)->toContain('badge-failed')
        ->and($html)->toContain('badge-warning')
        ->and($html)->toContain('<details><summary>Details</summary>')
        ->and($html)->toContain('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt; &amp; more')
        ->and($html)->toContain('<li>first &lt;issue&gt;</li>')
        ->and($html)->not->toContain('<script>')
        ->and($html)->toContain('</html>');
});

it('produces an HTML report for an empty result set', function () {
    $html = (new HtmlFormatter)->format(collect(), 'testing');

    expect($html)->toContain('No rules were executed.')
        ->and($html)->toContain('status-passed">passed');
});
