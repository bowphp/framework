<?php

namespace Bow\Tests\Database;

use Bow\Database\Redis;
use Bow\Tests\Config\TestingConfiguration;
use Redis as RedisClient;

class RedisTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Keys used during tests for cleanup
     *
     * @var array
     */
    private array $testKeys = [];

    protected function setUp(): void
    {
        parent::setUp();
        $config = TestingConfiguration::getConfig();
        $this->testKeys = [];
    }

    protected function tearDown(): void
    {
        // Clean up all test keys
        if (!empty($this->testKeys)) {
            $client = Redis::getClient();
            foreach ($this->testKeys as $key) {
                $client->del($key);
            }
        }
        parent::tearDown();
    }

    /**
     * Track a key for cleanup
     *
     * @param string $key
     * @return void
     */
    private function trackKey(string $key): void
    {
        $this->testKeys[] = $key;
    }

    // ===== Basic Set/Get Operations =====

    /**
     * @dataProvider basicDataProvider
     */
    public function test_set_and_get_various_types($key, $value, $expected)
    {
        $this->trackKey($key);

        $setResult = Redis::set($key, $value);
        $this->assertTrue($setResult);

        $getValue = Redis::get($key);
        $this->assertEquals($expected, $getValue);
    }

    /**
     * Basic data provider for various data types
     */
    public function basicDataProvider(): array
    {
        return [
            'string_value' => ['test:string', 'papac', 'papac'],
            'integer_value' => ['test:integer', 42, 42],
            'float_value' => ['test:float', 3.14, 3.14],
            'array_value' => ['test:array', ['name' => 'Dakia'], ['name' => 'Dakia']],
            'boolean_true' => ['test:bool:true', true, true],
            'boolean_false' => ['test:bool:false', false, false],
        ];
    }

    public function test_set_with_expiration_time()
    {
        $key = 'test:expiring';
        $this->trackKey($key);

        $result = Redis::set($key, 'temporary', 2);
        $this->assertTrue($result);

        $value = Redis::get($key);
        $this->assertEquals('temporary', $value);

        // Verify TTL is set
        $client = Redis::getClient();
        $ttl = $client->ttl($key);
        $this->assertGreaterThan(0, $ttl);
        $this->assertLessThanOrEqual(2, $ttl);
    }

    public function test_set_with_callable_value()
    {
        $key = 'test:callable';
        $this->trackKey($key);

        $result = Redis::set($key, function () {
            return 'computed_value';
        });

        $this->assertTrue($result);
        $this->assertEquals('computed_value', Redis::get($key));
    }

    // ===== Get Operations =====

    public function test_get_nonexistent_key_returns_null()
    {
        $result = Redis::get('test:nonexistent');
        $this->assertNull($result);
    }

    public function test_get_with_default_value()
    {
        $result = Redis::get('test:missing', 'default_value');
        $this->assertEquals('default_value', $result);
    }

    public function test_get_with_callable_default()
    {
        $result = Redis::get('test:missing', function () {
            return 'computed_default';
        });
        $this->assertEquals('computed_default', $result);
    }

    public function test_get_existing_key_ignores_default()
    {
        $key = 'test:existing';
        $this->trackKey($key);

        Redis::set($key, 'actual_value');
        $result = Redis::get($key, 'default_value');

        $this->assertEquals('actual_value', $result);
    }

    // ===== Get Client Operations =====

    public function test_get_client_returns_redis_instance()
    {
        $client = Redis::getClient();
        $this->assertInstanceOf(RedisClient::class, $client);
    }

    public function test_get_client_is_connected()
    {
        $client = Redis::getClient();
        $ping = $client->ping();

        // phpredis ping returns "+PONG" or true depending on version
        $this->assertTrue($ping === true || $ping === '+PONG');
    }

    public function test_multiple_get_client_calls_return_same_instance()
    {
        $client1 = Redis::getClient();
        $client2 = Redis::getClient();

        $this->assertSame($client1, $client2);
    }

    // ===== Ping Operations =====

    public function test_ping_without_message()
    {
        $this->expectNotToPerformAssertions();
        Redis::ping();
    }

    public function test_ping_with_message()
    {
        $this->expectNotToPerformAssertions();
        Redis::ping('test message');
    }

    // ===== Data Integrity Tests =====

    public function test_overwrite_existing_key()
    {
        $key = 'test:overwrite';
        $this->trackKey($key);

        Redis::set($key, 'first_value');
        $this->assertEquals('first_value', Redis::get($key));

        Redis::set($key, 'second_value');
        $this->assertEquals('second_value', Redis::get($key));
    }

    public function test_update_expiration_time()
    {
        $key = 'test:update_ttl';
        $this->trackKey($key);

        Redis::set($key, 'value', 5);
        Redis::set($key, 'value', 10);

        $client = Redis::getClient();
        $ttl = $client->ttl($key);

        $this->assertGreaterThan(5, $ttl);
        $this->assertLessThanOrEqual(10, $ttl);
    }

    public function test_null_value_storage()
    {
        $key = 'test:null_value';
        $this->trackKey($key);

        Redis::set($key, null);
        $value = Redis::get($key);

        $this->assertNull($value);
    }

    // ===== Complex Data Structures =====

    public function test_nested_array_storage()
    {
        $key = 'test:nested_array';
        $this->trackKey($key);

        $data = [
            'user' => [
                'name' => 'Dakia',
                'email' => 'dakia@example.com',
                'profile' => [
                    'age' => 30,
                    'country' => 'USA'
                ]
            ]
        ];

        Redis::set($key, $data);
        $retrieved = Redis::get($key);

        $this->assertEquals($data, $retrieved);
        $this->assertIsArray($retrieved);
        $this->assertArrayHasKey('user', $retrieved);
        $this->assertEquals('Dakia', $retrieved['user']['name']);
    }

    public function test_empty_array_storage()
    {
        $key = 'test:empty_array';
        $this->trackKey($key);

        Redis::set($key, []);
        $value = Redis::get($key);

        $this->assertEquals([], $value);
        $this->assertIsArray($value);
        $this->assertEmpty($value);
    }

    public function test_associative_array_with_mixed_types()
    {
        $key = 'test:mixed_array';
        $this->trackKey($key);

        $data = [
            'string' => 'value',
            'integer' => 123,
            'float' => 45.67,
            'boolean' => true,
            'array' => [1, 2, 3]
        ];

        Redis::set($key, $data);
        $retrieved = Redis::get($key);

        $this->assertEquals($data, $retrieved);
    }

    // ===== Multiple Operations =====

    public function test_multiple_keys_independently()
    {
        $keys = ['test:multi1', 'test:multi2', 'test:multi3'];
        foreach ($keys as $key) {
            $this->trackKey($key);
        }

        Redis::set('test:multi1', 'value1');
        Redis::set('test:multi2', 'value2');
        Redis::set('test:multi3', 'value3');

        $this->assertEquals('value1', Redis::get('test:multi1'));
        $this->assertEquals('value2', Redis::get('test:multi2'));
        $this->assertEquals('value3', Redis::get('test:multi3'));
    }

    public function test_sequential_operations_on_same_key()
    {
        $key = 'test:sequential';
        $this->trackKey($key);

        Redis::set($key, 'first');
        $this->assertEquals('first', Redis::get($key));

        Redis::set($key, 'second');
        $this->assertEquals('second', Redis::get($key));

        Redis::set($key, 'third');
        $this->assertEquals('third', Redis::get($key));
    }

    // ===== Edge Cases =====

    public function test_empty_string_value()
    {
        $key = 'test:empty_string';
        $this->trackKey($key);

        Redis::set($key, '');
        $value = Redis::get($key);

        $this->assertSame('', $value);
    }

    public function test_zero_values()
    {
        $intKey = 'test:zero_int';
        $floatKey = 'test:zero_float';
        $this->trackKey($intKey);
        $this->trackKey($floatKey);

        Redis::set($intKey, 0);
        Redis::set($floatKey, 0.0);

        $this->assertSame(0, Redis::get($intKey));
        $this->assertEquals(0.0, Redis::get($floatKey));
    }

    public function test_special_characters_in_value()
    {
        $key = 'test:special_chars';
        $this->trackKey($key);

        $value = "Special: !@#$%^&*()_+-=[]{}|;':\"<>?,./`~";
        Redis::set($key, $value);

        $this->assertEquals($value, Redis::get($key));
    }

    public function test_unicode_characters()
    {
        $key = 'test:unicode';
        $this->trackKey($key);

        $value = '日本語 français español 中文 العربية';
        Redis::set($key, $value);

        $this->assertEquals($value, Redis::get($key));
    }

    public function test_large_value_storage()
    {
        $key = 'test:large_value';
        $this->trackKey($key);

        $largeValue = str_repeat('a', 10000);
        Redis::set($key, $largeValue);

        $retrieved = Redis::get($key);
        $this->assertEquals($largeValue, $retrieved);
        $this->assertEquals(10000, strlen($retrieved));
    }

    // ===== Expiration Edge Cases =====

    public function test_set_without_expiration_persists()
    {
        $key = 'test:no_expire';
        $this->trackKey($key);

        Redis::set($key, 'persistent_value');

        // Verify the key exists and has no TTL
        $client = Redis::getClient();
        $ttl = $client->ttl($key);

        // -1 means key exists but has no expiration
        $this->assertEquals(-1, $ttl);
        $this->assertEquals('persistent_value', Redis::get($key));
    }

    public function test_set_with_very_short_expiration()
    {
        $key = 'test:short_expire';
        $this->trackKey($key);

        Redis::set($key, 'value', 1);
        $client = Redis::getClient();
        $ttl = $client->ttl($key);

        $this->assertGreaterThan(0, $ttl);
        $this->assertLessThanOrEqual(1, $ttl);
    }

    public function test_get_instance_returns_redis_object()
    {
        $instance = Redis::getInstance();
        $this->assertInstanceOf(Redis::class, $instance);
    }

    public function test_get_instance_is_singleton()
    {
        $instance1 = Redis::getInstance();
        $instance2 = Redis::getInstance();

        $this->assertSame($instance1, $instance2);
    }
}
