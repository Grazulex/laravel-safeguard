<?php

declare(strict_types=1);

use Grazulex\LaravelSafeguard\Baseline\Baseline;
use Grazulex\LaravelSafeguard\Console\Commands\SafeguardCheckCommand;
use Grazulex\LaravelSafeguard\Contracts\SafeguardRule;
use Grazulex\LaravelSafeguard\Formatters\AbstractFormatter;
use Grazulex\LaravelSafeguard\Formatters\HtmlFormatter;
use Grazulex\LaravelSafeguard\Formatters\JsonFormatter;
use Grazulex\LaravelSafeguard\Formatters\JunitFormatter;
use Grazulex\LaravelSafeguard\Rules\AbstractSafeguardRule;
use Grazulex\LaravelSafeguard\SafeguardResult;
use Grazulex\LaravelSafeguard\Services\SafeguardManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

function baselineFixtureRule(string $id, SafeguardResult $result): SafeguardRule
{
    return new class($id, $result) extends AbstractSafeguardRule
    {
        public function __construct(private readonly string $ruleId, private readonly SafeguardResult $result) {}

        public function id(): string
        {
            return $this->ruleId;
        }

        public function description(): string
        {
            return 'Fixture rule '.$this->ruleId;
        }

        public function check(): SafeguardResult
        {
            return $this->result;
        }

        public function appliesToEnvironment(string $environment): bool
        {
            return true;
        }

        public function severity(): string
        {
            return $this->result->severity();
        }
    };
}

function writeBaselineFile(string $path, array $entries): void
{
    File::put($path, json_encode(['accepted' => $entries], JSON_PRETTY_PRINT));
}

beforeEach(function () {
    $this->baselineDir = sys_get_temp_dir().'/safeguard-baseline-feature-'.uniqid();
    File::ensureDirectoryExists($this->baselineDir);
    $this->baselinePath = $this->baselineDir.'/safeguard-baseline.json';

    $this->manager = app(SafeguardManager::class);
    $this->manager->registerRule(baselineFixtureRule('fixture-passing', SafeguardResult::pass('Fixture passes')));
    $this->manager->registerRule(baselineFixtureRule('fixture-critical', SafeguardResult::critical('Fixture critical finding')));
    $this->manager->resetBaseline();

    config([
        'safeguard.rules' => ['fixture-passing' => true, 'fixture-critical' => true],
        'safeguard.baseline' => ['enabled' => true, 'path' => $this->baselinePath],
    ]);

    $this->future = now()->addYear()->format('Y-m-d');
    $this->past = now()->subDay()->format('Y-m-d');
});

afterEach(function () {
    File::deleteDirectory($this->baselineDir);
});

it('blocks on a failing rule without a baseline file', function () {
    $this->artisan(SafeguardCheckCommand::class, ['--ci' => true])
        ->expectsOutputToContain('[FAIL] fixture-critical: Fixture critical finding')
        ->assertExitCode(1);
});

it('does not block on a finding accepted in the baseline', function () {
    writeBaselineFile($this->baselinePath, [
        ['rule' => 'fixture-critical', 'reason' => 'Known legacy risk', 'expires' => $this->future],
    ]);

    $this->artisan(SafeguardCheckCommand::class, ['--ci' => true])
        ->expectsOutputToContain("[ACCEPTED] fixture-critical: Fixture critical finding (accepted in baseline until {$this->future}: Known legacy risk)")
        ->assertExitCode(0);

    $this->artisan(SafeguardCheckCommand::class, ['--fail-on-error' => true])
        ->expectsOutputToContain("Accepted in baseline until {$this->future}: Known legacy risk")
        ->expectsOutputToContain('1 accepted findings in baseline')
        ->assertExitCode(0);
});

it('flags and blocks on a finding whose exception has expired', function () {
    writeBaselineFile($this->baselinePath, [
        ['rule' => 'fixture-critical', 'reason' => 'Known legacy risk', 'expires' => $this->past],
    ]);

    $this->artisan(SafeguardCheckCommand::class, ['--ci' => true])
        ->expectsOutputToContain("[FAIL] fixture-critical: Fixture critical finding (baseline exception expired on {$this->past}: Known legacy risk)")
        ->assertExitCode(1);

    $this->artisan(SafeguardCheckCommand::class, ['--fail-on-error' => true])
        ->expectsOutputToContain("Baseline exception expired on {$this->past}")
        ->expectsOutputToContain('1 baseline exceptions have expired')
        ->assertExitCode(1);
});

it('keeps an exception valid on its expiry date', function () {
    writeBaselineFile($this->baselinePath, [
        ['rule' => 'fixture-critical', 'reason' => 'Last day', 'expires' => now()->format('Y-m-d')],
    ]);

    $this->artisan(SafeguardCheckCommand::class, ['--ci' => true])
        ->expectsOutputToContain('[ACCEPTED] fixture-critical')
        ->assertExitCode(0);
});

it('ignores the baseline with --no-baseline', function () {
    writeBaselineFile($this->baselinePath, [
        ['rule' => 'fixture-critical', 'reason' => 'Known legacy risk', 'expires' => $this->future],
    ]);

    $this->artisan(SafeguardCheckCommand::class, ['--ci' => true, '--no-baseline' => true])
        ->expectsOutputToContain('[FAIL] fixture-critical: Fixture critical finding')
        ->assertExitCode(1);

    // The override does not leak to later programmatic runs
    expect(AbstractFormatter::isAccepted($this->manager->runChecks()->get('fixture-critical')))->toBeTrue();
});

it('ignores the baseline when disabled in the configuration', function () {
    writeBaselineFile($this->baselinePath, [
        ['rule' => 'fixture-critical', 'reason' => 'Known legacy risk', 'expires' => $this->future],
    ]);
    config(['safeguard.baseline.enabled' => false]);

    $this->artisan(SafeguardCheckCommand::class, ['--ci' => true])
        ->assertExitCode(1);
});

it('uses the baseline file given with --baseline', function () {
    $custom = $this->baselineDir.'/custom-baseline.json';
    writeBaselineFile($custom, [
        ['rule' => 'fixture-critical', 'reason' => 'Custom file', 'expires' => $this->future],
    ]);

    $this->artisan(SafeguardCheckCommand::class, ['--ci' => true, '--baseline' => $custom])
        ->expectsOutputToContain('[ACCEPTED] fixture-critical')
        ->assertExitCode(0);
});

it('fails clearly when the --baseline file does not exist', function () {
    $this->artisan(SafeguardCheckCommand::class, ['--ci' => true, '--baseline' => $this->baselineDir.'/missing.json'])
        ->expectsOutputToContain('Baseline file not found')
        ->assertExitCode(1);
});

it('fails clearly when the configured baseline file is invalid', function () {
    File::put($this->baselinePath, json_encode(['accepted' => [['rule' => 'fixture-critical', 'reason' => 'x', 'expires' => 'soon']]]));

    $this->artisan(SafeguardCheckCommand::class)
        ->expectsOutputToContain('invalid "expires" date')
        ->assertExitCode(1);
});

it('applies the baseline to environment-specific checks', function () {
    config(['safeguard.environments.testing' => ['fixture-critical']]);
    writeBaselineFile($this->baselinePath, [
        ['rule' => 'fixture-critical', 'reason' => 'Known legacy risk', 'expires' => $this->future],
    ]);

    $this->artisan(SafeguardCheckCommand::class, ['--ci' => true, '--env-rules' => true])
        ->expectsOutputToContain('[ACCEPTED] fixture-critical')
        ->assertExitCode(0);
});

it('does not annotate passing rules listed in the baseline', function () {
    $this->manager->setBaseline(Baseline::fromArray([
        ['rule' => 'fixture-passing', 'reason' => 'Not needed anymore', 'expires' => $this->future],
    ]));

    $results = $this->manager->runChecks();

    expect($results->get('fixture-passing'))->not->toHaveKey('baseline')
        ->and($results->get('fixture-critical'))->not->toHaveKey('baseline');
});

it('excludes accepted findings from errors in summaries and reports', function () {
    $this->manager->setBaseline(Baseline::fromArray([
        ['rule' => 'fixture-critical', 'reason' => 'Known <legacy> risk', 'expires' => $this->future],
    ]));
    $results = $this->manager->runChecks();

    expect(AbstractFormatter::summarize($results))->toBe(['total' => 2, 'passed' => 1, 'errors' => 0, 'warnings' => 0])
        ->and(AbstractFormatter::baselineSummary($results))->toBe(['accepted' => 1, 'expired' => 0]);

    $json = json_decode((new JsonFormatter)->format($results, 'testing'), true);
    $accepted = collect($json['results'])->firstWhere('rule', 'fixture-critical');

    expect($json['status'])->toBe('passed')
        ->and($json['baseline'])->toBe(['accepted' => 1, 'expired' => 0])
        ->and($accepted['status'])->toBe('accepted')
        ->and($accepted['baseline'])->toBe(['status' => 'accepted', 'reason' => 'Known <legacy> risk', 'expires' => $this->future]);

    $document = simplexml_load_string((new JunitFormatter)->format($results, 'testing'));
    $testcase = $document->testsuite->testcase[1];

    expect((string) $document['failures'])->toBe('0')
        ->and((string) $document->testsuite['skipped'])->toBe('1')
        ->and(isset($testcase->failure))->toBeFalse()
        ->and((string) $testcase->skipped['message'])->toBe("Accepted in baseline until {$this->future}: Known <legacy> risk");

    $html = (new HtmlFormatter)->format($results, 'testing');

    expect($html)->toContain('badge-accepted')
        ->and($html)->toContain('Known &lt;legacy&gt; risk')
        ->and($html)->not->toContain('Known <legacy> risk');
});

it('keeps expired findings as failures in reports', function () {
    $this->manager->setBaseline(Baseline::fromArray([
        ['rule' => 'fixture-critical', 'reason' => 'Old risk', 'expires' => $this->past],
    ]));
    $results = $this->manager->runChecks();

    expect(AbstractFormatter::summarize($results)['errors'])->toBe(1)
        ->and(AbstractFormatter::baselineSummary($results))->toBe(['accepted' => 0, 'expired' => 1]);

    $json = json_decode((new JsonFormatter)->format($results, 'testing'), true);
    $expired = collect($json['results'])->firstWhere('rule', 'fixture-critical');

    expect($json['status'])->toBe('failed')
        ->and($expired['status'])->toBe('failed')
        ->and($expired['baseline']['status'])->toBe('expired');

    $document = simplexml_load_string((new JunitFormatter)->format($results, 'testing'));

    expect((string) $document['failures'])->toBe('1')
        ->and((string) $document->testsuite->testcase[1]->failure)->toContain("Baseline exception expired on {$this->past}: Old risk");

    expect((new HtmlFormatter)->format($results, 'testing'))->toContain('baseline-expired');
});

it('exits with 0 for formatted output when every failure is accepted', function () {
    writeBaselineFile($this->baselinePath, [
        ['rule' => 'fixture-critical', 'reason' => 'Known legacy risk', 'expires' => $this->future],
    ]);

    expect(Artisan::call('safeguard:check', ['--format' => 'json']))->toBe(0)
        ->and(Artisan::call('safeguard:check', ['--format' => 'json', '--no-baseline' => true]))->toBe(1);
});
