<?php

namespace Bow\Tests\Support;

use Bow\Support\Arraydotify;

class ArraydotifyTest extends \PHPUnit\Framework\TestCase
{
    protected Arraydotify $dot;

    protected array $collection = [
        'name' => 'bow',
        'lastname' => 'framework',
        'bio' => 'The php micro framework',
        'author' => [
            'name' => 'Franck Dakia',
            'email' => 'dakiafranck@gmail.com'
        ],
        'location' => [
            'city' => 'Abidjan',
            'tel' => "12346678",
            "state" => [
                'code' => 225,
                'abr' => 'CI',
                'name' => 'Ivory Coast'
            ]
        ]
    ];

    protected function setUp(): void
    {
        $this->dot = new Arraydotify(['code' => $this->collection]);
    }

    public function test_instance_creation()
    {
        $dot = new Arraydotify(['name' => 'test']);
        $this->assertInstanceOf(Arraydotify::class, $dot);
    }

    public function test_static_make()
    {
        $dot = Arraydotify::make(['name' => 'test']);
        $this->assertInstanceOf(Arraydotify::class, $dot);
    }

    public function test_get_top_level_array()
    {
        $this->assertTrue(is_array($this->dot['code']));
    }

    public function test_get_simple_value()
    {
        $this->assertEquals('bow', $this->dot['code.name']);
        $this->assertEquals('framework', $this->dot['code.lastname']);
    }

    public function test_get_deeply_nested_value()
    {
        $this->assertEquals('CI', $this->dot['code.location.state.abr']);
        $this->assertEquals(225, $this->dot['code.location.state.code']);
        $this->assertEquals('Ivory Coast', $this->dot['code.location.state.name']);
    }

    public function test_get_nested_array()
    {
        $this->assertTrue(is_array($this->dot['code.location']));
        $this->assertTrue(is_array($this->dot['code.author']));
    }

    public function test_get_nested_array_contains_keys()
    {
        $location = $this->dot['code.location'];
        $this->assertArrayHasKey('city', $location);
        $this->assertArrayHasKey('tel', $location);
        $this->assertArrayHasKey('state', $location);

        $state = $this->dot['code.location.state'];
        $this->assertTrue(is_array($state));
        $this->assertArrayHasKey('code', $state);
        $this->assertArrayHasKey('abr', $state);
        $this->assertArrayHasKey('name', $state);
    }

    public function test_offset_exists()
    {
        $this->assertTrue(isset($this->dot['code']));
        $this->assertTrue(isset($this->dot['code.name']));
        $this->assertTrue(isset($this->dot['code.location.state.abr']));
        $this->assertFalse(isset($this->dot['nonexistent']));
        $this->assertFalse(isset($this->dot['code.nonexistent']));
    }

    public function test_has_method()
    {
        $this->assertTrue($this->dot->has('code'));
        $this->assertTrue($this->dot->has('code.name'));
        $this->assertTrue($this->dot->has('code.location.state.abr'));
        $this->assertFalse($this->dot->has('nonexistent'));
    }

    public function test_get_method()
    {
        $this->assertEquals('bow', $this->dot->get('code.name'));
        $this->assertEquals('default', $this->dot->get('nonexistent', 'default'));
        $this->assertNull($this->dot->get('nonexistent'));
    }

    public function test_get_nonexistent_returns_null()
    {
        $this->assertNull($this->dot['nonexistent.key']);
        $this->assertNull($this->dot['code.nonexistent']);
    }

    public function test_offset_set_simple_value()
    {
        $this->dot['code.version'] = '5.0';
        $this->assertEquals('5.0', $this->dot['code.version']);
    }

    public function test_offset_set_nested_value()
    {
        $this->dot['code.config.debug'] = true;
        $this->assertTrue($this->dot['code.config.debug']);
    }

    public function test_set_method()
    {
        $this->dot->set('code.environment', 'production');
        $this->assertEquals('production', $this->dot->get('code.environment'));
    }

    public function test_offset_set_overwrites_existing()
    {
        $this->dot['code.name'] = 'new-name';
        $this->assertEquals('new-name', $this->dot['code.name']);
    }

    public function test_offset_unset()
    {
        $this->assertTrue(isset($this->dot['code.name']));
        unset($this->dot['code.name']);
        $this->assertFalse(isset($this->dot['code.name']));
    }

    public function test_offset_unset_nested()
    {
        $this->assertTrue(isset($this->dot['code.location.state']));
        unset($this->dot['code.location.state']);
        $this->assertFalse(isset($this->dot['code.location.state']));
    }

    public function test_to_array_returns_original_structure()
    {
        $array = $this->dot->toArray();
        $this->assertIsArray($array);
        $this->assertArrayHasKey('code', $array);
        $this->assertEquals($this->collection, $array['code']);
    }

    public function test_get_dotified_returns_flat_array()
    {
        $dotified = $this->dot->getDotified();
        $this->assertIsArray($dotified);
        $this->assertArrayHasKey('code.name', $dotified);
        $this->assertArrayHasKey('code.location.state.abr', $dotified);
        $this->assertEquals('bow', $dotified['code.name']);
    }

    public function test_empty_array()
    {
        $dot = new Arraydotify([]);
        $this->assertEquals([], $dot->toArray());
        $this->assertEquals([], $dot->getDotified());
    }

    public function test_single_level_array()
    {
        $dot = new Arraydotify(['a' => 1, 'b' => 2, 'c' => 3]);
        $this->assertEquals(1, $dot['a']);
        $this->assertEquals(2, $dot['b']);
        $this->assertEquals(3, $dot['c']);
    }

    public function test_numeric_keys()
    {
        $dot = new Arraydotify(['items' => [0 => 'first', 1 => 'second', 2 => 'third']]);
        $this->assertEquals('first', $dot['items.0']);
        $this->assertEquals('second', $dot['items.1']);
        $this->assertEquals('third', $dot['items.2']);
    }

    public function test_mixed_keys()
    {
        $dot = new Arraydotify([
            'config' => [
                'database' => ['host' => 'localhost'],
                'cache' => ['driver' => 'redis']
            ]
        ]);
        $this->assertEquals('localhost', $dot['config.database.host']);
        $this->assertEquals('redis', $dot['config.cache.driver']);
    }

    public function test_set_creates_nested_structure()
    {
        $dot = new Arraydotify();
        $dot['app.name'] = 'MyApp';
        $this->assertEquals('MyApp', $dot['app.name']);

        $array = $dot->toArray();
        $this->assertArrayHasKey('app', $array);
        $this->assertArrayHasKey('name', $array['app']);
        $this->assertEquals('MyApp', $array['app']['name']);
    }

    public function test_set_deeply_nested_creates_path()
    {
        $dot = new Arraydotify();
        $dot['level1.level2.level3.level4.value'] = 'deep';
        $this->assertEquals('deep', $dot['level1.level2.level3.level4.value']);

        $this->assertTrue($dot->has('level1'));
        $this->assertTrue($dot->has('level1.level2'));
        $this->assertTrue($dot->has('level1.level2.level3'));
        $this->assertTrue($dot->has('level1.level2.level3.level4'));
    }

    public function test_set_array_value()
    {
        $dot = new Arraydotify(['data' => []]);
        $dot['data.items'] = ['apple', 'banana', 'orange'];

        $items = $dot['data.items'];
        $this->assertIsArray($items);
        $this->assertCount(3, $items);
        $this->assertContains('apple', $items);
    }

    public function test_set_null_value()
    {
        $dot = new Arraydotify(['key' => 'value']);
        $dot['key'] = null;
        $this->assertNull($dot['key']);
    }

    public function test_set_overwrites_nested_structure()
    {
        $dot = new Arraydotify([
            'config' => [
                'debug' => true,
                'app' => ['name' => 'OldApp']
            ]
        ]);

        $dot['config.app'] = 'NewValue';
        $this->assertEquals('NewValue', $dot['config.app']);
        $this->assertFalse($dot->has('config.app.name'));
    }

    public function test_set_multiple_values_same_path()
    {
        $dot = new Arraydotify();
        $dot['user.name'] = 'John';
        $dot['user.email'] = 'john@example.com';
        $dot['user.age'] = 30;

        $this->assertEquals('John', $dot['user.name']);
        $this->assertEquals('john@example.com', $dot['user.email']);
        $this->assertEquals(30, $dot['user.age']);

        $user = $dot['user'];
        $this->assertIsArray($user);
        $this->assertCount(3, $user);
    }

    public function test_set_with_numeric_index()
    {
        $dot = new Arraydotify();
        $dot['items.0'] = 'first';
        $dot['items.1'] = 'second';
        $dot['items.2'] = 'third';

        $this->assertEquals('first', $dot['items.0']);
        $this->assertEquals('second', $dot['items.1']);
        $this->assertEquals('third', $dot['items.2']);
    }

    public function test_set_boolean_values()
    {
        $dot = new Arraydotify();
        $dot['settings.enabled'] = true;
        $dot['settings.disabled'] = false;

        $this->assertTrue($dot['settings.enabled']);
        $this->assertFalse($dot['settings.disabled']);
    }

    public function test_set_integer_and_float_values()
    {
        $dot = new Arraydotify();
        $dot['numbers.integer'] = 42;
        $dot['numbers.float'] = 3.14;
        $dot['numbers.negative'] = -10;

        $this->assertSame(42, $dot['numbers.integer']);
        $this->assertSame(3.14, $dot['numbers.float']);
        $this->assertSame(-10, $dot['numbers.negative']);
    }

    public function test_set_preserves_existing_siblings()
    {
        $dot = new Arraydotify([
            'config' => [
                'app' => 'MyApp',
                'version' => '1.0'
            ]
        ]);

        $dot['config.debug'] = true;

        $this->assertEquals('MyApp', $dot['config.app']);
        $this->assertEquals('1.0', $dot['config.version']);
        $this->assertTrue($dot['config.debug']);
    }

    public function test_set_updates_both_storage_and_origin()
    {
        $dot = new Arraydotify();
        $dot['new.path.value'] = 'test';

        // Check dotified storage
        $dotified = $dot->getDotified();
        $this->assertArrayHasKey('new.path.value', $dotified);

        // Check original structure
        $array = $dot->toArray();
        $this->assertEquals('test', $array['new']['path']['value']);
    }

    public function test_set_empty_string()
    {
        $dot = new Arraydotify();
        $dot['empty'] = '';
        $this->assertSame('', $dot['empty']);
        $this->assertTrue($dot->has('empty'));
    }

    public function test_set_zero_value()
    {
        $dot = new Arraydotify();
        $dot['zero.int'] = 0;
        $dot['zero.float'] = 0.0;

        $this->assertSame(0, $dot['zero.int']);
        $this->assertSame(0.0, $dot['zero.float']);
    }

    public function test_set_method_with_complex_path()
    {
        $dot = new Arraydotify();
        $dot->set('api.endpoints.users.list', '/api/v1/users');
        $dot->set('api.endpoints.users.create', '/api/v1/users/create');
        $dot->set('api.endpoints.posts.list', '/api/v1/posts');

        $this->assertEquals('/api/v1/users', $dot->get('api.endpoints.users.list'));
        $this->assertEquals('/api/v1/users/create', $dot->get('api.endpoints.users.create'));
        $this->assertEquals('/api/v1/posts', $dot->get('api.endpoints.posts.list'));

        $endpoints = $dot['api.endpoints'];
        $this->assertIsArray($endpoints);
        $this->assertArrayHasKey('users', $endpoints);
        $this->assertArrayHasKey('posts', $endpoints);
    }
}
