<?php

namespace Bow\Tests\Support;

use Bow\Support\Env;
use Bow\Tests\Config\TestingConfiguration;

class EnvTest extends \PHPUnit\Framework\TestCase
{
    private Env $env;

    public static function setUpBeforeClass(): void
    {
        // Other test classes may have already booted Env with a different
        // (or empty) config; reset so this suite's env.json actually loads.
        Env::reset();
        Env::configure(__DIR__ . '/../Config/stubs/env.json');
    }

    public function setUp(): void
    {
        $this->env = Env::getInstance();
    }

    public function test_is_loaded()
    {
        $this->assertEquals($this->env->isLoaded(), true);
    }

    public function test_get()
    {
        $this->assertEquals($this->env->get('APP_NAME'), 'papac');
        $this->assertNull($this->env->get('LAST_NAME'));
        $this->assertEquals($this->env->get('SINCE', date('Y')), date('Y'));
    }

    public function test_set()
    {
        $this->env->set('APP_NAME', 'bow framework');

        $this->assertNotEquals($this->env->get('APP_NAME'), 'papac');
        $this->assertEquals($this->env->get('APP_NAME'), 'bow framework');
    }
}
