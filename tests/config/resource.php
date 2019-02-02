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
            'storage' => __DIR__.'/../data/storage',
            'public' => __DIR__.'/../data/public',
        ]
    ],

    /**
     * Repertoire de log
     */
    'log' => __DIR__.'/../data/logs',

    /**
     * Repertoure de cache
     */
    'cache' => __DIR__.'/../data/cache',

    'services' => [
        /**
         * FTP configuration
         */
        'ftp' => [
            'hostname' => 'test.rebex.net',
            'password' => 'password',
            'username' => 'demo',
            'port'     => 21,
            'root' => '', // Start directory
            'tls' => false, // `true` enable the secure connexion.
            'timeout' => 90 // Temps d'attente de connection
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
    ]
];
