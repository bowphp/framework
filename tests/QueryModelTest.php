<?php

use \Bow\Database\Database;
use \Bow\Database\Barry\Model;

class Pets extends Model
{
    /**
     * @var string
     */
    protected string $table = "pets";

    /**
     * @var string
     */
    protected string $primaryKey = 'id';

    /**
     * @var bool
     */
    protected bool $timestamps = false;
}

class QueryModelTest extends \PHPUnit\Framework\TestCase
{
    public function test_get_connection()
    {
        return Database::getInstance();
    }

    /**
     * @param Database $db
     * @depends test_get_connection
     */
    public function test_the_first_result_should_be_the_instance_of_same_model(Bow\Database\Database $db)
    {
        $pet = new Pets();

        $pet = $pet->first();

        $this->assertInstanceOf(Pets::class, $pet);
    }

    /**
     * @depends test_get_connection
     * @param Database $db
     */
    public function test_take_method_and_the_result_should_be_the_instance_of_the_same_model(Bow\Database\Database $db)
    {
        $pet = new Pets();

        $pet = $pet->take(1)->get()->first();

        $this->assertInstanceOf(Pets::class, $pet);
    }

    /**
     * @depends test_get_connection
     * @param Database $db
     */
    public function testInstanceCollectionOf(Bow\Database\Database $db)
    {
        $pets = Pets::all();

        $this->assertInstanceOf(Bow\Support\Collection::class, $pets);
    }

    /**
     * @depends test_get_connection
     * @param Database $db
     */
    public function testChainSelectOf(Bow\Database\Database $db)
    {
        $pets = Pets::where('id', 1)->select(['name'])->get();

        $this->assertInstanceOf(Bow\Support\Collection::class, $pets);
    }

    /**
     * @depends test_get_connection
     * @param Database $db
     */
    public function testCountOf(Bow\Database\Database $db)
    {
        $pets = Pets::count();

        $this->assertEquals(is_int($pets), true);
    }

    /**
     * @depends test_get_connection
     * @param Database $db
     */
    public function testCountSelectCountOf(Bow\Database\Database $db)
    {
        $b = Pets::count();

        $a = Pets::all()->count();

        $this->assertEquals($a, $b);
    }

    /**
     * @depends test_get_connection
     */
    public function testNotCountSelectCountOf(Bow\Database\Database $db)
    {
        $b = Pets::where('id', 1)->count();

        $a = Pets::all()->count();

        $this->assertNotEquals($a, $b);
    }

    /**
     * @depends test_get_connection
     * @param Database $db
     */
    public function testSaveOf(Bow\Database\Database $db)
    {
        $pet = Pets::first();

        $this->assertInstanceOf(Pets::class, $pet);
    }

    /**
     * @depends test_get_connection
     * @param Database $db
     */
    public function testInsert(Bow\Database\Database $db)
    {
        $insert_result = Pets::create(['name' => 'Couli', 'id' => 1 ]);
        $select_result = Pet::findBy('id', 1)->first();

        $this->assertInstanceOf(Pets::class, $insert_result);
        $this->assertInstanceOf(Pets::class, $select_result);
    }

    /**
     * @depends test_get_connection
     * @param Database $db
     */
    public function test_find_should_not_be_empty(Bow\Database\Database $db)
    {
        $pet = Pets::find(1);

        $this->assertInstanceOf(Pets::class, $pet);
        $this->assertEquals($pet->name, 'Couli');
    }

    /**
     * @depends test_get_connection
     * @param Database $db
     */
    public function test_find_result_should_be_empty(Bow\Database\Database $db)
    {
        $pet = Pets::find(100);

        $this->assertNull($pet);
    }

    /**
     * @depends test_get_connection
     * @param Database $db
     */
    public function test_findby_result_should_not_be_empty(Bow\Database\Database $db)
    {
        $result = Pets::findBy('id', 1);
        $pet = $result->first();

        $this->assertNotEquals($result->count(), 0);
        $this->assertNotNull($pet);
        $this->assertEquals($pet->name, 'Couli');
    }

    /**
     * @depends test_get_connection
     * @param Database $db
     */
    public function test_find_by_method_should_be_empty(Bow\Database\Database $db)
    {
        $result = Pets::findBy('id', 100);
        $pet = $result->first();

        $this->assertEquals($pet->count(), 0);
        $this->assertNull($pet);
    }
}
