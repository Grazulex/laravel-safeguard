<?php

declare(strict_types=1);

it('can create basic configuration', function () {
    $config = include __DIR__.'/../../src/Config/safeguard.php';

    expect($config)
        ->toBeArray();
});

it('config path is accessible', function () {
    expect(file_exists(__DIR__.'/../../src/Config/safeguard.php'))
        ->toBeTrue();
});

it('config has expected structure', function () {
    $config = include __DIR__.'/../../src/Config/safeguard.php';

    expect($config)
        ->toBeArray()
        ->toHaveKey('state_machines_path')
        ->toHaveKey('default_state_field')
        ->toHaveKey('events')
        ->toHaveKey('history');
});
