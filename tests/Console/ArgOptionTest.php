<?php

namespace Bow\Tests\Console;

use Bow\Console\ArgOption;
use Bow\Support\Collection;

class ArgOptionTest extends \PHPUnit\Framework\TestCase
{
    public function test_not_parameters_passed()
    {
        $GLOBALS["argv"] = ["bow"];
        $arg = new ArgOption;

        $this->assertNull($arg->getCommand());
        $this->assertNull($arg->getAction());
        $this->assertNull($arg->getTarget());
    }

    public function test_one_arg_passed_a_command_only()
    {
        $GLOBALS["argv"] = ["bow", "run"];
        $arg = new ArgOption;

        $this->assertNotNull($arg->getCommand());
        $this->assertNull($arg->getAction());
        $this->assertNull($arg->getTarget());

        $this->assertEquals($arg->getCommand(), "run");
    }

    public function test_one_arg_passed()
    {
        $GLOBALS["argv"] = ["bow", "run:server"];
        $arg = new ArgOption;

        $this->assertNotNull($arg->getCommand());
        $this->assertNotNull($arg->getAction());
        $this->assertNull($arg->getTarget());

        $this->assertEquals($arg->getCommand(), "run");
        $this->assertEquals($arg->getAction(), "server");
    }

    public function test_get_target()
    {
        $GLOBALS["argv"] = ["bow", "command:action", "target"];
        $arg = new ArgOption;

        $this->assertNotNull($arg->getTarget());
        $this->assertEquals($arg->getTarget(), "target");
    }

    public function test_get_options_with_target_passed()
    {
        $GLOBALS["argv"] = ["bow", "command:action", "target", "--class=TestClass::class"];
        $arg = new ArgOption;

        $this->assertNotNull($arg->getTarget());
        $this->assertEquals($arg->getTarget(), "target");
        $this->assertNull($arg->getParameter("--not-found"));
        $this->assertEquals($arg->getParameter("--class"), "TestClass::class");

        $this->assertInstanceOf(Collection::class, $arg->getParameters());
        $this->assertTrue($arg->getParameters()->has("--class"));
        $this->assertFalse($arg->getParameters()->has("--not-found"));
    }

    public function test_get_options_as_collection()
    {
        $GLOBALS["argv"] = ["bow", "command:action", "target", "--class=TestClass::class"];
        $arg = new ArgOption;

        $this->assertInstanceOf(Collection::class, $arg->getParameters());
        $this->assertTrue($arg->getParameters()->has("--class"));
        $this->assertFalse($arg->getParameters()->has("--not-found"));
    }
}
