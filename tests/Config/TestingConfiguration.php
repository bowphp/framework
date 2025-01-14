<?php

namespace Bow\Tests\Config;

use Bow\Configuration\Loader as ConfigurationLoader;
use Bow\Testing\KernelTesting;

class TestingConfiguration
{
    /**
     * TestingConfiguration constructor
     */
    public function __construct()
    {
        is_dir(TESTING_RESOURCE_BASE_DIRECTORY) || mkdir(TESTING_RESOURCE_BASE_DIRECTORY, 0777);
    }

    /**
     * Configure the testing
     *
     * @param array $configurations
     * @return void
     */
    public static function withConfigurations(array $configurations)
    {
        KernelTesting::withConfigurations($configurations);
    }

    /**
     * Configure the testing
     *
     * @param array $middlewares
     * @return void
     */
    public static function withMiddlewares(array $middlewares)
    {
        KernelTesting::withMiddlewares($middlewares);
    }

    /**
     * Set the loading events
     *
     * @param array $events
     * @return void
     */
    public static function withEvents(array $events): void
    {
        KernelTesting::withEvents($events);
    }

    /**
     * Get the configuration for testing
     *
     * @return ConfigurationLoader
     */
    public static function getConfig(): ConfigurationLoader
    {
        return KernelTesting::configure(__DIR__ . '/stubs');
    }
}
