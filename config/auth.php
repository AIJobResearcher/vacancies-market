<?php

return [

    'defaults' => [
        'guard' => env('AUTH_GUARD'),
        'passwords' => env('AUTH_PASSWORD_BROKER'),
    ],

    'guards' => [],

    'providers' => [],

    'passwords' => [],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
