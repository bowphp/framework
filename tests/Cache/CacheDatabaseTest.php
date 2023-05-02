<?php

namespace Bow\Tests\Cache;

use Bow\Cache\Cache;
use Bow\Database\Database;
use Bow\Tests\Config\TestingConfiguration;

class CacheDatabaseTest extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();

        Database::configure($config["database"]);

        Database::statement("drop table if exists caches;");
        Database::statement("
            create table if not exists caches (
                `keyname` varchar(500) not null primary key,
                `data` text null,
                `expire` datetime null
            )");

        Cache::confirgure($config["cache"]);
        Cache::cache("database");
    }

    public function test_create_cache()
    {
        $result = Cache::add('name', 'Dakia');

        $this->assertEquals($result, true);
    }

    public function test_get_cache()
    {
        $this->assertEquals(Cache::get('name'), 'Dakia');
    }

    public function test_AddWithCallbackCache()
    {
        $result = Cache::add('lastname', fn () => 'Franck');
        $result = $result && Cache::add('age', fn () => 25, 20000);

        $this->assertEquals($result, true);
    }

    public function test_GetCallbackCache()
    {
        $this->assertEquals(Cache::get('lastname'), 'Franck');

        $this->assertEquals(Cache::get('age'), 25);
    }

    public function test_AddArrayCache()
    {
        $result = Cache::add('address', [
            'tel' => "49929598",
            'city' => "Abidjan",
            'country' => "Cote d'ivoire"
        ]);

        $this->assertEquals($result, true);
    }

    public function test_GetArrayCache()
    {
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
        $this->expectExceptionMessage("The key name is not found");
        $result = Cache::forget('name');
    }

    public function test_time_of_empty()
    {
        $result = Cache::timeOf('lastname');

        $this->assertIsString($result);
    }

    public function test_time_of_empty_2()
    {
        $result = Cache::timeOf('address');

        $this->assertIsString($result);
    }

    public function test_time_of_empty_3()
    {
        $result = Cache::timeOf('age');

        $this->assertIsString($result);
    }

    public function test_can_add_many_data_at_the_same_time_in_the_cache()
    {
        $result = Cache::addMany(['name' => 'Doe', 'first_name' => 'John']);

        $this->assertEquals($result, true);
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
