<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Grazulex\LaravelSafeguard\Baseline\Baseline;
use Grazulex\LaravelSafeguard\Baseline\BaselineEntry;
use Grazulex\LaravelSafeguard\Baseline\InvalidBaselineException;
use Grazulex\LaravelSafeguard\SafeguardResult;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->baselineDir = sys_get_temp_dir().'/safeguard-baseline-unit-'.uniqid();
    File::ensureDirectoryExists($this->baselineDir);
});

afterEach(function () {
    File::deleteDirectory($this->baselineDir);
});

it('parses entries from an object with an accepted list', function () {
    $baseline = Baseline::fromArray([
        'accepted' => [
            ['rule' => 'csrf-enabled', 'reason' => 'Legacy webhook', 'expires' => '2030-01-31'],
        ],
    ]);

    $entry = $baseline->entryFor('csrf-enabled');

    expect($baseline->entries())->toHaveCount(1)
        ->and($entry)->toBeInstanceOf(BaselineEntry::class)
        ->and($entry->reason)->toBe('Legacy webhook')
        ->and($entry->expires->format('Y-m-d'))->toBe('2030-01-31')
        ->and($baseline->entryFor('unknown-rule'))->toBeNull();
});

it('parses entries from a plain list', function () {
    $baseline = Baseline::fromArray([
        ['rule' => 'csrf-enabled', 'reason' => 'Legacy webhook', 'expires' => '2030-01-31'],
        ['rule' => 'app-key-is-set', 'reason' => 'Rotated next sprint', 'expires' => '2030-02-28'],
    ]);

    expect(array_keys($baseline->entries()))->toBe(['csrf-enabled', 'app-key-is-set']);
});

it('accepts an empty baseline', function () {
    expect(Baseline::fromArray(['accepted' => []])->entries())->toBe([]);
});

it('rejects invalid baselines', function (array $data, string $message) {
    expect(fn () => Baseline::fromArray($data))
        ->toThrow(InvalidBaselineException::class, $message);
})->with([
    'missing accepted key' => [['entries' => []], 'missing "accepted" list'],
    'accepted not a list' => [['accepted' => 'nope'], '"accepted" must be a list'],
    'entry not an object' => [['accepted' => ['csrf-enabled']], 'entry #1 must be an object'],
    'missing rule' => [['accepted' => [['reason' => 'x', 'expires' => '2030-01-01']]], 'non-empty "rule"'],
    'missing reason' => [['accepted' => [['rule' => 'a', 'expires' => '2030-01-01']]], 'non-empty "reason"'],
    'blank reason' => [['accepted' => [['rule' => 'a', 'reason' => '  ', 'expires' => '2030-01-01']]], 'non-empty "reason"'],
    'missing expires' => [['accepted' => [['rule' => 'a', 'reason' => 'x']]], 'non-empty "expires"'],
    'bad date format' => [['accepted' => [['rule' => 'a', 'reason' => 'x', 'expires' => '31/12/2030']]], 'invalid "expires" date'],
    'impossible date' => [['accepted' => [['rule' => 'a', 'reason' => 'x', 'expires' => '2030-02-30']]], 'invalid "expires" date'],
    'duplicate rule' => [['accepted' => [
        ['rule' => 'a', 'reason' => 'x', 'expires' => '2030-01-01'],
        ['rule' => 'a', 'reason' => 'y', 'expires' => '2030-01-02'],
    ]], 'duplicate entry for rule "a"'],
]);

it('loads a baseline from a JSON file', function () {
    $path = $this->baselineDir.'/safeguard-baseline.json';
    File::put($path, json_encode(['accepted' => [
        ['rule' => 'csrf-enabled', 'reason' => 'Legacy webhook', 'expires' => '2030-01-31'],
    ]]));

    expect(Baseline::fromFile($path)->entryFor('csrf-enabled'))->not->toBeNull();
});

it('fails on a missing file or invalid JSON', function () {
    $missing = $this->baselineDir.'/missing.json';
    $invalid = $this->baselineDir.'/invalid.json';
    File::put($invalid, '{not json');

    expect(fn () => Baseline::fromFile($missing))->toThrow(InvalidBaselineException::class, 'not found')
        ->and(fn () => Baseline::fromFile($invalid))->toThrow(InvalidBaselineException::class, 'Invalid JSON');
});

it('loads the configured baseline only when enabled and present', function () {
    $path = $this->baselineDir.'/safeguard-baseline.json';
    File::put($path, json_encode(['accepted' => []]));

    config(['safeguard.baseline' => ['enabled' => true, 'path' => $path]]);
    expect(Baseline::fromConfig())->toBeInstanceOf(Baseline::class);

    config(['safeguard.baseline' => ['enabled' => false, 'path' => $path]]);
    expect(Baseline::fromConfig())->toBeNull();

    config(['safeguard.baseline' => ['enabled' => true, 'path' => $this->baselineDir.'/missing.json']]);
    expect(Baseline::fromConfig())->toBeNull();
});

it('keeps an exception valid up to and including its expiry date', function () {
    $entry = new BaselineEntry('a', 'reason', CarbonImmutable::parse('2030-06-15'));

    expect($entry->isExpired(CarbonImmutable::parse('2030-06-14 12:00')))->toBeFalse()
        ->and($entry->isExpired(CarbonImmutable::parse('2030-06-15 23:59')))->toBeFalse()
        ->and($entry->isExpired(CarbonImmutable::parse('2030-06-16 00:00')))->toBeTrue();
});

it('annotates failed checks covered by the baseline', function () {
    $baseline = Baseline::fromArray(['accepted' => [
        ['rule' => 'accepted-rule', 'reason' => 'Known risk', 'expires' => '2030-06-15'],
        ['rule' => 'expired-rule', 'reason' => 'Old risk', 'expires' => '2030-01-01'],
        ['rule' => 'passing-rule', 'reason' => 'Not needed', 'expires' => '2030-06-15'],
    ]]);

    $results = collect([
        ['rule' => 'accepted-rule', 'description' => 'd', 'severity' => 'error', 'result' => SafeguardResult::fail('Accepted failure')],
        ['rule' => 'expired-rule', 'description' => 'd', 'severity' => 'critical', 'result' => SafeguardResult::critical('Expired failure')],
        ['rule' => 'passing-rule', 'description' => 'd', 'severity' => 'error', 'result' => SafeguardResult::pass('OK')],
        ['rule' => 'other-rule', 'description' => 'd', 'severity' => 'error', 'result' => SafeguardResult::fail('Other failure')],
    ]);

    $annotated = $baseline->apply($results, CarbonImmutable::parse('2030-03-01'))->values();

    expect($annotated[0]['baseline'])->toBe(['status' => 'accepted', 'reason' => 'Known risk', 'expires' => '2030-06-15'])
        ->and($annotated[1]['baseline'])->toBe(['status' => 'expired', 'reason' => 'Old risk', 'expires' => '2030-01-01'])
        ->and($annotated[2])->not->toHaveKey('baseline')
        ->and($annotated[3])->not->toHaveKey('baseline');
});
