<?php

return [
    /**
     * The defaut connexion
     */
    "default" => "beanstalkd",

    /**
     * The queue drive connection
     */
    "connections" => [
        /**
         * The sync connexion
         */
        "sync" => [
            "directory" => TESTING_RESOURCE_BASE_DIRECTORY . "/queue"
        ],

        /**
         * The beanstalkd connexion
         */
        "beanstalkd" => [
            "hostname" => "127.0.0.1",
            "port" => 11300,
            "timeout" => 10,
        ],

        /**
         * The sqs connexion
         */
        "sqs" => [
            'profile' => 'default',
            'region' => 'ap-south-1',
            'version' => 'latest',
            'url' => app_env("AWS_SQS_URL"),
            'credentials' => [
                'key' => app_env('AWS_KEY'),
                'secret' => app_env('AWS_SECRET'),
            ],
        ],
        
        /**
         * The sqs connexion
         */
        "database" => [
            'table' => "queues",
        ]
    ]
];
