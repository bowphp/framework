<?php

namespace Bow\Tests\Support;

use Bow\Support\Collection;
use Generator as PHPGenerator;

class CollectionTest extends \PHPUnit\Framework\TestCase
{
    public function test_get_instance()
    {
        $collection = new Collection(range(1, 10));

        $this->assertInstanceOf(Collection::class, $collection);

        return $collection;
    }

    /**
     * @param Collection $collection
     * @depends test_get_instance
     */
    public function test_sum(Collection $collection)
    {
        $this->assertEquals(array_sum(range(1, 10)), $collection->sum());
    }

    /**
     * @param Collection $collection
     * @depends test_get_instance
     */
    public function test_max(Collection $collection)
    {
        $this->assertEquals(max(range(1, 10)), $collection->max());
    }

    /**
     * @param Collection $collection
     * @depends test_get_instance
     */
    public function test_min(Collection $collection)
    {
        $this->assertEquals(min(range(1, 10)), $collection->min());
    }

    /**
     * @param Collection $collection
     * @depends test_get_instance
     */
    public function test_count(Collection $collection)
    {
        // Create fresh collection to avoid mutations from previous tests
        $collection = new Collection(range(1, 10));
        $this->assertEquals(count(range(1, 10)), $collection->count());
    }

    public function test_pop()
    {
        $collection = new Collection(range(1, 10));
        $this->assertEquals(10, $collection->pop());
    }

    public function test_shift()
    {
        $collection = new Collection(range(1, 10));
        $this->assertEquals(1, $collection->shift());
    }

    public function test_reserve()
    {
        $collection = new Collection(range(1, 10));
        $this->assertEquals(array_reverse(range(1, 10)), $collection->reverse()->toArray());
    }

    public function test_generator()
    {
        $collection = new Collection(range(1, 10));
        $gen = $collection->yieldify();

        $this->assertInstanceOf(PHPGenerator::class, $gen);
    }

    public function test_json()
    {
        $collection = new Collection(range(1, 10));
        $this->assertJson($collection->toJson());
    }

    public function test_excepts()
    {
        $collection = new Collection(range(1, 10));
        // excepts([0, 1]) keeps only items at indices 0 and 1, which are values 1 and 2
        $result = $collection->excepts([0, 1])->toArray();
        $this->assertEquals([0 => 1, 1 => 2], $result);
    }

    public function test_push()
    {
        $collection = new Collection(range(1, 9));
        $collection->push(10);

        $this->assertEquals(range(1, 10), $collection->toArray());
    }

    public function test_first()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $this->assertEquals(1, $collection->first());
    }

    public function test_last()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $this->assertEquals(5, $collection->last());
    }

    public function test_is_empty()
    {
        $collection = new Collection();
        $this->assertTrue($collection->isEmpty());

        $collection->push(1);
        $this->assertFalse($collection->isEmpty());
    }

    public function test_length()
    {
        $collection = new Collection([1, 2, 3]);
        $this->assertEquals(3, $collection->length());
    }

    public function test_values()
    {
        $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
        $values = $collection->values();
        $this->assertInstanceOf(Collection::class, $values);
        $this->assertEquals([1, 2, 3], $values->toArray());
    }

    public function test_keys()
    {
        $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
        $keys = $collection->keys();
        $this->assertInstanceOf(Collection::class, $keys);
        $this->assertEquals(['a', 'b', 'c'], $keys->toArray());
    }

    public function test_chunk()
    {
        $collection = new Collection([1, 2, 3, 4, 5, 6]);
        $chunked = $collection->chunk(2);
        $expected = [[1, 2], [3, 4], [5, 6]];
        $this->assertEquals($expected, $chunked->all());
    }

    public function test_collectify()
    {
        $collection = new Collection(['items' => [1, 2, 3], 'count' => 3]);
        $items = $collection->collectify('items');
        $this->assertInstanceOf(Collection::class, $items);
        $this->assertEquals([1, 2, 3], $items->toArray());
    }

    public function test_has()
    {
        $collection = new Collection(['name' => 'John', 'age' => 30]);
        $this->assertTrue($collection->has('name'));
        $this->assertFalse($collection->has('email'));
        $this->assertTrue($collection->has('age', true));
    }

    public function test_each()
    {
        $collection = new Collection([1, 2, 3]);
        $sum = 0;
        $collection->each(function ($value) use (&$sum) {
            $sum += $value;
        });
        $this->assertEquals(6, $sum);
    }

    public function test_merge()
    {
        $collection = new Collection([1, 2, 3]);
        $merged = $collection->merge([4, 5, 6]);
        $this->assertEquals([1, 2, 3, 4, 5, 6], $merged->toArray());
    }

    public function test_merge_with_collection()
    {
        $collection1 = new Collection([1, 2, 3]);
        $collection2 = new Collection([4, 5, 6]);
        $merged = $collection1->merge($collection2);
        $this->assertEquals([1, 2, 3, 4, 5, 6], $merged->toArray());
    }

    public function test_map()
    {
        $collection = new Collection([1, 2, 3]);
        $mapped = $collection->map(function ($value) {
            return $value * 2;
        });
        $this->assertEquals([2, 4, 6], $mapped->toArray());
    }

    public function test_filter()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $filtered = $collection->filter(function ($value) {
            return $value > 3;
        });
        $this->assertEquals([4, 5], $filtered->toArray());
    }

    public function test_fill()
    {
        $collection = new Collection([1, 2, 3]);
        $old = $collection->fill('x', 2);
        $this->assertEquals([1, 2, 3], $old);
        $this->assertEquals([1, 2, 3, 'x', 'x'], $collection->toArray());
    }

    public function test_reduce()
    {
        $collection = new Collection([1, 2, 3, 4]);
        $result = $collection->reduce(function ($carry, $item) {
            return $carry + $item;
        }, 0);
        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_implode()
    {
        $collection = new Collection(['a', 'b', 'c']);
        $this->assertEquals('a,b,c', $collection->implode(','));
    }

    public function test_ignores()
    {
        $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
        $ignored = $collection->ignores(['b']);
        $this->assertInstanceOf(Collection::class, $ignored);
        $this->assertEquals(['a' => 1, 'c' => 3], $ignored->toArray());
    }

    public function test_update()
    {
        $collection = new Collection(['name' => 'John', 'age' => 30]);
        $result = $collection->update('name', 'Jane');
        $this->assertTrue($result);
        $this->assertEquals('Jane', $collection->get('name'));
    }

    public function test_update_non_existing()
    {
        $collection = new Collection(['name' => 'John']);
        $result = $collection->update('email', 'john@example.com');
        $this->assertFalse($result);
    }

    public function test_all()
    {
        $data = ['a' => 1, 'b' => 2];
        $collection = new Collection($data);
        $this->assertEquals($data, $collection->all());
    }

    public function test_get()
    {
        $collection = new Collection(['name' => 'John', 'age' => 30]);
        $this->assertEquals('John', $collection->get('name'));
        $this->assertEquals('default', $collection->get('email', 'default'));
    }

    public function test_get_with_callback()
    {
        $collection = new Collection(['name' => 'John']);
        $result = $collection->get('email', function () {
            return 'no-email@example.com';
        });
        $this->assertEquals('no-email@example.com', $result);
    }

    public function test_set()
    {
        $collection = new Collection(['name' => 'John']);
        $old = $collection->set('name', 'Jane');
        $this->assertEquals('John', $old);
        $this->assertEquals('Jane', $collection->get('name'));
    }

    public function test_set_new_key()
    {
        $collection = new Collection();
        $old = $collection->set('name', 'John');
        $this->assertNull($old);
        $this->assertEquals('John', $collection->get('name'));
    }

    public function test_remove()
    {
        $collection = new Collection(['name' => 'John', 'age' => 30]);
        $result = $collection->remove('name');
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertFalse($collection->has('name'));
    }

    public function test_magic_get()
    {
        $collection = new Collection(['name' => 'John']);
        $this->assertEquals('John', $collection->name);
    }

    public function test_magic_set()
    {
        $collection = new Collection();
        $collection->name = 'John';
        $this->assertEquals('John', $collection->get('name'));
    }

    public function test_magic_isset()
    {
        $collection = new Collection(['name' => 'John']);
        $this->assertTrue(isset($collection->name));
        $this->assertFalse(isset($collection->email));
    }

    public function test_magic_unset()
    {
        $collection = new Collection(['name' => 'John', 'age' => 30]);
        unset($collection->name);
        $this->assertFalse($collection->has('name'));
    }

    public function test_array_access_exists()
    {
        $collection = new Collection(['name' => 'John']);
        $this->assertTrue(isset($collection['name']));
    }

    public function test_array_access_get()
    {
        $collection = new Collection(['name' => 'John']);
        $this->assertEquals('John', $collection['name']);
    }

    public function test_array_access_set()
    {
        $collection = new Collection();
        $collection['name'] = 'John';
        $this->assertEquals('John', $collection->get('name'));
    }

    public function test_array_access_unset()
    {
        $collection = new Collection(['name' => 'John', 'age' => 30]);
        unset($collection['name']);
        $this->assertFalse($collection->has('name'));
    }

    public function test_iterator()
    {
        $data = ['a' => 1, 'b' => 2, 'c' => 3];
        $collection = new Collection($data);
        $result = [];
        foreach ($collection as $key => $value) {
            $result[$key] = $value;
        }
        $this->assertEquals($data, $result);
    }

    public function test_to_string()
    {
        $collection = new Collection(['name' => 'John', 'age' => 30]);
        $json = (string)$collection;
        $this->assertJson($json);
        $this->assertEquals(['name' => 'John', 'age' => 30], json_decode($json, true));
    }

    public function test_json_serialize()
    {
        $collection = new Collection(['name' => 'John', 'age' => 30]);
        $this->assertEquals(['name' => 'John', 'age' => 30], $collection->jsonSerialize());
    }
}
