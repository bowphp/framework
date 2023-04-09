<?php

namespace Bow\Tests\Database\Relation;

use Bow\Database\Database;
use Bow\Tests\Database\Stubs\PetMasterModelStub;
use Bow\Tests\Database\Stubs\PetModelStub;
use Bow\Tests\Database\Stubs\PetWithMasterModelStub;

class RelationQueryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Database
     */
    private Database $connection;

    public function test_get_the_relationship()
    {
        $pet = PetWithMasterModelStub::find(1);

        $master = $pet->master;

        $this->assertInstanceOf(PetMasterModelStub::class, $master);
        $this->assertEquals('didi', $master->name);
    }

    public static function setUpBeforeClass(): void
    {
        static::configureDatabase();

        Database::statement('
            create table if not exists pet_masters (
                id int primary key, name varchar(255)
            );');
        Database::statement('
            create table if not exists pet_with_masters (
                id int primary key,
                name varchar(255),
                master_id int,
                foreign key (master_id) references pet_masters(id)
            );
        ');

        // Create the records
        Database::table("pet_masters")->truncate();
        Database::table("pet_with_masters")->truncate();
        Database::statement("insert into pet_masters values (1, 'didi')");
        Database::statement("insert into pet_with_masters values (1, 'fluffy', 1), (2, 'dolly', 1)");
    }

    public static function tearDownAfterClass(): void
    {
        Database::statement('drop table if exists pet_with_masters');
        Database::statement('drop table if exists pet_masters');
    }

    private static function configureDatabase()
    {
        return Database::configure([
            'fetch' => \PDO::FETCH_OBJ,
            'default' => 'sqlite',
            'connections' => [
                'sqlite' => [
                    'driver' => 'sqlite',
                    'database' => sprintf('%s/bowphp_sqlite_testing_database.sqlite', sys_get_temp_dir()),
                    'prefix' => ''
                ]
            ]
        ]);
    }
}
