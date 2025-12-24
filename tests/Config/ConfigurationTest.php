<?php

namespace Bow\Tests\Config;

use Bow\Support\Env;
use Bow\Configuration\EnvConfiguration;
use Bow\Configuration\Loader as ConfigurationLoader;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    private ConfigurationLoader $config;

    public function setUp(): void
    {
        Env::configure(__DIR__ . '/stubs/env.json');
        $this->config = ConfigurationLoader::configure(__DIR__ . '/stubs/config');
        $this->config->boot();
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

    public function test_access_with_dot_notation()
    {
        // Test simple dot notation access
        $this->assertEquals("papac", $this->config["stub.name"]);

        // Test nested dot notation access
        $this->assertEquals("bowphp", $this->config["stub.sub.framework"]);

        // Test partial dot notation returns array
        $this->assertIsArray($this->config["stub.sub"]);
        $this->assertArrayHasKey("framework", $this->config["stub.sub"]);
    }

    public function test_set_config_values()
    {
        // Set values using dot notation (array chaining not supported in ArrayAccess)
        $this->config["stub.name"] = "franck";
        $this->assertEquals("franck", $this->config["stub.name"]);

        // Set nested values using dot notation
        $this->config["stub.sub.job"] = "dev";
        $this->assertEquals("dev", $this->config["stub.sub.job"]);

        // Original values should still exist
        $this->assertEquals("bowphp", $this->config["stub.sub.framework"]);
    }

    public function test_set_config_values_with_dot_notation()
    {
        // Set simple value using dot notation
        $this->config["stub.name"] = "john";
        $this->assertEquals("john", $this->config["stub.name"]);
        $this->assertEquals("john", $this->config["stub"]["name"]);

        // Set nested value using dot notation
        $this->config["stub.sub.job"] = "developer";
        $this->assertEquals("developer", $this->config["stub.sub.job"]);

        // Add new nested path using dot notation
        $this->config["stub.location.city"] = "paris";
        $this->assertEquals("paris", $this->config["stub.location.city"]);
        $this->assertIsArray($this->config["stub.location"]);
    }

    public function test_overwrite_nested_array()
    {
        // Store original value
        $originalFramework = $this->config["stub.sub.framework"];
        $this->assertEquals("bowphp", $originalFramework);

        // Overwrite entire nested array using dot notation
        $this->config["stub.sub"] = [
            "job" => "dev",
            "skill" => "php"
        ];

        // Old value should be gone
        $subArray = $this->config["stub.sub"];
        $this->assertArrayNotHasKey("framework", $subArray);

        // New values should exist
        $this->assertEquals("dev", $this->config["stub.sub.job"]);
        $this->assertEquals("php", $this->config["stub.sub.skill"]);
    }

    public function test_offset_exists()
    {
        // Test top-level array notation
        $this->assertTrue(isset($this->config["stub"]));
        $this->assertFalse(isset($this->config["nonexistent"]));

        // Test dot notation (recommended way) - use values we know exist
        $this->assertTrue(isset($this->config["stub.name"]));

        // Test non-existent keys
        $this->assertFalse(isset($this->config["completely.nonexistent.path"]));
        $this->assertFalse(isset($this->config["stub.does.not.exist"]));
    }

    public function test_offset_unset()
    {
        // Set a test value first
        $this->config["test.unset.value"] = "temporary";
        $this->assertEquals("temporary", $this->config["test.unset.value"]);

        // Unset value using dot notation
        unset($this->config["test.unset.value"]);

        // Verify it's gone
        $this->assertNull($this->config["test.unset.value"]);
        $this->assertFalse(isset($this->config["test.unset.value"]));
    }

    public function test_unset_with_dot_notation()
    {
        // Set a nested test value with sibling
        $this->config["test.nested.value"] = "data";
        $this->config["test.nested.sibling"] = "other";
        $this->assertEquals("data", $this->config["test.nested.value"]);

        // Unset using dot notation
        unset($this->config["test.nested.value"]);

        // Verify it's gone
        $this->assertNull($this->config["test.nested.value"]);
        $this->assertFalse(isset($this->config["test.nested.value"]));

        // Parent level should still exist with sibling
        $this->assertIsArray($this->config["test.nested"]);
        $this->assertEquals("other", $this->config["test.nested.sibling"]);
    }

    public function test_null_value_returns_null()
    {
        $this->assertNull($this->config["nonexistent"]);
        $this->assertNull($this->config["stub.nonexistent"]);
        $this->assertNull($this->config["stub.sub.nonexistent"]);
    }

    public function test_invoke_method()
    {
        // Test getting value via invoke
        $result = ($this->config)("stub.name");
        $this->assertEquals("john", $result);

        // Test setting value via invoke
        ($this->config)("stub.name", "alice");
        $this->assertEquals("alice", $this->config["stub.name"]);
    }

    public function test_get_base_path()
    {
        $basePath = $this->config->getBasePath();
        $this->assertEquals(__DIR__ . '/stubs/config', $basePath);
        $this->assertIsString($basePath);
    }

    public function test_is_cli()
    {
        $isCli = $this->config->isCli();
        $this->assertTrue($isCli); // PHPUnit runs in CLI
        $this->assertIsBool($isCli);
    }

    public function test_get_instance()
    {
        $instance = ConfigurationLoader::getInstance();
        $this->assertInstanceOf(ConfigurationLoader::class, $instance);
        $this->assertSame($this->config, $instance);
    }

    public function test_singleton_pattern()
    {
        $config1 = ConfigurationLoader::getInstance();
        $config2 = ConfigurationLoader::getInstance();

        $this->assertSame($config1, $config2);
    }

    public function test_config_array_is_readonly_structure()
    {
        // Get array value
        $stubArray = $this->config["stub"];
        $this->assertIsArray($stubArray);

        // Modify the returned array
        $stubArray["modified"] = "value";

        // Original config should not be affected
        $this->assertArrayNotHasKey("modified", $this->config["stub"]);
    }

    public function test_deep_nested_access()
    {
        // Create deep nested structure
        $this->config["level1.level2.level3.level4"] = "deep_value";

        // Access through different notations
        $this->assertEquals("deep_value", $this->config["level1.level2.level3.level4"]);
        $this->assertEquals("deep_value", $this->config["level1"]["level2"]["level3"]["level4"]);

        // Access intermediate levels
        $this->assertIsArray($this->config["level1.level2.level3"]);
        $this->assertIsArray($this->config["level1"]["level2"]["level3"]);
    }
}
