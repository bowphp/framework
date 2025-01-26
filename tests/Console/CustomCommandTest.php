<?php

namespace Bow\Tests\Console\Stubs;

use Bow\Console\Console;
use Bow\Console\Setting;

class CustomCommandTest extends \PHPUnit\Framework\TestCase
{
    private static Console $console;

    public static function setUpBeforeClass(): void
    {
        // This define the command like this `php bow command`
        $GLOBALS["argv"] = ["command"];

        $setting = new Setting(TESTING_RESOURCE_BASE_DIRECTORY);
        static::$console = new Console($setting);
    }

    public function test_create_the_custom_command_from_static_calling()
    {
        Console::register("command", CustomCommand::class);
        static::$console->call("command");

        $content = $this->getFileContent();
        $this->assertEquals($content, 'ok');

        $this->clearFile();
    }

    public function test_create_the_custom_command_from_instance_calling()
    {
        static::$console->addCommand("command", CustomCommand::class);
        static::$console->call("command");

        $content = $this->getFileContent();
        $this->assertEquals($content, 'ok');

        $this->clearFile();
    }

    protected function clearFile()
    {
        file_put_contents(TESTING_RESOURCE_BASE_DIRECTORY . '/test_custom_command.txt', '');
    }

    protected function getFileContent()
    {
        return file_get_contents(TESTING_RESOURCE_BASE_DIRECTORY . '/test_custom_command.txt');
    }
}
