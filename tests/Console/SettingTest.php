<?php

namespace Bow\Tests\Console;

use Bow\Configuration\Loader as ConfigurationLoader;
use Bow\Console\Setting;
use Bow\Tests\Config\TestingConfiguration;

class SettingTest extends \PHPUnit\Framework\TestCase
{
    private static ConfigurationLoader $kernel;

    private static Setting $setting;

    public static function setUpBeforeClass(): void
    {
        static::$kernel = TestingConfiguration::getConfig();
        static::$setting = new Setting(static::$kernel->getBasePath());
    }

    /**
     * @dataProvider get_the_directories
     */
    public function test_set_the_model_directory(string $method, string $directory)
    {
        $model_directory = TESTING_RESOURCE_BASE_DIRECTORY . $directory;

        $set_method = 'set' . ucfirst($method) . 'Directory';
        $get_method = 'get' . ucfirst($method) . 'Directory';

        static::$setting->{$set_method}($model_directory);

        $this->assertNotNull(static::$setting->{$get_method}());
        $this->assertEquals($model_directory, static::$setting->{$get_method}());
    }

    public function test_set_the_validation_directory()
    {
        $model_directory = TESTING_RESOURCE_BASE_DIRECTORY . '/app/Validations';
        static::$setting->setValidationDirectory($model_directory);

        $this->assertNotNull(static::$setting->getValidationDirectory());
        $this->assertEquals($model_directory, static::$setting->getValidationDirectory());
    }

    public function get_the_directories()
    {
        return [
            ["model", "/app/Models"],
            ["middleware", "/app/Middlewares"],
            ["validation", "/app/Validations"],
            ["Package", "/app/Configurations"],
            ["controller", "/app/Controllers"],
            ["migration", "/app/Migrations"],
            ["exception", "/app/Exceptions"],
            ["service", "/app/Services"],
            ["Event", "/app/Events"],
            ["EventListener", "/app/Listeners"],
            ["producer", "/app/Producers"],
            ["command", "/app/Commands"],
            ["seeder", "/seeders"],
            ["component", "/frontend"],
            ["config", "/config"],
            ["public", "/public"],
        ];
    }
}
