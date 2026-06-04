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

class EagerLoadingQueryTest extends \PHPUnit\Framework\TestCase
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

    // ===== belongsTo =====

    /**
     * @dataProvider connectionNames
     */
    public function test_eager_belongs_to_matches_each_parent(string $name)
    {
        $this->executeMigration($name);
        $this->seedTestData($name);

        $expected = [1 => 'didi', 2 => 'didi', 3 => 'john', 4 => 'john', 5 => 'jane'];

        $pets = PetModelStub::connection($name)->eager('master')->get();

        $this->assertInstanceOf(Collection::class, $pets);

        foreach ($pets as $pet) {
            $master = $pet->master;
            $this->assertInstanceOf(PetMasterModelStub::class, $master);
            $this->assertEquals($expected[$pet->id], $master->name);
            $this->assertEquals($pet->master_id, $master->id);
        }
    }

    /**
     * Eager loading must pre-populate the relation so accessing it issues no
     * further query. We prove this by removing the related rows after the eager
     * load: a lazy fetch would now find nothing, an eager one still has the data.
     *
     * @dataProvider connectionNames
     */
    public function test_eager_belongs_to_avoids_followup_query(string $name)
    {
        $this->executeMigration($name);
        $this->seedTestData($name);

        $pets = PetModelStub::connection($name)->eager('master')->get();

        // Wipe the related table; an eager-loaded relation is unaffected.
        Database::connection($name)->statement("DELETE FROM pets");
        Database::connection($name)->statement("DELETE FROM pet_masters");

        foreach ($pets as $pet) {
            $master = $pet->master;
            $this->assertInstanceOf(PetMasterModelStub::class, $master);
            $this->assertEquals($pet->master_id, $master->id);
        }
    }

    /**
     * @dataProvider connectionNames
     */
    public function test_eager_returns_same_instance_on_repeat_access(string $name)
    {
        $this->executeMigration($name);
        $this->seedTestData($name);

        $pets = PetModelStub::connection($name)->eager('master')->get();
        $pet = $pets->first();

        $this->assertSame($pet->master, $pet->master);
    }

    // ===== hasMany =====

    /**
     * @dataProvider connectionNames
     */
    public function test_eager_has_many_matches_each_parent(string $name)
    {
        $this->executeMigration($name);
        $this->seedTestData($name);

        $expected = [1 => ['fluffy', 'dolly'], 2 => ['rex', 'max'], 3 => ['bella']];

        $masters = PetMasterModelStub::connection($name)->eager('pets')->get();

        Database::connection($name)->statement("DELETE FROM pets");

        foreach ($masters as $master) {
            $pets = $master->pets;
            $this->assertInstanceOf(Collection::class, $pets);
            $names = array_map(fn ($pet) => $pet->name, $pets->all());
            sort($names);
            $want = $expected[$master->id];
            sort($want);
            $this->assertEquals($want, $names);
        }
    }

    // ===== hasOne =====

    /**
     * @dataProvider connectionNames
     */
    public function test_eager_has_one_matches_each_parent(string $name)
    {
        $this->executeMigration($name);
        $this->seedTestData($name);

        $masters = PetMasterModelStub::connection($name)->eager('firstPet')->get();

        Database::connection($name)->statement("DELETE FROM pets");

        foreach ($masters as $master) {
            $pet = $master->firstPet;
            $this->assertInstanceOf(PetModelStub::class, $pet);
            $this->assertEquals($master->id, $pet->master_id);
        }
    }

    // ===== belongsToMany =====

    /**
     * @dataProvider connectionNames
     */
    public function test_eager_belongs_to_many_matches_each_parent(string $name)
    {
        $this->executeMigration($name);
        $this->seedTestData($name);

        $expected = [1 => 2, 2 => 2, 3 => 1];

        $masters = PetMasterModelStub::connection($name)->eager('manyPets')->get();

        Database::connection($name)->statement("DELETE FROM pets");

        foreach ($masters as $master) {
            $pets = $master->manyPets;
            $this->assertInstanceOf(Collection::class, $pets);
            $this->assertCount($expected[$master->id], $pets->all());
        }
    }

    // ===== multiple names =====

    /**
     * @dataProvider connectionNames
     */
    public function test_eager_multiple_relations_in_one_call(string $name)
    {
        $this->executeMigration($name);
        $this->seedTestData($name);

        $masters = PetMasterModelStub::connection($name)->eager(['pets', 'firstPet'])->get();

        Database::connection($name)->statement("DELETE FROM pets");

        foreach ($masters as $master) {
            $this->assertInstanceOf(Collection::class, $master->pets);
            $this->assertInstanceOf(PetModelStub::class, $master->firstPet);
        }
    }

    // ===== edge cases =====

    /**
     * @dataProvider connectionNames
     */
    public function test_eager_with_no_related_rows(string $name)
    {
        $this->executeMigration($name);
        Database::connection($name)->statement("INSERT INTO pet_masters VALUES (1, 'didi')");

        $masters = PetMasterModelStub::connection($name)->eager('pets')->get();

        foreach ($masters as $master) {
            $pets = $master->pets;
            $this->assertInstanceOf(Collection::class, $pets);
            $this->assertCount(0, $pets->all());
        }
    }

    /**
     * Guards the HasMany::addConstraints() fix: lazy access must filter on the
     * foreign key column (master_id), not the parent primary key.
     *
     * @dataProvider connectionNames
     */
    public function test_lazy_has_many_filters_on_foreign_key(string $name)
    {
        $this->executeMigration($name);
        $this->seedTestData($name);

        $master = PetMasterModelStub::connection($name)->retrieve(2);
        $pets = $master->pets;

        $names = array_map(fn ($pet) => $pet->name, $pets->all());
        sort($names);

        $this->assertEquals(['max', 'rex'], $names);
    }

    /**
     * The eager list must not leak onto a subsequent query reusing the shared
     * builder instance.
     *
     * @dataProvider connectionNames
     */
    public function test_eager_does_not_leak_into_next_query(string $name)
    {
        $this->executeMigration($name);
        $this->seedTestData($name);

        PetModelStub::connection($name)->eager('master')->get();

        // A plain fetch afterwards must not attempt to eager load anything.
        $pets = PetModelStub::connection($name)->all();

        $this->assertInstanceOf(Collection::class, $pets);
        $this->assertCount(5, $pets->all());
    }
}
