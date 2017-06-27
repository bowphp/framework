<?php

use \Bow\Database\Database;

class Pets extends \Bow\Database\Barry\Model
{
    /**
     * @var string
     */
    protected $table = "pets";

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var bool
     */
    protected $timestamps = false;
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
    public function testInstanceOfModel(Bow\Database\Database $db)
    {
        $pet = new Pets();
        $pet = $pet->first();

        $this->assertInstanceOf(Pets::class, $pet);
    }

    /**
     * @depends testGetConnection
     */
    public function testInstanceOfModel2(Bow\Database\Database $db)
    {
        $pet = new Pets();
        $pet = $pet->take(1)->get()->first();
        $this->assertInstanceOf(Pets::class, $pet);
    }

    /**
     * @depends testGetConnection
     */
    public function testInstanceCollectionOf(Bow\Database\Database $db)
    {
        $pets = Pets::all();
        $this->assertInstanceOf(Bow\Support\Collection::class, $pets);
    }

    /**
     * @depends testGetConnection
     */
    public function testChainSelectOf(Bow\Database\Database $db)
    {
        $pets = Pets::where('id', 1)->select(['name'])->get();
        $this->assertInstanceOf(Bow\Support\Collection::class, $pets);
    }

    /**
     * @depends testGetConnection
     */
    public function testCountOf(Bow\Database\Database $db)
    {
        $pets = Pets::count();
        $this->assertEquals(is_int($pets), true);
    }

    /**
     * @depends testGetConnection
     */
    public function testCountSelectCountOf(Bow\Database\Database $db)
    {
        $b = Pets::count();
        $a = Pets::all()->count();
        $this->assertEquals($a, $b);
    }

    /**
     * @depends testGetConnection
     */
    public function testNotCountSelectCountOf(Bow\Database\Database $db)
    {
        $b = Pets::where('id', 1)->count();
        $a = Pets::all()->count();
        $this->assertNotEquals($a, $b);
    }

    /**
     * @depends testGetConnection
     */
    public function testSaveOf(Bow\Database\Database $db)
    {
        $pet = Pets::first();
        $this->assertInstanceOf(Pets::class, $pet);
    }

    /**
     * @depends testGetConnection
     */
    public function testInsert(Bow\Database\Database $db)
    {
        $pet = Pets::create([
            'name' => 'Couli',
            'id' => 1
        ]);

        $this->assertInstanceOf(Pets::class, $pet);
    }
}