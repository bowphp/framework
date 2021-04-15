<?php

require __DIR__."/../vendor/autoload.php";

Bow\Configuration\Loader::configure(__DIR__.'/config');

Bow\Container\Actionner::configure([], []);

Bow\Database\Database::configure([
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
            'database' => __DIR__ . '/data/database.sqlite',
            'prefix' => ''
        ]
    ]
]);
