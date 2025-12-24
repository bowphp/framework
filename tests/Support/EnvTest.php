<?php

namespace Bow\Tests\Support;

use Bow\Support\Env;

class EnvTest extends \PHPUnit\Framework\TestCase
{
    private Env $env;

    public static function setUpBeforeClass(): void
    {
        $env_filename = __DIR__ . '/stubs/env.json';

        if (!file_exists($env_filename)) {
            file_put_contents($env_filename, json_encode(['APP_NAME' => 'papac']));
        }

        Env::configure($env_filename);
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
