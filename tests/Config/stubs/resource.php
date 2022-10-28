<?php
/**
 * Fichier de configuration de la classe réssource
 */
return [
    /**
     * Store location
     */
    'disk' =>[
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
            'hostname' => app_env('FTP_HOST', 'localhost'),
            'password' => app_env('FTP_PASSWORD', '12345'),
            'username' => app_env('FTP_USERNAME', 'bob'),
            'port'     => app_env('FTP_PORT', 21),
            'root' => app_env('FTP_ROOT', sys_get_temp_dir()), // Start directory
            'tls' => app_env('FTP_SSL', false), // `true` enable the secure connexion.
            'timeout' => app_env('FTP_TIMEOUT', 90) // Temps d'attente de connection
        ],

        /**
         * S3 configuration
         */
        's3' => [
            'credentials' => [
                'key'    => '',
                'secret' => '',
            ],
            'bucket' => '',
            'region' => '',
            'version' => 'latest'
        ]
    ],


    /**
     * Repertoire de log
     */
    'log' => TESTING_RESOURCE_BASE_DIRECTORY,

    /**
     * Repertoure de cache
     */
    'cache' => TESTING_RESOURCE_BASE_DIRECTORY,
];
