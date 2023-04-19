<?php

namespace Bow\Testing;

use Bow\Configuration\Loader as ConfigurationLoader;

class KernelTesting extends ConfigurationLoader
{
    public static array $configurations = [];
    public static array $events = [];
    public static array $middlewares = [];

    /**
     * @inheritDoc
     */
    public function configurations(): array
    {
        return static::$configurations;
    }

    /**
     * @inheritDoc
     */
    public function events(): array
    {
        return static::$events;
    }

    /**
     * @inheritDoc
     */
    public function middlewares(): array
    {
        return static::$middlewares;
    }
}
