<?php

return [
    "default" => "file",

    "stores" => [
        "file" => [
            "driver" => "file",
            "path" => TESTING_RESOURCE_BASE_DIRECTORY
        ],

        "redis" => [
            'driver' => 'redis',
            'url' => app_env('REDIS_URL'),
            'host' => '127.0.0.1',
            'port' => 6379,
            'timeout' => 2.5,
            'ssl' => false,
            'username' => app_env('REDIS_USERNAME'),
            'password' => app_env('REDIS_PASSWORD'),
            'database' => app_env('REDIS_CACHE_DB', '1'),
        ]
    ]
];
