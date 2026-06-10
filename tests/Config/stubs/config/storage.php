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
            'hostname' => app_env('FTP_HOST', 'localhost'),
            'password' => app_env('FTP_PASSWORD', 'password'),
            'username' => app_env('FTP_USERNAME', 'username'),
            'port' => app_env('FTP_PORT', 21),
            'root' => app_env('FTP_ROOT', '/tmp'), // Start directory
            'tls' => app_env('FTP_SSL', false), // `true` enable the secure connexion.
            'timeout' => app_env('FTP_TIMEOUT', 90) // Temps d'attente de connection
        ],

        /**
         * S3 configuration
         * Supports both AWS S3 and MinIO (S3-compatible storage)
         */
        's3' => [
            "driver" => "s3",
            'credentials' => [
                // `?:` so an unset env (which CI exposes as an empty string)
                // falls back to the local docker-compose MinIO defaults.
                'key' => app_env('AWS_KEY') ?: 'minioadmin',
                'secret' => app_env('AWS_SECRET') ?: 'minioadmin',
            ],
            'bucket' => app_env('AWS_S3_BUCKET') ?: 'tests',
            'region' => app_env('AWS_REGION') ?: 'us-east-1',
            'version' => 'latest',
            // MinIO configuration. Defaults target the local docker-compose
            // MinIO service; override via env for a real AWS S3 endpoint.
            'endpoint' => app_env('AWS_ENDPOINT') ?: 'http://127.0.0.1:9000',
            'use_path_style_endpoint' => true, // Set to true for MinIO
        ]
    ],
];
