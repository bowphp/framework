<?php

namespace Bow\Test;

use \Bow\Cache\Cache;

class CacheTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        parent::setUp();

        Cache::confirgure(__DIR__.'/data/cache/bow');
    }



    public function test_CreateCache()
    {
        $r = Cache::add('name', 'Dakia');

        $this->assertEquals($r, true);
    }

    public function test_GetCache()
    {
        $this->assertEquals(Cache::get('name'), 'Dakia');
    }

    public function test_AddWithCallbackCache()
    {
        $r = Cache::add('lastname', function () {
            return 'Franck';
        });

        $r = $r && Cache::add('age', function () {
                return 25;
        }, 20000);

        $this->assertEquals($r, true);
    }

    public function test_GetCallbackCache()
    {
        $this->assertEquals(Cache::get('lastname'), 'Franck');

        $this->assertEquals(Cache::get('age'), 25);
    }

    public function test_AddArrayCache()
    {
        $r = Cache::add('address', [
            'tel' => "49929598",
            'city' => "Abidjan",
            'country' => "Cote d'ivoire"
        ]);

        $this->assertEquals($r, true);
    }

    public function test_GetArrayCache()
    {
        $r = Cache::get('address');

        $this->assertEquals(true, is_array($r));

        $this->assertEquals(count($r), 3);

        $this->assertArrayHasKey('tel', $r);

        $this->assertArrayHasKey('city', $r);

        $this->assertArrayHasKey('country', $r);
    }

    public function test_has()
    {
        $r1 = Cache::has('name');

        $r2 = Cache::has('jobs');

        $this->assertEquals(true, $r1);

        $this->assertEquals(false, $r2);
    }

    public function test_forget()
    {
        Cache::forget('address');

        $r1 = Cache::forget('name');

        $this->assertEquals(true, $r1);

        $this->assertEquals(Cache::get('name', false), false);
    }

    public function test_forget_empty()
    {
        $r1 = Cache::forget('name');

        $this->assertEquals(false, $r1);
    }

    public function test_time_of_empty()
    {
        $r1 = Cache::timeOf('lastname');

        $this->assertEquals('+', $r1);
    }

    public function test_time_of_empty_2()
    {
        $r1 = Cache::timeOf('address');

        $this->assertEquals(false, $r1);
    }

    public function test_time_of_empty_3()
    {
        $r1 = Cache::timeOf('age');

        $this->assertEquals(is_int($r1), true);
    }

    public function test_can_add_many_data_at_the_same_time_in_the_cache()
    {
        $passes = Cache::addMany(['name' => 'Doe', 'first_name' => 'John']);

        $this->assertEquals($passes, true);
    }

    public function test_can_retrieve_multiple_cache_stored()
    {
        Cache::addMany(['name' => 'Doe', 'first_name' => 'John']);

        $this->assertEquals(Cache::get('name'), 'Doe');
        $this->assertEquals(Cache::get('first_name'), 'John');
    }

    public function test_clear_cache()
    {
        Cache::addMany(['name' => 'Doe', 'first_name' => 'John']);

        $this->assertEquals(Cache::get('first_name'), 'John');
        $this->assertEquals(Cache::get('name'), 'Doe');

        Cache::clear();

        $this->assertNull(Cache::get('name'));
        $this->assertNull(Cache::get('first_name'));
    }
}
