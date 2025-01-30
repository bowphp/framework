<?php

namespace Bow\Tests\Database\Query;

use Bow\Database\Database;
use Bow\Database\Exception\ConnectionException;
use Bow\Tests\Config\TestingConfiguration;
use Bow\Tests\Database\Stubs\PetModelStub;

class ModelQueryTest extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();
        Database::configure($config["database"]);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_the_first_result_should_be_the_instance_of_same_model(string $name)
    {
        $this->createTestingTable($name);

        $pet_model = new PetModelStub();
        $pet = $pet_model->first();

        $this->assertInstanceOf(PetModelStub::class, $pet);
    }

    /**
     * @param string $name
     * @throws ConnectionException
     */
    public function createTestingTable(string $name): void
    {
        $connection = Database::connection($name);

        if ($name == 'pgsql') {
            $sql = 'create table pets (id serial primary key, name varchar(255))';
        }

        if ($name == 'sqlite') {
            $sql = 'create table pets (id integer not null primary key autoincrement, name varchar(255))';
        }

        if ($name == 'mysql') {
            $sql = 'create table pets (id int not null primary key auto_increment, name varchar(255))';
        }

        $connection->statement('drop table if exists pets');
        $connection->statement($sql);
        $connection->insert('insert into pets(name) values(:name)', [
            ['name' => 'Couli'], ['name' => 'Bobi']
        ]);
    }

    /**
     * @dataProvider connectionNameProvider
     * @throws ConnectionException
     */
    public function test_take_method_and_the_result_should_be_the_instance_of_the_same_model(
        string $name
    ) {
        $this->createTestingTable($name);

        $pet_model = new PetModelStub();
        $pet = $pet_model->take(1)->get()->first();

        $this->assertInstanceOf(PetModelStub::class, $pet);
    }

    /**
     * @dataProvider connectionNameProvider
     * @throws ConnectionException
     */
    public function test_instance_off_collection(string $name)
    {
        $this->createTestingTable($name);

        $pet_model = PetModelStub::all();

        $this->assertInstanceOf(\Bow\Support\Collection::class, $pet_model);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_chain_select(string $name)
    {
        $this->createTestingTable($name);

        $pet_collection_model = PetModelStub::where('id', 1)->select(['name'])->get();

        $this->assertInstanceOf(\Bow\Support\Collection::class, $pet_collection_model);
    }

    /**
     * @dataProvider connectionNameProvider
     * @throws ConnectionException
     */
    public function test_count_simple(string $name)
    {
        $this->createTestingTable($name);

        $pet_count = PetModelStub::count();

        $this->assertEquals(is_int($pet_count), true);
    }

    /**
     * @dataProvider connectionNameProvider
     * @throws ConnectionException
     */
    public function test_count_selected(string $name)
    {
        $this->createTestingTable($name);

        $pet_count_first = PetModelStub::count();
        $pet_count_second = PetModelStub::all()->count();

        $this->assertEquals($pet_count_first, $pet_count_second);
    }

    /**
     * @dataProvider connectionNameProvider
     * @throws ConnectionException
     */
    public function test_count_selected_with_collection_count(string $name)
    {
        $this->createTestingTable($name);

        $pet_count_first = PetModelStub::where('id', 1)->count();
        $pet_count_second = PetModelStub::all()->count();

        $this->assertNotEquals($pet_count_first, $pet_count_second);
    }

    /**
     * @dataProvider connectionNameProvider
     * @throws ConnectionException
     */
    public function test_insert_by_create_method(string $name)
    {
        $this->createTestingTable($name);

        $next_id = PetModelStub::all()->count() + 1;

        $insert_result = PetModelStub::create(['name' => 'Tor']);
        $select_result = PetModelStub::retrieveBy('id', $next_id)->first();

        $this->assertInstanceOf(PetModelStub::class, $insert_result);
        $this->assertInstanceOf(PetModelStub::class, $select_result);

        $this->assertEquals($insert_result->name, 'Tor');
        $this->assertEquals($insert_result->id, $next_id);

        $this->assertEquals($select_result->name, 'Tor');
        $this->assertEquals($select_result->id, $next_id);
    }

    /**
     * @dataProvider connectionNameProvider
     * @throws ConnectionException
     */
    public function test_save(string $name)
    {
        $this->createTestingTable($name);

        $pet = PetModelStub::first();
        $pet->name = "Lofi";
        $pet->save();

        $this->assertNotEquals($pet->name, 'Couli');
        $this->assertInstanceOf(PetModelStub::class, $pet);
    }

    /**
     * @dataProvider connectionNameProvider
     * @throws ConnectionException
     */
    public function test_retrieve_should_not_be_empty(string $name)
    {
        $this->createTestingTable($name);

        $pet = PetModelStub::retrieve(1);

        $this->assertEquals($pet->name, 'Couli');
    }

    /**
     * @dataProvider connectionNameProvider
     * @throws ConnectionException
     */
    public function test_retrieve_result_should_be_empty(string $name)
    {
        $this->createTestingTable($name);

        $pet = PetModelStub::retrieve(100);

        $this->assertNull($pet);
    }

    /**
     * @dataProvider connectionNameProvider
     * @throws ConnectionException
     */
    public function test_retrieve_by_result_should_not_be_empty(string $name)
    {
        $this->createTestingTable($name);

        $result = PetModelStub::retrieveBy('id', 1);
        $pet = $result->first();

        $this->assertNotEquals($result->count(), 0);
        $this->assertNotNull($pet);
        $this->assertEquals($pet->name, 'Couli');
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_retrieve_by_method_should_be_empty(string $name)
    {
        $this->createTestingTable($name);

        $result = PetModelStub::retrieveBy('id', 100);
        $pet = $result->first();

        $this->assertNull($pet);
    }

    /**
     * @return array
     */
    public function connectionNameProvider(): array
    {
        return [['mysql'], ['sqlite'], ['pgsql']];
    }
}
