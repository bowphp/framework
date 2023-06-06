<?php

namespace Bow\Tests\Database;

use Bow\Database\Redis;
use Bow\Tests\Config\TestingConfiguration;

class RedisTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $config = TestingConfiguration::getConfig();
    }

    public function test_create_cache()
    {
        $result = Redis::get('name', 'Dakia');

        $this->assertEquals($result, true);
    }

    public function test_get_cache()
    {
        Redis::set('lastname', 'papac');

        $this->assertNull(Redis::get('name'));
        $this->assertEquals(Redis::get('lastname'), "papac");
    }
}
