<?php

return [
    /**
     * Repertoire de log
     */
    'log' => TESTING_RESOURCE_BASE_DIRECTORY,

    /**
     * Store location
     */
    'disk' => [
        'mount' => 'storage',
        'path' => [
            'storage' => TESTING_RESOURCE_BASE_DIRECTORY,
            'public' => TESTING_RESOURCE_BASE_DIRECTORY,
        ]
    ],

    'services' => [
        /**
         * FTP configuration
         */
        'ftp' => [
            "driver" => "ftp",
            'hostname' => app_env('FTP_HOST', '127.0.0.1'),
            'password' => app_env('FTP_PASSWORD', 'password'),
            'username' => app_env('FTP_USERNAME', 'username'),
            'port'     => app_env('FTP_PORT', 21),
            'root' => app_env('FTP_ROOT'), // Start directory
            'tls' => app_env('FTP_SSL', false), // `true` enable the secure connexion.
            'timeout' => app_env('FTP_TIMEOUT', 90) // Temps d'attente de connection
        ],

        /**
         * S3 configuration
         */
        's3' => [
            "driver" => "s3",
            'credentials' => [
                'key'    => getenv('AWS_KEY'),
                'secret' => getenv('AWS_SECRET'),
            ],
            'bucket' => getenv('AWS_S3_BUCKET'),
            'region' => 'us-east-1',
            'version' => 'latest'
        ]
    ],
];
