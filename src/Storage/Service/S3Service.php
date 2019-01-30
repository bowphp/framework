<?php

namespace Bow\Storage\Service;

use Aws\S3\S3Client;
use Bow\Storage\Contracts\ServiceInterface;
use Bow\Storage\Contracts\FilesystemInterface;

class S3Service extends FilesystemInterface implements ServiceInterface
{
    /**
     * The S3Service instance
     *
     * @var S3Service
     */
    private static $instance;

    /**
     * S3Service constructor
     *
     * @param array $config
     */
    private function __consturct(array $config)
    {
        $this->config = $config;

        $this->client = new S3Client;
    }

    /**
     * S3Service Configuration
     *
     * @param array $config
     */
    public static function config(array $config)
    {
        if (is_null(static::$instance)) {
            static::$instance = new static($config);
        }

        return static::$instance;
    }

    /**
     * Get S3Service
     *
     * @return S3Service
     */
    public static function getInstance()
    {
        return static::$instance;
    }
}
