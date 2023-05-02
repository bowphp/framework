<?php

return [
    "default" => "file",

    "stores" => [
        "file" => [
            "driver" => "file",
            "path" => TESTING_RESOURCE_BASE_DIRECTORY
        ],

        "database" => [
            "driver" => "database",
            "connection" => "mysql",
            "table" => "caches",
        ],

        "redis" => [
            'driver' => 'redis',
            'host' => app_env('REDIS_HOSTNAME', '127.0.0.1'),
            'port' => app_env('REDIS_PORT', 6379),
            'timeout' => 2.5,
            'ssl' => false,
            'username' => app_env('REDIS_USERNAME'),
            'password' => app_env('REDIS_PASSWORD'),
            'database' => app_env('REDIS_CACHE_DB', '1'),
            "prefix" => "__app__",
            'slave' => [
                'host' => app_env('REDIS_HOSTNAME', '127.0.0.1'),
                'port' => app_env('REDIS_PORT', 6379),
                'timeout' => 2.5,
                'ssl' => false,
                'username' => app_env('REDIS_USERNAME'),
                'password' => app_env('REDIS_PASSWORD'),
                'database' => app_env('REDIS_CACHE_DB', '1'),
            ]
        ]
    ]
];
