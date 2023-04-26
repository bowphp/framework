<?php

namespace Bow\Tests\Database\Query;

use Bow\Database\Database;
use Bow\Tests\Config\TestingConfiguration;
use Bow\Tests\Database\Stubs\PetModelStub;

class ModelQueryTest extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();
        Database::statement($config["database"]);
        Database::statement('create table if not exists pets (id int primary key, name varchar(255))');
        Database::insert('insert into pets values(:id, :name)', ['id' => 1, 'name' => 'Couli']);
        Database::insert('insert into pets values(:id, :name)', ['id' => 2, 'name' => 'Bobi']);
    }

    public static function tearDownAfterClass(): void
    {
        Database::table("pets")->truncate();
    }

    public function test_get_database_connection()
    {
        $instance = Database::getInstance();

        $this->assertInstanceOf(Database::class, $instance);

        return Database::getInstance();
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_the_first_result_should_be_the_instance_of_same_model()
    {
        $pet_model = new PetModelStub();
        $pet = $pet_model->first();

        $this->assertInstanceOf(PetModelStub::class, $pet);
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_take_method_and_the_result_should_be_the_instance_of_the_same_model(
        Database $database
    ) {
        $pet_model = new PetModelStub();
        $pet = $pet_model->take(1)->get()->first();

        $this->assertInstanceOf(PetModelStub::class, $pet);
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_instance_off_collection()
    {
        $pet_model = PetModelStub::all();

        $this->assertInstanceOf(\Bow\Support\Collection::class, $pet_model);
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_chain_select()
    {
        $pet_collection_model = PetModelStub::where('id', 1)->select(['name'])->get();

        $this->assertInstanceOf(\Bow\Support\Collection::class, $pet_collection_model);
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_count_simple()
    {
        $pet_count = PetModelStub::count();

        $this->assertEquals(is_int($pet_count), true);
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_count_selected()
    {
        $pet_count_first = PetModelStub::count();
        $pet_count_second = PetModelStub::all()->count();

        $this->assertEquals($pet_count_first, $pet_count_second);
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_count_selected_with_collection_count()
    {
        $pet_count_first = PetModelStub::where('id', 1)->count();
        $pet_count_second = PetModelStub::all()->count();

        $this->assertNotEquals($pet_count_first, $pet_count_second);
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_insert_by_create_method()
    {
        $id = PetModelStub::all()->count() + 1;
        $insert_result = PetModelStub::create(['id' => $id, 'name' => 'Tor']);
        $select_result = PetModelStub::findBy('id', $id)->first();

        $this->assertInstanceOf(PetModelStub::class, $insert_result);
        $this->assertInstanceOf(PetModelStub::class, $select_result);

        $this->assertEquals($insert_result->name, 'Tor');
        $this->assertEquals($insert_result->id, $id);

        $this->assertEquals($select_result->name, 'Tor');
        $this->assertEquals($select_result->id, $id);
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_save()
    {
        $pet = PetModelStub::first();
        $pet->name = "Lofi";
        $pet->save();

        $this->assertNotEquals($pet->name, 'Couli');
        $this->assertInstanceOf(PetModelStub::class, $pet);
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_find_should_not_be_empty()
    {
        $pet = PetModelStub::find(1);

        $this->assertEquals($pet->name, 'Lofi');
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_find_result_should_be_empty()
    {
        $pet = PetModelStub::find(100);

        $this->assertNull($pet);
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_findby_result_should_not_be_empty()
    {
        $result = PetModelStub::findBy('id', 1);
        $pet = $result->first();

        $this->assertNotEquals($result->count(), 0);
        $this->assertNotNull($pet);
        $this->assertEquals($pet->name, 'Lofi');
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_find_by_method_should_be_empty()
    {
        $result = PetModelStub::findBy('id', 100);
        $pet = $result->first();

        $this->assertNull($pet);
    }
}
