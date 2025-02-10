<?php

return [
    'driver' => 'smtp',
    'charset' => 'utf8',

    'smtp' => [
        'hostname' => '192.168.1.3',
        'username' => 'test@test.dev',
        'password' => null,
        'port' => 1025,
        'tls' => false,
        'ssl' => false,
        'timeout' => 150,
    ],

    'mail' => [
        'default' => 'contact',
        'from' => [
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
