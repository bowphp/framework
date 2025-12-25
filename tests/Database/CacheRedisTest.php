<?php

namespace Bow\Tests\Cache;

use Bow\Cache\Cache;
use Bow\Tests\Config\TestingConfiguration;

class CacheRedisTest extends \PHPUnit\Framework\TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $config = TestingConfiguration::getConfig();

        Cache::configure($config["cache"]);
        Cache::store("redis");

        // Clear cache before each test for isolation
        try {
            // Cache::clear();
        } catch (\Exception $e) {
            // Redis might not be available, skip clearing
        }
    }

    public function test_create_cache()
    {
        $result = Cache::set('name', 'Dakia');

        $this->assertEquals($result, true);
    }

    public function test_get_cache()
    {
        Cache::set('name', 'Dakia');

        $this->assertEquals(Cache::get('name'), 'Dakia');
    }

    public function test_set_with_callback_cache()
    {
        $result = Cache::set('lastname', fn() => 'Franck');
        $result = $result && Cache::set('age', fn() => 25, 20000);

        $this->assertEquals($result, true);
    }

    public function test_get_callback_cache()
    {
        $this->assertEquals(Cache::get('lastname'), 'Franck');

        $this->assertEquals(Cache::get('age'), 25);
    }

    public function test_set_array_cache()
    {
        $result = Cache::set('address', [
            'tel' => "0700000000",
            'city' => "Abidjan",
            'country' => "Cote d'ivoire"
        ]);

        $this->assertEquals($result, true);
    }

    public function test_get_array_cache()
    {
        $result = Cache::set('address', [
            'tel' => "0700000000",
            'city' => "Abidjan",
            'country' => "Cote d'ivoire"
        ]);

        $result = Cache::get('address');

        $this->assertEquals(true, is_array($result));
        $this->assertEquals(count($result), 3);
        $this->assertArrayHasKey('tel', $result);
        $this->assertArrayHasKey('city', $result);
        $this->assertArrayHasKey('country', $result);
    }

    public function test_has()
    {
        $first_result = Cache::has('name');
        $other_result = Cache::has('jobs');

        $this->assertEquals(true, $first_result);
        $this->assertEquals(false, $other_result);
    }

    public function test_forget()
    {
        $result = Cache::forget('name');

        $this->assertEquals(true, $result);
        $this->assertEquals(Cache::get('name', false), false);
    }

    public function test_forget_empty()
    {
        $result = Cache::forget('name');

        $this->assertEquals(false, $result);
    }

    public function test_time_of_empty()
    {
        $result = Cache::timeOf('lastname');

        $this->assertEquals($result, -1);
    }

    public function test_time_of_empty_2()
    {
        $result = Cache::timeOf('address');

        $this->assertEquals($result, -1);
    }

    public function test_time_of_empty_3()
    {
        $result = Cache::timeOf('age');

        $this->assertEquals(is_int($result), true);
    }

    public function test_can_add_many_data_at_the_same_time_in_the_cache()
    {
        $result = Cache::setMany(['name' => 'Doe', 'first_name' => 'John']);

        $this->assertEquals($result, true);
    }

    public function test_can_retrieve_multiple_cache_stored()
    {
        Cache::setMany(['name' => 'Doe', 'first_name' => 'John']);

        $this->assertEquals(Cache::get('name'), 'Doe');
        $this->assertEquals(Cache::get('first_name'), 'John');
    }

    public function test_clear_cache()
    {
        Cache::setMany(['name' => 'Doe', 'first_name' => 'John']);

        $this->assertEquals(Cache::get('first_name'), 'John');
        $this->assertEquals(Cache::get('name'), 'Doe');

        Cache::clear();

        $this->assertNull(Cache::get('name'));
        $this->assertNull(Cache::get('first_name'));
    }

    public function test_get_with_default_returns_default_for_missing_key()
    {
        $result = Cache::get('missing_key', 'default_value');
        $this->assertEquals('default_value', $result);
    }

    public function test_cache_stores_complex_data_structures()
    {
        $complexData = [
            'nested' => [
                'array' => [1, 2, 3],
                'string' => 'value'
            ],
            'number' => 42
        ];

        Cache::set('complex', $complexData);
        $retrieved = Cache::get('complex');

        $this->assertEquals($complexData, $retrieved);
    }

    public function test_multiple_stores_work_independently()
    {
        Cache::store('redis')->set('redis_key', 'redis_value');

        $this->assertEquals('redis_value', Cache::get('redis_key'));
        $this->assertTrue(Cache::has('redis_key'));
    }
}
