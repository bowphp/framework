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

        Database::statement("DROP TABLE IF EXISTS caches;");
        Database::statement("
            CREATE TABLE IF NOT EXISTS caches (
                key_name varchar(500) not null primary key,
                data text null,
                expire TIMESTAMP null
            )");

        Cache::configure($config["cache"]);
        Cache::store("database");
    }

    public function setUp(): void
    {
        // Clear all cache before each test for isolation
        Database::statement("DELETE FROM caches");
    }

    public function test_create_cache()
    {
        $result = Cache::add('name', 'Dakia');

        $this->assertEquals($result, true);
    }

    public function test_get_cache()
    {
        Cache::add('name', 'Dakia');
        $this->assertEquals(Cache::get('name'), 'Dakia');
    }

    public function test_add_with_callback_cache()
    {
        $result = Cache::add('lastname', fn() => 'Franck');
        $result = $result && Cache::add('age', fn() => 25, 20000);

        $this->assertEquals($result, true);
    }

    public function test_get_callback_cache()
    {
        Cache::add('lastname', fn() => 'Franck');
        Cache::add('age', fn() => 25, 20000);
        
        $this->assertEquals(Cache::get('lastname'), 'Franck');
        $this->assertEquals(Cache::get('age'), 25);
    }

    public function test_add_array_cache()
    {
        $result = Cache::add('address', [
            'tel' => "49929598",
            'city' => "Abidjan",
            'country' => "Cote d'ivoire"
        ]);

        $this->assertEquals($result, true);
    }

    public function test_get_array_cache()
    {
        Cache::add('address', [
            'tel' => "49929598",
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
        Cache::add('name', 'TestValue');
        
        $first_result = Cache::has('name');
        $other_result = Cache::has('jobs');

        $this->assertEquals(true, $first_result);
        $this->assertEquals(false, $other_result);
    }

    public function test_forget()
    {
        Cache::add('name', 'TestValue');
        $result = Cache::forget('name');

        $this->assertEquals(true, $result);
        $this->assertEquals(Cache::get('name', false), false);
    }

    public function test_forget_empty()
    {
        $this->expectExceptionMessage("is not found");
        // Try to forget a key that doesn't exist
        $result = Cache::forget('non_existent_key');
    }

    public function test_time_of_empty()
    {
        Cache::add('lastname', 'TestValue');
        $result = Cache::timeOf('lastname');

        $this->assertIsString($result);
    }

    public function test_time_of_empty_2()
    {
        Cache::add('address', ['test' => 'value']);
        $result = Cache::timeOf('address');

        $this->assertIsString($result);
    }

    public function test_time_of_empty_3()
    {
        Cache::add('age', 25, 20000);
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
