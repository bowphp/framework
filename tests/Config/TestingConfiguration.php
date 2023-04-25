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
     * Set the loading configuration
     *
     * @param array $configurations
     * @return void
     */
    public static function withConfiguations(array $configurations): void
    {
        KernelTesting::$configurations = $configurations;
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
