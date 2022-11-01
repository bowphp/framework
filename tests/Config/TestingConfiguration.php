<?php

namespace Bow\Tests\Config;

use Bow\Configuration\Loader as ConfigurationLoader;

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
     * Get the configuration for testing
     *
     * @return ConfigurationLoader
     */
    public static function getConfig(): ConfigurationLoader
    {
        return ConfigurationLoader::configure(__DIR__.'/stubs');
    }
}
