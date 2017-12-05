<?php

use \Bow\Support\Collection;

class CollectionTest extends \PHPUnit\Framework\TestCase
{
    public function testGetInstance()
    {
        $collection = new Collection(range(1, 10));
        $this->assertInstanceOf(Collection::class, $collection);
        return $collection;
    }

    /**
     * @param $collection
     * @depends testGetInstance
     */
    public function testSum(Collection $collection)
    {
        $this->assertEquals(array_sum(range(1, 10)), $collection->sum());
    }

    /**
     * @param $collection
     * @depends testGetInstance
     */
    public function testMax(Collection $collection)
    {
        $this->assertEquals(max(range(1, 10)), $collection->max());
    }

    /**
     * @param $collection
     * @depends testGetInstance
     */
    public function testMin(Collection $collection)
    {
        $this->assertEquals(min(range(1, 10)), $collection->min());
    }

    /**
     * @param $collection
     * @depends testGetInstance
     */
    public function testCount(Collection $collection)
    {
        $this->assertEquals(count(range(1, 10)), $collection->count());
    }

    /**
     * @param $collection
     * @depends testGetInstance
     */
    public function testPop(Collection $collection)
    {
        $this->assertEquals(10, $collection->pop());
    }

    /**
     * @param $collection
     * @depends testGetInstance
     */
    public function testShift(Collection $collection)
    {
        $this->assertEquals(1, $collection->shift());
    }

    /**
     * @param $collection
     * @depends testGetInstance
     */
    public function testReserve(Collection $collection)
    {
        $this->assertEquals(array_reverse(range(1, 9)), $collection->reverse()->toArray());
    }

    /**
     * @param $collection
     * @depends testGetInstance
     */
    public function testGenerator(Collection $collection)
    {
        $gen = $collection->yieldify();
        $this->assertInstanceOf(Generator::class, $gen);
    }

    /**
     * @param $collection
     * @depends testGetInstance
     */
    public function testJson(Collection $collection)
    {
        $this->assertJson($collection->toJson());
    }

    /**
     * @param $collection
     * @depends testGetInstance
     */
    public function testExcepts(Collection $collection)
    {
        $this->assertEquals(range(1, 2), $collection->excepts([0, 1])->toArray());
    }

    /**
     * @param $collection
     * @depends testGetInstance
     */
    public function testPush(Collection $collection)
    {
        $collection->push(10);
        $this->assertEquals(range(1, 10), $collection->toArray());
    }
}