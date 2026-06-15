<?php

namespace Bow\Tests\Database\Relation;

use Bow\Cache\Cache;
use Bow\Database\Barry\Relation;
use Bow\Database\Collection;
use Bow\Database\Database;
use Bow\Database\Migration\Table;
use Bow\Tests\Config\TestingConfiguration;
use Bow\Tests\Database\Stubs\MigrationExtendedStub;
use Bow\Tests\Database\Stubs\UuidItemModelStub;
use Bow\Tests\Database\Stubs\UuidOwnerModelStub;

/**
 * Guards eager loading of a belongsTo relation whose foreign key is null.
 *
 * When every parent's foreign key is null the eager loader has no keys to
 * match. It must NOT inject a placeholder value into the whereIn clause: a
 * literal like `0` is rejected by strongly typed columns (e.g. a PostgreSQL
 * `uuid` primary key throws "invalid input syntax for type uuid: 0").
 */
class EagerLoadingNullForeignKeyTest extends \PHPUnit\Framework\TestCase
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
                $migration->connection($name)->dropIfExists("uuid_items", false);
                $migration->connection($name)->dropIfExists("uuid_owners", false);
            } catch (\Exception $e) {
                // Ignore errors during cleanup
            }
        }
    }

    private function executeMigration(string $name): void
    {
        $migration = new MigrationExtendedStub();
        $migration->connection($name)->dropIfExists("uuid_items", false);
        $migration->connection($name)->dropIfExists("uuid_owners", false);

        $migration->connection($name)->create("uuid_owners", function (Table $table) {
            $table->addUuid("id", ["primary" => true]);
            $table->addString("name");
        }, false);

        $migration->connection($name)->create("uuid_items", function (Table $table) {
            $table->addUuid("id", ["primary" => true]);
            $table->addString("name");
            $table->addUuid("owner_id", ["nullable" => true]);
        }, false);
    }

    /**
     * Reproduces the reported crash: eager loading a belongsTo where every
     * foreign key is null must return null relations, not raise a type error.
     *
     * @dataProvider connectionNames
     */
    public function test_eager_belongs_to_with_all_null_foreign_keys(string $name)
    {
        $this->executeMigration($name);

        Database::connection($name)->statement(
            "INSERT INTO uuid_owners (id, name) VALUES "
            . "('11111111-1111-1111-1111-111111111111', 'alice')"
        );
        Database::connection($name)->statement(
            "INSERT INTO uuid_items (id, name, owner_id) VALUES "
            . "('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa', 'first', NULL), "
            . "('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb', 'second', NULL)"
        );

        $items = UuidItemModelStub::connection($name)->eager('owner')->get();

        $this->assertInstanceOf(Collection::class, $items);
        $this->assertCount(2, $items->all());

        foreach ($items as $item) {
            $this->assertNull($item->owner);
        }
    }

    /**
     * A mix of null and non-null foreign keys must still match the present ones
     * and leave the null ones unresolved.
     *
     * @dataProvider connectionNames
     */
    public function test_eager_belongs_to_with_some_null_foreign_keys(string $name)
    {
        $this->executeMigration($name);

        Database::connection($name)->statement(
            "INSERT INTO uuid_owners (id, name) VALUES "
            . "('11111111-1111-1111-1111-111111111111', 'alice')"
        );
        Database::connection($name)->statement(
            "INSERT INTO uuid_items (id, name, owner_id) VALUES "
            . "('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa', 'owned', "
            . "'11111111-1111-1111-1111-111111111111'), "
            . "('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb', 'orphan', NULL)"
        );

        $items = UuidItemModelStub::connection($name)->eager('owner')->get();

        $byName = [];
        foreach ($items as $item) {
            $byName[$item->name] = $item;
        }

        $this->assertInstanceOf(UuidOwnerModelStub::class, $byName['owned']->owner);
        $this->assertEquals('alice', $byName['owned']->owner->name);
        $this->assertNull($byName['orphan']->owner);
    }

    /**
     * Root-cause guard (no database server required): when every parent key is
     * null the eager query must NOT carry a value-based `in (...)` predicate.
     * The previous `[0]` sentinel produced `where id in (?)` bound to 0, which a
     * PostgreSQL uuid column rejects.
     */
    public function test_addEagerConstraints_injects_no_placeholder_when_all_keys_null()
    {
        // Use the in-memory sqlite connection so no server is needed.
        Database::connection('sqlite');

        $parents = [
            new UuidItemModelStub(['id' => 'a', 'name' => 'first', 'owner_id' => null]),
            new UuidItemModelStub(['id' => 'b', 'name' => 'second', 'owner_id' => null]),
        ];

        $relation = Relation::noConstraints(fn () => $parents[0]->owner());
        $relation->addEagerConstraints($parents);

        $sql = strtolower($relation->toSql());

        $this->assertStringNotContainsString(' in (', $sql);
        $this->assertStringNotContainsString('where', $sql);
    }
}
