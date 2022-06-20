<?php

use \Bow\Support\Collection;

class CollectionTest extends \PHPUnit\Framework\TestCase
{
    public function test_get_instance()
    {
        $collection = new Collection(range(1, 10));

        $this->assertInstanceOf(Collection::class, $collection);

        return $collection;
    }

    /**
     * @param $collection
     * @depends test_get_instance
     */
    public function test_sum(Collection $collection)
    {
        $this->assertEquals(array_sum(range(1, 10)), $collection->sum());
    }

    /**
     * @param $collection
     * @depends test_get_instance
     */
    public function test_max(Collection $collection)
    {
        $this->assertEquals(max(range(1, 10)), $collection->max());
    }

    /**
     * @param $collection
     * @depends test_get_instance
     */
    public function test_min(Collection $collection)
    {
        $this->assertEquals(min(range(1, 10)), $collection->min());
    }

    /**
     * @param $collection
     * @depends test_get_instance
     */
    public function test_count(Collection $collection)
    {
        $this->assertEquals(count(range(1, 10)), $collection->count());
    }

    /**
     * @param $collection
     * @depends test_get_instance
     */
    public function test_pop(Collection $collection)
    {
        $this->assertEquals(10, $collection->pop());
    }

    /**
     * @param $collection
     * @depends test_get_instance
     */
    public function test_shift(Collection $collection)
    {
        $this->assertEquals(1, $collection->shift());
    }

    /**
     * @param $collection
     * @depends test_get_instance
     */
    public function test_reserve(Collection $collection)
    {
        $this->assertEquals(array_reverse(range(1, 9)), $collection->reverse()->toArray());
    }

    /**
     * @param $collection
     * @depends test_get_instance
     */
    public function test_generator(Collection $collection)
    {
        $gen = $collection->yieldify();

        $this->assertInstanceOf(Generator::class, $gen);
    }

    /**
     * @param $collection
     * @depends test_get_instance
     */
    public function test_json(Collection $collection)
    {
        $this->assertJson($collection->toJson());
    }

    /**
     * @param $collection
     * @depends test_get_instance
     */
    public function test_excepts(Collection $collection)
    {
        $this->assertEquals(range(1, 2), $collection->excepts([0, 1])->toArray());
    }

    /**
     * @param $collection
     * @depends test_get_instance
     */
    public function test_push(Collection $collection)
    {
        $collection->push(10);

        $this->assertEquals(range(1, 10), $collection->toArray());
    }
}
