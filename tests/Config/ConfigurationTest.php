<?php

namespace Bow\Tests\Config;

use Bow\Configuration\Loader as ConfigurationLoader;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    private ConfigurationLoader $config;

    public function setUp(): void
    {
        $this->config = ConfigurationLoader::configure(__DIR__.'/stubs');
    }

    public function test_instance_of_loader()
    {
        $this->assertInstanceOf(ConfigurationLoader::class, $this->config);
    }
}
