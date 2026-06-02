<?php

namespace Bow\Tests\Database\Relation;

use Bow\Cache\Cache;
use Bow\Database\Collection;
use Bow\Database\Database;
use Bow\Database\Migration\Table;
use Bow\Tests\Config\TestingConfiguration;
use Bow\Tests\Database\Stubs\MigrationExtendedStub;
use Bow\Tests\Database\Stubs\PetMasterModelStub;
use Bow\Tests\Database\Stubs\PetModelStub;

class BelongsToRelationQueryTest extends \PHPUnit\Framework\TestCase
{
    private static bool $configured = false;

    public static function setUpBeforeClass(): void
    {
        if (!static::$configured) {
            $config = TestingConfiguration::getConfig();
            Database::configure($config["database"]);
            Cache::configure($config["cache"]);
            static::$configured = true;
        }
    }

    /**
     * @return array
     */
    public function connectionNames(): array
    {
        return [
            ['mysql'], ['sqlite'], ['pgsql']
        ];
    }

    public function setUp(): void
    {
        ob_start();
    }

    public function tearDown(): void
    {
        ob_get_clean();

        // Clean up test tables after each test
        foreach (['mysql', 'sqlite', 'pgsql'] as $name) {
            try {
                $migration = new MigrationExtendedStub();
                $migration->connection($name)->dropIfExists("pets", false);
                $migration->connection($name)->dropIfExists("pet_masters", false);
            } catch (\Exception $e) {
                // Ignore errors during cleanup
            }
        }
    }

    private function executeMigration(string $name): void
    {
        $migration = new MigrationExtendedStub();
        $migration->connection($name)->dropIfExists("pets", false);
        $migration->connection($name)->dropIfExists("pet_masters", false);

        $migration->connection($name)->create("pet_masters", function (Table $table) {
            $table->addIncrement("id");
            $table->addString("name");
        }, false);

        $migration->connection($name)->create("pets", function (Table $table) {
            $table->addIncrement("id");
            $table->addString("name");
            $table->addInteger("master_id");
            $table->addForeign("master_id", [
                "table" => "pet_masters",
                "references" => "id",
                "on" => "delete cascade"
            ]);
        }, false);
    }

    private function seedTestData(string $name): void
    {
        Database::connection($name)->statement("INSERT INTO pet_masters VALUES (1, 'didi'), (2, 'john'), (3, 'jane')");
        Database::connection($name)->statement("INSERT INTO pets VALUES (1, 'fluffy', 1), (2, 'dolly', 1), (3, 'rex', 2), (4, 'max', 2), (5, 'bella', 3)");
    }

    // ===== Basic BelongsTo Relationship Tests =====

    /**
     * @dataProvider connectionNames
     */
    public function test_get_the_relationship(string $name)
    {
        $this->executeMigration($name);
        $this->seedTestData($name);

        $pet = PetModelStub::connection($name)->retrieve(1);
        $master = $pet->master;

        $this->assertInstanceOf(PetMasterModelStub::class, $master);
        $this->assertEquals('didi', $master->name);
    }

    /**
     * @dataProvider connectionNames
     */
    public function test_relationship_returns_correct_owner(string $name)
    {
        $this->executeMigration($name);
        $this->seedTestData($name);

        $pet = PetModelStub::connection($name)->retrieve(1);
        $master = $pet->master;

        $this->assertInstanceOf(PetMasterModelStub::class, $master);
        $this->assertEquals(1, $master->id);
        $this->assertEquals('didi', $master->name);
    }

    /**
     * @dataProvider connectionNames
     */
    public function test_multiple_pets_same_master(string $name)
    {
        $this->executeMigration($name);
        $this->seedTestData($name);

        $pet1 = PetModelStub::connection($name)->retrieve(1);
        $pet2 = PetModelStub::connection($name)->retrieve(2);

        $this->assertEquals($pet1->master->id, $pet2->master->id);
        $this->assertEquals('didi', $pet1->master->name);
        $this->assertEquals('didi', $pet2->master->name);
    }

    /**
     * @dataProvider connectionNames
     */
    public function test_lazy_loading_relationship(string $name)
    {
        $this->executeMigration($name);
        $this->seedTestData($name);

        $pet = PetModelStub::connection($name)->retrieve(1);

        // Master should not be loaded yet (lazy loading)
        $this->assertIsObject($pet);

        // Access the relationship
        $master = $pet->master;

        $this->assertInstanceOf(PetMasterModelStub::class, $master);
        $this->assertEquals('didi', $master->name);
    }

    /**
     * @dataProvider connectionNames
     */
    public function test_multiple_relationship_accesses(string $name)
    {
        $this->executeMigration($name);
        $this->seedTestData($name);

        $pet = PetModelStub::connection($name)->retrieve(1);

        // Access the relationship multiple times
        $master1 = $pet->master;
        $master2 = $pet->master;

        $this->assertInstanceOf(PetMasterModelStub::class, $master1);
        $this->assertInstanceOf(PetMasterModelStub::class, $master2);
        $this->assertEquals($master1->id, $master2->id);
        $this->assertEquals($master1->name, $master2->name);
    }

    /**
     * @dataProvider connectionNames
     */
    public function test_relationship_in_loop_returns_correct_owner_per_pet(string $name)
    {
        $this->executeMigration($name);
        $this->seedTestData($name);

        // Ensure no stale relation cache leaks between pets
        Cache::store('file')->clear();

        $expected = [
            1 => 'didi', // fluffy -> master 1
            2 => 'didi', // dolly  -> master 1
            3 => 'john', // rex    -> master 2
            4 => 'john', // max    -> master 2
            5 => 'jane', // bella  -> master 3
        ];

        $pets = PetModelStub::connection($name)->all();

        foreach ($pets as $pet) {
            $master = $pet->master;

            $this->assertInstanceOf(PetMasterModelStub::class, $master);
            $this->assertEquals(
                $expected[$pet->id],
                $master->name,
                "Pet #{$pet->id} should belong to master '{$expected[$pet->id]}'"
            );
            $this->assertEquals($pet->master_id, $master->id);
        }
    }

    // ===== Relationship Data Integrity Tests =====

    /**
     * @dataProvider connectionNames
     */
    public function test_relationship_with_all_pets(string $name)
    {
        $this->executeMigration($name);
        $this->seedTestData($name);

        $pets = PetModelStub::connection($name)->all();

        $this->assertInstanceOf(Collection::class, $pets);
        $this->assertCount(5, $pets);

        // Iterate directly over Collection (it's IteratorAggregate)
        foreach ($pets as $pet) {
            $master = $pet->master;
            $this->assertInstanceOf(PetMasterModelStub::class, $master);
            $this->assertIsInt($master->id);
            $this->assertIsString($master->name);
        }
    }

    /**
     * @dataProvider connectionNames
     */
    public function test_relationship_foreign_key_value(string $name)
    {
        $this->executeMigration($name);
        $this->seedTestData($name);

        $pet = PetModelStub::connection($name)->retrieve(1);
        $master = $pet->master;

        // Verify the foreign key matches the master's id
        $this->assertEquals($pet->master_id, $master->id);
    }

    /**
     * @dataProvider connectionNames
     */
    public function test_relationship_properties_accessible(string $name)
    {
        $this->executeMigration($name);
        $this->seedTestData($name);

        $pet = PetModelStub::connection($name)->retrieve(1);
        $master = $pet->master;

        // Verify properties are accessible
        $this->assertIsInt($master->id);
        $this->assertIsString($master->name);
        $this->assertEquals(1, $master->id);
        $this->assertEquals('didi', $master->name);
    }

    // ===== Edge Cases =====

    /**
     * @dataProvider connectionNames
     */
    public function test_relationship_with_first_pet(string $name)
    {
        $this->executeMigration($name);
        $this->seedTestData($name);

        $pet = PetModelStub::connection($name)->first();
        $master = $pet->master;

        $this->assertInstanceOf(PetMasterModelStub::class, $master);
        $this->assertIsInt($master->id);
    }

    /**
     * @dataProvider connectionNames
     */
    public function test_relationship_with_specific_pet(string $name)
    {
        $this->executeMigration($name);
        $this->seedTestData($name);

        // Get a specific pet and verify it has a master
        $pet = PetModelStub::connection($name)->first();
        $master = $pet->master;

        $this->assertInstanceOf(PetMasterModelStub::class, $master);
        $this->assertIsInt($master->id);
        $this->assertIsString($master->name);
        $this->assertContains($master->name, ['didi', 'john', 'jane']);
    }

    /**
     * @dataProvider connectionNames
     */
    public function test_relationship_chain_with_where_clause(string $name)
    {
        $this->executeMigration($name);
        $this->seedTestData($name);

        $pets = PetModelStub::connection($name)->where('master_id', 1)->get();

        $this->assertInstanceOf(Collection::class, $pets);
        $this->assertCount(2, $pets);

        // Iterate directly over Collection
        foreach ($pets as $pet) {
            $this->assertEquals(1, $pet->master_id);
            $this->assertEquals('didi', $pet->master->name);
        }
    }

    /**
     * @dataProvider connectionNames
     */
    public function test_relationship_verifies_correct_count_per_master(string $name)
    {
        $this->executeMigration($name);
        $this->seedTestData($name);

        // Count pets for each master
        $master1Pets = PetModelStub::connection($name)->where('master_id', 1)->count();
        $master2Pets = PetModelStub::connection($name)->where('master_id', 2)->count();
        $master3Pets = PetModelStub::connection($name)->where('master_id', 3)->count();

        $this->assertEquals(2, $master1Pets);
        $this->assertEquals(2, $master2Pets);
        $this->assertEquals(1, $master3Pets);
    }
}
