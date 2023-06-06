<?php

namespace Bow\Testing;

use Bow\Configuration\Loader as ConfigurationLoader;

class KernelTesting extends ConfigurationLoader
{
    private static array $configurations = [];
    private static array $events = [];
    private static array $middlewares = [];

    /**
     * Set the loading configuration
     *
     * @param array $configurations
     * @return void
     */
    public static function withConfiguations(array $configurations): void
    {
        static::$configurations = $configurations;
    }

    /**
     * Set the loading events
     *
     * @param array $events
     * @return void
     */
    public static function withEvents(array $events): void
    {
        static::$events = $events;
    }

    /**
     * Set the loading middlewares
     *
     * @param array $middlewares
     * @return void
     */
    public static function withMiddlewares(array $middlewares): void
    {
        static::$middlewares = $middlewares;
    }

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
