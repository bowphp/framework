<?php

return [
    'fetch' => \PDO::FETCH_OBJ,
    'default' => 'mysql',
    'connections' => [
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
        'pgsql' => [
            'driver' => 'pgsql',
            'hostname' => "127.0.0.1",
            'username' => "postgres",
            'password' => "postgres",
            'database' => "postgres",
            'charset'  => "utf8",
            'prefix' => app_env('DB_PREFIX', ''),
            'port' => 5432,
            'socket' => null
        ],
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => ''
        ]
    ]
];
