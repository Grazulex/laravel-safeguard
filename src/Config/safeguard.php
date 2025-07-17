<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | State Machines Path
    |--------------------------------------------------------------------------
    |
    | The path where your state machine YAML definitions are stored.
    | This can be an absolute path or relative to your Laravel base path.
    |
    */
    'state_machines_path' => database_path('state_machines'),

    /*
    |--------------------------------------------------------------------------
    | Definitions Path
    |--------------------------------------------------------------------------
    |
    | The path where your state machine YAML definitions are stored.
    | Used by list, show, export and validate commands.
    |
    */
    'definitions_path' => resource_path('statemachines'),

    /*
    |--------------------------------------------------------------------------
    | Generated Code Path
    |--------------------------------------------------------------------------
    |
    | The path where generated PHP classes (guards, actions, models) will be stored.
    | Used by the generate command.
    |
    */
    'generated_code_path' => app_path('StateMachines'),

    /*
    |--------------------------------------------------------------------------
    | Default State Field
    |--------------------------------------------------------------------------
    |
    | The default field name to use for storing state on your models.
    | This can be overridden in individual state machine definitions.
    |
    */
    'default_state_field' => 'state',

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    |
    | Enable or disable state machine events.
    |
    */
    'events' => [
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | History Tracking
    |--------------------------------------------------------------------------
    |
    | Enable automatic tracking of state transitions.
    |
    */
    'history' => [
        'enabled' => false,
        'table' => 'state_machine_history',
    ],
];
