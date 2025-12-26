<?php

namespace Bow\Tests\Database;

use Bow\Cache\Cache;
use Bow\Database\Database;
use Bow\Database\QueryEvent;
use Bow\Event\Event;
use Bow\Tests\Config\TestingConfiguration;

class CacheDatabaseTest extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();

        Database::configure($config["database"]);
        Database::connection('mysql');
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

    public static function tearDownAfterClass(): void
    {
        // Clean up database table after all tests
        try {
            Database::connection('mysql');
            // Database::statement("TRUNCATE TABLE caches");
        } catch (\Exception $e) {
            // Silently fail
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

    public function test_set_cache()
    {
        // set() should overwrite existing values unlike add()
        Cache::set('name', 'First');
        $this->assertEquals(Cache::get('name'), 'First');

        Cache::set('name', 'Second');
        $this->assertEquals(Cache::get('name'), 'Second');
    }

    public function test_set_with_callback_cache()
    {
        $result = Cache::set('lastname', fn() => 'Franck');
        $result = $result && Cache::set('age', fn() => 25, 20000);

        $this->assertEquals($result, true);
    }

    public function test_get_callback_cache()
    {
        Cache::set('lastname', fn() => 'Franck');
        Cache::set('age', fn() => 25, 20000);

        $this->assertEquals(Cache::get('lastname'), 'Franck');
        $this->assertEquals(Cache::get('age'), 25);
    }

    public function test_set_array_cache()
    {
        $result = Cache::set('address', [
            'tel' => "49929598",
            'city' => "Abidjan",
            'country' => "Cote d'ivoire"
        ]);

        $this->assertEquals($result, true);
    }

    public function test_get_array_cache()
    {
        Cache::set('address', [
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
        Cache::set('name', 'TestValue');

        $first_result = Cache::has('name');
        $other_result = Cache::has('jobs');

        $this->assertEquals(true, $first_result);
        $this->assertEquals(false, $other_result);
    }

    public function test_forget()
    {
        Cache::set('name', 'TestValue');
        $result = Cache::forget('name');

        $this->assertEquals(true, $result);
        $this->assertEquals(Cache::get('name', false), false);
    }

    public function test_forget_empty()
    {
        $result = Cache::forget('non_existent_key');

        $this->assertEquals(false, $result);
    }

    public function test_time_of_empty()
    {
        Cache::set('lastname', 'TestValue');

        $result = Cache::timeOf('lastname');

        $this->assertIsInt($result);
        $this->assertEquals(0, $result);
    }

    public function test_time_of_empty_2()
    {
        Cache::set('address', ['test' => 'value']);

        $result = Cache::timeOf('address');

        $this->assertIsInt($result);
        $this->assertEquals(0, $result);
    }

    public function test_time_of_empty_3()
    {
        Cache::set('age', 25, 20000);
        $result = Cache::timeOf('age');

        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
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

    public function test_get_with_default_value()
    {
        $result = Cache::get('non_existent_key', 'default_value');
        $this->assertEquals('default_value', $result);
    }

    public function test_cache_with_numeric_values()
    {
        Cache::set('integer', 42);
        Cache::set('float', 3.14);
        Cache::set('zero', 0);

        $this->assertSame(42, Cache::get('integer'));
        $this->assertSame(3.14, Cache::get('float'));
        $this->assertSame(0, Cache::get('zero'));
    }

    public function test_cache_with_boolean_values()
    {
        Cache::set('true_value', true);
        Cache::set('false_value', false);

        $this->assertTrue(Cache::get('true_value'));
        $this->assertFalse(Cache::get('false_value'));
    }

    public function test_cache_expiration()
    {
        // Add cache with 3 second expiry
        Cache::set('expiring_key', 'temporary', 1);

        $this->assertEquals('temporary', Cache::get('expiring_key'));

        // Wait for expiration
        sleep(2);

        $this->assertNull(Cache::get('expiring_key'));
    }
}
