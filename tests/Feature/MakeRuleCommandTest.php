<?php

declare(strict_types=1);

use Grazulex\LaravelSafeguard\Console\Commands\MakeRuleCommand;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->rulesPath = app_path('Safeguard/Rules');
});

afterEach(function () {
    if (File::exists($this->rulesPath)) {
        File::deleteDirectory($this->rulesPath);
    }
});

it('creates a new rule file', function () {
    $this->artisan(MakeRuleCommand::class, ['name' => 'TestRule'])
        ->expectsOutput('Rule created successfully.')
        ->assertExitCode(0);

    $ruleFile = $this->rulesPath.'/TestRule.php';

    expect(File::exists($ruleFile))->toBeTrue();

    $content = File::get($ruleFile);
    expect($content)->toContain('class TestRule')
        ->toContain('implements SafeguardRule')
        ->toContain('public function id(): string')
        ->toContain('public function description(): string')
        ->toContain('public function check(): SafeguardResult');
});

it('creates a rule with custom severity', function () {
    $this->artisan(MakeRuleCommand::class, [
        'name' => 'CustomRule',
        '--severity' => 'warning',
    ])->assertExitCode(0);

    $ruleFile = $this->rulesPath.'/CustomRule.php';
    $content = File::get($ruleFile);

    expect($content)->toContain("return 'warning';");
});

it('validates severity option', function () {
    $this->artisan(MakeRuleCommand::class, [
        'name' => 'InvalidRule',
        '--severity' => 'invalid',
    ])->expectsOutput('Invalid severity. Must be one of: error, warning, info')
        ->assertExitCode(1);
});
