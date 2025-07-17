<?php

declare(strict_types=1);

use Grazulex\LaravelSafeguard\Rules\Configuration\AppKeyIsSet;
use Grazulex\LaravelSafeguard\Rules\Environment\AppDebugFalseInProduction;
use Grazulex\LaravelSafeguard\Services\SafeguardManager;

it('can register and retrieve security rules', function () {
    $manager = new SafeguardManager();
    $rule = new AppKeyIsSet();

    $manager->registerRule($rule);

    expect($manager->getRules())
        ->toHaveCount(1)
        ->toContain($rule);

    expect($manager->getRule('app-key-is-set'))
        ->toBe($rule);
});

it('can get enabled rules from configuration', function () {
    config(['safeguard.rules' => [
        'app-key-is-set' => true,
        'app-debug-false-in-production' => false,
    ]]);

    $manager = new SafeguardManager();
    $manager->registerRule(new AppKeyIsSet());
    $manager->registerRule(new AppDebugFalseInProduction());

    $enabledRules = $manager->getEnabledRules();

    expect($enabledRules)
        ->toHaveCount(1);

    expect($enabledRules->first()->id())
        ->toBe('app-key-is-set');
});

it('can run security checks', function () {
    config(['safeguard.rules' => [
        'app-key-is-set' => true,
    ]]);

    $manager = new SafeguardManager();
    $manager->registerRule(new AppKeyIsSet());

    $results = $manager->runChecks();

    expect($results)
        ->toHaveCount(1);

    $result = $results->first();
    expect($result)
        ->toHaveKey('rule')
        ->toHaveKey('description')
        ->toHaveKey('result');

    expect($result['rule'])->toBe('app-key-is-set');
});
