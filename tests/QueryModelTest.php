<?php

use \Bow\Database\Database;

class Pets extends \Bow\Database\Model
{
    /**
     * @var string
     */
    public static $table = "pets";

    /**
     * @var string
     */
    public static $primaryKey = 'pet_id';
}

class QueryModelTest extends \PHPUnit\Framework\TestCase
{
    public function testGetConnection()
    {
        return Database::instance();
    }

    /**
     * @depends testGetConnection
     */
    public function testInstanceOfModel($db)
    {
        $pet = new Pets();
        $pet = $pet->get()->first();
        $this->assertInstanceOf(Pets::class, $pet);
    }

    /**
     * @depends testGetConnection
     */
    public function testInstanceOfModel2($db)
    {
        $pet = new Pets();
        $pet = $pet->take(1)->get()->first();
        $this->assertInstanceOf(Pets::class, $pet);
    }

    /**
     * @depends testGetConnection
     */
    public function testInstanceCollectionOf($db)
    {
        $pets = Pets::get();
        $this->assertInstanceOf(Bow\Support\Collection::class, $pets);
    }

    /**
     * @depends testGetConnection
     */
    public function testChainSelectOf($db)
    {
        $pets = Pets::where('id', 1)->select(['name'])->get();
        $this->assertInstanceOf(Bow\Support\Collection::class, $pets);
    }

    /**
     * @depends testGetConnection
     */
    public function testCountOf($db)
    {
        $pets = Pets::count();
        $this->assertEquals(is_int($pets), true);
    }

    /**
     * @depends testGetConnection
     */
    public function testCountSelectCountOf($db)
    {
        $b = Pets::count();
        $a = Pets::get()->count();
        $this->assertEquals($a, $b);
    }

    /**
     * @depends testGetConnection
     */
    public function testNotCountSelectCountOf($db)
    {
        $b = Pets::where('id', 1)->count();
        $a = Pets::get()->count();
        $this->assertNotEquals($a, $b);
    }

    /**
     * @depends testGetConnection
     */
    public function testSaveOf($db)
    {
        $pet = Pets::first();
        $this->assertInstanceOf(Pets::class, $pet);
    }
}