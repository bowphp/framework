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
            'url' => app_env('SQS_URL', 'https://sqs.ap-south-1.amazonaws.com/242848748621/messaging'),
        ],

        /**
         * The sqs connexion
         */
        "database" => [
            'table' => "queue_jobs",
        ]
    ]
];
