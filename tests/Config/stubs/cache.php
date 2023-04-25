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
            'connection' => 'cache',
            'lock_connection' => 'default',
        ]
    ]
];
