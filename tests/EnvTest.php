<?php

use \Bow\View\View;
use \Bow\Support\Env;

class EnvTest extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (! file_exists(__DIR__.'/data/.env.json')) {
            file_put_contents(__DIR__.'/data/.env.json', json_encode(['NAME' => 'papac']));
        }

        Env::load(__DIR__.'/data/.env.json');
    }

    public function test_is_loaded()
    {
        $this->assertEquals(Env::isLoaded(), true);
    }

    public function test_get()
    {
        $this->assertEquals(Env::get('NAME'), 'papac');

        $this->assertNull(Env::get('LASTNAME'));

        $this->assertEquals(Env::get('SINCE', date('Y')), date('Y'));
    }

    public function test_set()
    {
        Env::set('NAME', 'bow framework');

        $this->assertNotEquals(Env::get('NAME'), 'papac');

        $this->assertEquals(Env::get('NAME'), 'bow framework');
    }
}
