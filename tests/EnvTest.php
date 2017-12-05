<?php

use \Bow\View\View;
use \Bow\Support\Env;

class EnvTest extends \PHPUnit\Framework\TestCase
{
	public static function setUpBeforeClass()
	{
		if (! file_exists(__DIR__.'/data/.env.json')) {
			file_put_contents(__DIR__.'/data/.env.json', json_encode(['NAME' => 'papac']));
		}
		Env::load(__DIR__.'/data/.env.json');
	}

	public function testIsLoaded()
	{
		$this->assertEquals(Env::isLoaded(), null);
	}

	public function testGet()
	{
		$this->assertEquals(Env::get('NAME'), 'papac');
		$this->assertNull(Env::get('LASTNAME'));
		$this->assertEquals(Env::get('SINCE', date('Y')), date('Y'));
	}

	public function testSet()
	{
		Env::set('NAME', 'bow framework');

		$this->assertNotEquals(Env::get('NAME'), 'papac');
		$this->assertEquals(Env::get('NAME'), 'bow framework');
	}
}