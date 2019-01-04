<?php

namespace Bow\Storage\Contracts;

interface ServiceInterface implements FilesystemInterface
{
    /**
     * Configure serivice
     *
     * @param array $config
     */
    public static function configure(array $config);
}
