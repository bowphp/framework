<?php

namespace Bow\Tests\Database\Query;

use Bow\Database\Database;
use Bow\Database\Exception\ConnectionException;
use Bow\Tests\Config\TestingConfiguration;
use Bow\Tests\Database\Stubs\PetModelStub;
use Bow\Support\Collection;

class ModelQueryTest extends \PHPUnit\Framework\TestCase
{
    private static bool $configured = false;

    public static function setUpBeforeClass(): void
    {
        if (!static::$configured) {
            $config = TestingConfiguration::getConfig();
            Database::configure($config["database"]);
            static::$configured = true;
        }
    }

    public function tearDown(): void
    {
        // Clean up test table after each test for all connections
        foreach (['mysql', 'sqlite', 'pgsql'] as $name) {
            try {
                Database::connection($name)->statement('DROP TABLE IF EXISTS pets');
            } catch (\Exception $e) {
                // Ignore errors during cleanup
            }
        }
        parent::tearDown();
    }

    private function createTestingTable(string $name): void
    {
        $connection = Database::connection($name);

        $sql = match ($name) {
            'pgsql' => 'CREATE TABLE pets (id SERIAL PRIMARY KEY, name VARCHAR(255))',
            'sqlite' => 'CREATE TABLE pets (id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT, name VARCHAR(255))',
            'mysql' => 'CREATE TABLE pets (id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, name VARCHAR(255))',
            default => throw new \InvalidArgumentException("Unsupported database: $name")
        };

        $connection->statement('DROP TABLE IF EXISTS pets');
        $connection->statement($sql);
        $connection->insert('INSERT INTO pets(name) VALUES(:name)', [
            ['name' => 'Couli'],
            ['name' => 'Bobi']
        ]);
    }

    // ===== Basic Query Tests =====

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_the_first_result_should_be_the_instance_of_same_model(string $name)
    {
        $this->createTestingTable($name);

        $pet_model = new PetModelStub();
        $pet = $pet_model->first();

        $this->assertInstanceOf(PetModelStub::class, $pet);
        $this->assertIsInt($pet->id);
        $this->assertIsString($pet->name);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_first_returns_null_when_no_results(string $name)
    {
        $this->createTestingTable($name);
        Database::connection($name)->delete('DELETE FROM pets WHERE id > 0');

        $pet = PetModelStub::first();

        $this->assertNull($pet);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_all_method_returns_collection(string $name)
    {
        $this->createTestingTable($name);

        $pet_collection = PetModelStub::all();

        $this->assertInstanceOf(Collection::class, $pet_collection);
        $this->assertCount(2, $pet_collection);
        $this->assertContainsOnlyInstancesOf(PetModelStub::class, $pet_collection);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_get_method_returns_collection(string $name)
    {
        $this->createTestingTable($name);

        $pets = PetModelStub::where('id', '>', 0)->get();

        $this->assertInstanceOf(Collection::class, $pets);
        $this->assertCount(2, $pets);
    }

    // ===== Query Builder Methods =====

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
        $this->assertEquals('Couli', $pet->name);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_where_method(string $name)
    {
        $this->createTestingTable($name);

        $pets = PetModelStub::where('name', 'Couli')->get();

        $this->assertCount(1, $pets);
        $this->assertEquals('Couli', $pets->first()->name);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_where_with_operator(string $name)
    {
        $this->createTestingTable($name);

        $pets = PetModelStub::where('id', '>=', 1)->get();

        $this->assertCount(2, $pets);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_where_in_method(string $name)
    {
        $this->createTestingTable($name);

        $pets = PetModelStub::whereIn('name', ['Couli', 'Bobi'])->get();

        $this->assertCount(2, $pets);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_where_not_in_method(string $name)
    {
        $this->createTestingTable($name);

        $pets = PetModelStub::whereNotIn('name', ['Couli'])->get();

        $this->assertCount(1, $pets);
        $this->assertEquals('Bobi', $pets->first()->name);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_order_by_method(string $name)
    {
        $this->createTestingTable($name);

        $pets = PetModelStub::orderBy('name', 'DESC')->get();

        // DESC order: Couli comes after Bobi alphabetically, so Bobi is last
        $this->assertEquals('Bobi', $pets->first()->name);
        $this->assertEquals('Couli', $pets->last()->name);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_select_specific_columns(string $name)
    {
        $this->createTestingTable($name);

        $pets = PetModelStub::select(['id', 'name'])->get();

        $this->assertCount(2, $pets);
        $pet = $pets->first();
        // Model has these as attributes, check they exist
        $this->assertNotNull($pet->id);
        $this->assertNotNull($pet->name);
    }

    // ===== Collection Tests =====

    /**
     * @dataProvider connectionNameProvider
     * @throws ConnectionException
     */
    public function test_instance_off_collection(string $name)
    {
        $this->createTestingTable($name);

        $pet_model = PetModelStub::all();

        $this->assertInstanceOf(Collection::class, $pet_model);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_chain_select(string $name)
    {
        $this->createTestingTable($name);

        $pet_collection_model = PetModelStub::where('id', 1)->select(['name'])->get();

        $this->assertInstanceOf(Collection::class, $pet_collection_model);
        $this->assertCount(1, $pet_collection_model);
    }

    // ===== Count Tests =====

    /**
     * @dataProvider connectionNameProvider
     * @throws ConnectionException
     */
    public function test_count_simple(string $name)
    {
        $this->createTestingTable($name);

        $pet_count = PetModelStub::count();

        $this->assertIsInt($pet_count);
        $this->assertEquals(2, $pet_count);
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
     */
    public function test_count_with_where_clause(string $name)
    {
        $this->createTestingTable($name);

        $count = PetModelStub::where('name', 'Couli')->count();

        $this->assertEquals(1, $count);
    }

    // ===== Create and Update Tests =====

    /**
     * @dataProvider connectionNameProvider
     * @throws ConnectionException
     */
    public function test_insert_by_create_method(string $name)
    {
        $this->createTestingTable($name);

        $next_id = PetModelStub::all()->count() + 1;

        $insert_result = PetModelStub::create(['name' => 'Tor']);
        $insert_result->persist();
        $select_result = PetModelStub::retrieveBy('id', $next_id)->first();

        $this->assertInstanceOf(PetModelStub::class, $insert_result);
        $this->assertInstanceOf(PetModelStub::class, $select_result);

        $this->assertEquals('Tor', $insert_result->name);
        $this->assertEquals($next_id, $insert_result->id);

        $this->assertEquals('Tor', $select_result->name);
        $this->assertEquals($next_id, $select_result->id);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_create_without_persist(string $name)
    {
        $this->createTestingTable($name);

        $pet = PetModelStub::create(['name' => 'NewPet']);

        $this->assertInstanceOf(PetModelStub::class, $pet);
        $this->assertEquals('NewPet', $pet->name);
        // Not persisted yet, so shouldn't have an ID
        $this->assertNull($pet->id);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_update_model_attributes(string $name)
    {
        $this->createTestingTable($name);

        $pet = PetModelStub::first();
        $originalName = $pet->name;
        $pet->name = 'UpdatedName';

        $this->assertEquals('UpdatedName', $pet->name);
        $this->assertNotEquals($originalName, $pet->name);
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
        $pet->persist();

        $this->assertEquals('Lofi', $pet->name);
        $this->assertNotEquals('Couli', $pet->name);
        $this->assertInstanceOf(PetModelStub::class, $pet);

        // Verify persistence
        $updatedPet = PetModelStub::retrieve($pet->id);
        $this->assertEquals('Lofi', $updatedPet->name);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_persist_new_model(string $name)
    {
        $this->createTestingTable($name);

        $pet = new PetModelStub();
        $pet->name = 'NewDog';
        $pet->persist();

        $this->assertIsInt($pet->id);
        $this->assertGreaterThan(2, $pet->id);

        $foundPet = PetModelStub::retrieve($pet->id);
        $this->assertEquals('NewDog', $foundPet->name);
    }

    // ===== Retrieve Tests =====

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

        $this->assertCount(1, $result);
        $this->assertNotNull($pet);
        $this->assertInstanceOf(PetModelStub::class, $pet);
        $this->assertEquals('Couli', $pet->name);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_retrieve_by_with_multiple_results(string $name)
    {
        $this->createTestingTable($name);
        Database::connection($name)->insert('INSERT INTO pets(name) VALUES(:name)', [
            ['name' => 'Couli']
        ]);

        $result = PetModelStub::retrieveBy('name', 'Couli');

        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(PetModelStub::class, $result);
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

    // ===== Delete Tests =====

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_delete_model(string $name)
    {
        $this->createTestingTable($name);

        $pet = PetModelStub::first();
        $petId = $pet->id;
        $pet->delete();

        $deletedPet = PetModelStub::retrieve($petId);
        $this->assertNull($deletedPet);

        $remainingCount = PetModelStub::count();
        $this->assertEquals(1, $remainingCount);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_delete_with_where_clause(string $name)
    {
        $this->createTestingTable($name);

        $deleted = PetModelStub::where('name', 'Couli')->delete();

        $this->assertGreaterThan(0, $deleted);
        $remainingPets = PetModelStub::all();
        $this->assertCount(1, $remainingPets);
        $this->assertEquals('Bobi', $remainingPets->first()->name);
    }

    // ===== Edge Cases and Special Scenarios =====

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_empty_where_returns_all(string $name)
    {
        $this->createTestingTable($name);

        $pets = PetModelStub::where('id', '>', 0)->get();

        $this->assertCount(2, $pets);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_chaining_multiple_where_clauses(string $name)
    {
        $this->createTestingTable($name);

        $pets = PetModelStub::where('id', '>', 0)
            ->where('name', 'Couli')
            ->get();

        $this->assertCount(1, $pets);
        $this->assertEquals('Couli', $pets->first()->name);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_model_to_array(string $name)
    {
        $this->createTestingTable($name);

        $pet = PetModelStub::first();
        $array = $pet->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_collection_to_array(string $name)
    {
        $this->createTestingTable($name);

        $pets = PetModelStub::all();
        $array = $pets->toArray();

        $this->assertIsArray($array);
        $this->assertCount(2, $array);
        $this->assertIsArray($array[0]);
    }

    /**
     * @return array
     */
    public function connectionNameProvider(): array
    {
        return [['mysql'], ['sqlite'], ['pgsql']];
    }
}
