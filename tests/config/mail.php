<?php
return [
    'driver' => 'smtp',
    'charset'  => 'utf8',
    // smtp authentification
    'smtp' => [
        'hostname' => 'localhost',
        'username' => 'test@test.dev',
        'password' => null,
        'port'     => 1025,
        'tls'      => false,
        'ssl'      => false,
        'timeout'  => 50,
    ]
];