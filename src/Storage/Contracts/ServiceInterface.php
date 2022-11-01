<?php

declare(strict_types=1);

namespace Bow\Storage\Contracts;

interface ServiceInterface extends FilesystemInterface
{
    /**
     * Configure service
     *
     * @param array $config
     */
    public static function configure(array $config): FilesystemInterface;
}
