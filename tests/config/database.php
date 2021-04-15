<?php

return [
    'fetch' => \PDO::FETCH_OBJ,
    'default' => 'mysql',
    'connection' => [
        'mysql' => [
            'driver' => 'mysql',
            'hostname' => getenv('MYSQL_HOSTNAME'),
            'username' => getenv('MYSQL_USER'),
            'password' => getenv('MYSQL_PASSWORD'),
            'database' => getenv('MYSQL_DATABASE'),
            'charset'  => getenv('MYSQL_CHARSET'),
            'collation' => getenv('MYSQL_COLLATE') ? getenv('MYSQL_COLLATE') : 'utf8_unicode_ci',
            'port' => 3306,
            'socket' => null
        ],
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => ''
        ]
    ]
];
