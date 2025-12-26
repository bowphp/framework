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

        // The redis connection
        "redis" => [
            'driver' => 'redis',
            'database' => app_env('REDIS_CACHE_DB', 5),
            "prefix" => "__app__",
        ]
    ]
];
