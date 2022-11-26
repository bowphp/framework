<?php

namespace Bow\Tests\Support;

use Bow\View\View;
use Bow\Support\Env;

class EnvTest extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        $env_filename = __DIR__.'/stubs/env.json';

        if (!file_exists($env_filename)) {
            file_put_contents($env_filename, json_encode(['APP_NAME' => 'papac']));
        }

        Env::load($env_filename);
    }

    public function test_is_loaded()
    {
        $this->assertEquals(Env::isLoaded(), true);
    }

    public function test_get()
    {
        $this->assertEquals(Env::get('APP_NAME'), 'papac');
        $this->assertNull(Env::get('LAST_NAME'));
        $this->assertEquals(Env::get('SINCE', date('Y')), date('Y'));
    }

    public function test_set()
    {
        Env::set('APP_NAME', 'bow framework');

        $this->assertNotEquals(Env::get('APP_NAME'), 'papac');
        $this->assertEquals(Env::get('APP_NAME'), 'bow framework');
    }
}
