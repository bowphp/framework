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

    public function test_access_to_values()
    {
        $this->assertIsArray($this->config["stub"]);
        $this->assertNull($this->config["key_not_found"]);
        $this->assertEquals($this->config["stub"]["name"], "papac");
        $this->assertEquals($this->config["stub"]["sub"]["framework"], "bowphp");

        $this->assertEquals($this->config["stub.name"], "papac");
        $this->assertIsArray($this->config["stub.sub"]);
        $this->assertEquals($this->config["stub.sub.framework"], "bowphp");
    }

    // public function test_set_config_values()
    // {
    //     $this->config["stub"]["name"] = "franck";
    //     $this->config["stub"]["sub"] = [
    //         "job" => "dev"
    //     ];
    //     $this->assertIsArray($this->config["stub"]);
    //     $this->assertNull($this->config["key_not_found"]);
    //     $this->assertEquals($this->config["stub"]["name"], "franck");
    //     $this->assertEquals($this->config["stub"]["sub"]["framework"], "bowphp");
    //     $this->assertEquals($this->config["stub"]["sub"]["job"], "dev");
    // }
}
// I want to rewrite the internal dotnotion for config loader
