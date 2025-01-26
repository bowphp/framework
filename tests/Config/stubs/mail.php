<?php

return [
    'driver' => 'smtp',
    'charset' => 'utf8',

    'smtp' => [
        'hostname' => 'localhost',
        'username' => 'test@test.dev',
        'password' => null,
        'port' => 1025,
        'tls' => false,
        'ssl' => false,
        'timeout' => 150,
    ],

    'mail' => [
        'default' => 'contact',
        'froms' => [
            'contact' => [
                'address' => app_env('MAIL_FROM_EMAIL'),
                'name' => app_env('MAIL_FROM_NAME')
            ],
            'info' => [
                'address' => 'info@exemple.com',
                'username' => 'Address information'
            ]
        ]
    ]
];
