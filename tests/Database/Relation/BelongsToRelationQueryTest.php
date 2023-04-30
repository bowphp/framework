<?php

namespace Bow\Tests\Database\Relation;

use Bow\Database\Database;
use Bow\Database\Migration\SQLGenerator;
use Bow\Tests\Config\TestingConfiguration;
use Bow\Tests\Database\Stubs\MigrationExtendedStub;
use Bow\Tests\Database\Stubs\PetMasterModelStub;
use Bow\Tests\Database\Stubs\PetModelStub;

class BelongsToRelationQueryTest extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();
        Database::configure($config["database"]);
    }

    public function connectionNames()
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
    }

    /**
     * @dataProvider connectionNames
     */
    public function test_get_the_relationship(string $name)
    {
        $this->executeMigration($name);

        $pet = PetModelStub::connection($name)->find(1);
        $master = $pet->master;

        $this->assertInstanceOf(PetMasterModelStub::class, $master);
        $this->assertEquals('didi', $master->name);
    }

    public function executeMigration(string $name)
    {
        $migration = new MigrationExtendedStub();
        $migration->connection($name)->dropIfExists("pets");
        $migration->connection($name)->dropIfExists("pet_masters");
        $migration->connection($name)->create("pet_masters", function (SQLGenerator $table) {
            $table->addIncrement("id");
            $table->addString("name");
        });
        $migration->connection($name)->create("pets", function (SQLGenerator $table) {
            $table->addIncrement("id");
            $table->addString("name");
            $table->addInteger("master_id");
            $table->addForeign("master_id", [
                "table" => "pet_masters",
                "references" => "id",
                "on" => "delete cascade"
            ]);
        });
        Database::connection($name)->statement("insert into pet_masters values (1, 'didi')");
        Database::connection($name)->statement("insert into pets values (1, 'fluffy', 1), (2, 'dolly', 1)");
    }
}
