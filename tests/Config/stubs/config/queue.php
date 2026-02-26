<?php

return [
    /**
     * The defaut connexion
     */
    "default" => "sync",

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
         * The redis connexion
         */
        "redis" => [
            "database" => 1,
            "block_timeout" => 5,
        ],

        /**
         * The rabbitmq connection
         */
        "rabbitmq" => [
            'host' => 'localhost',
            'port' => 5672,
            'user' => 'guest',
            'password' => 'guest',
            'vhost' => '/',
            'queue' => 'default',
        ],

        /**
         * The kafka connection
         */
        "kafka" => [
            'host' => 'localhost',
            'port' => 9092,
            'topic' => 'default',
            'group_id' => 'bow_queue_group',
            'auto_offset_reset' => 'earliest',
            'enable_auto_commit' => 'true',
        ],

        /**
         * The sqs connexion
         */
        "sqs" => [
            'profile' => 'default',
            'region' => 'ap-south-1',
            'version' => 'latest',
            'url' => getenv("AWS_SQS_URL"),
            'credentials' => [
                'key' => getenv('AWS_KEY'),
                'secret' => getenv('AWS_SECRET'),
            ],
        ],

        /**
         * The database connexion
         */
        "database" => [
            'table' => "queues",
        ]
    ]
];
