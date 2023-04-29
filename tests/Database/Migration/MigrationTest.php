<?php

namespace Bow\Tests\Database\Migration;

use Bow\Database\Database;
use Bow\Database\Exception\MigrationException;
use Bow\Database\Migration\Migration;
use Bow\Database\Migration\SQLGenerator;
use Bow\Tests\Config\TestingConfiguration;
use Bow\Tests\Database\Stubs\MigrationExtendedStub;
use Exception;

class MigrationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * The migration instance
     *
     * @var Migration
     */
    private $migration;

    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();
        Database::configure($config["database"]);
    }

    protected function setUp(): void
    {
        $this->migration = new MigrationExtendedStub();
        ob_start();
    }

    protected function tearDown(): void
    {
        ob_get_clean();
    }

    /**
     * @dataProvider connectionNames
     */
    public function test_addSql_method(string $name)
    {
        $this->migration->connection($name)->addSql('drop table if exists bow_testing;');
        $this->migration->connection($name)->addSql('create table if not exists bow_testing (name varchar(255));');

        $result = Database::connection($name)->insert("INSERT INTO bow_testing(name) VALUES('Bow Framework')");
        $this->assertEquals($result, 1);

        $result = Database::connection($name)->select('select * from bow_testing');
        $this->assertTrue(is_array($result));

        $this->migration->connection($name)->addSql('drop table if exists bow_testing;');

        $this->expectException(Exception::class);
        $result = Database::connection($name)->insert("INSERT INTO bow_testing(name) VALUES('Bow Framework')");
    }

    /**
     * @dataProvider connectionNames
     */
    public function test_create_fail(string $name)
    {
        Database::connection($name)->statement("drop table if exists bow_testing;");

        if ($name != 'sqlite') {
            $this->expectException(MigrationException::class);
        }

        $status = $this->migration->connection($name)->create('bow_testing', function (SQLGenerator $generator) {
            $generator->addColumn('id', 'string', ['size' => 225, 'primary' => true]);
            $generator->addColumn('name', 'typenotfound', ['size' => 225]); // Sqlite tranform the unknown type to NULL type
            $generator->addColumn('lastname', 'string', ['size' => 225]);
            $generator->addColumn('created_at', 'datetime');
        });

        if ($name == 'sqlite') {
            $this->assertInstanceOf(Migration::class, $status);
        }
    }

    /**
     * @dataProvider connectionNames
     */
    public function test_create_success(string $name)
    {
        Database::connection($name)->statement("drop table if exists bow_testing;");
        $status = $this->migration->connection($name)->create('bow_testing', function (SQLGenerator $generator) use ($name) {
            $generator->addColumn('id', 'string', ['size' => 225, 'primary' => true]);
            $generator->addColumn('name', 'string', ['size' => 225]);
            $generator->addColumn('lastname', 'string', ['size' => 225]);
            if ($name === 'pgsql') {
                $generator->addColumn('created_at', 'timestamp');
            } else {
                $generator->addColumn('created_at', 'datetime');
            }
        });
        $this->assertInstanceOf(Migration::class, $status);
    }

    /**
     * @dataProvider connectionNames
     */
    public function test_alter_success(string $name)
    {
        $this->migration->connection($name)->addSql('create table if not exists bow_testing (name varchar(255));');
        $status = $this->migration->connection($name)->alter('bow_testing', function (SQLGenerator $generator) {
            $generator->dropColumn('name');
            $generator->addColumn('age', 'int', ['size' => 11, 'default' => 12]);
        });

        $this->assertInstanceOf(Migration::class, $status);
    }

    /**
     * @dataProvider connectionNames
     */
    public function test_alter_fail(string $name)
    {
        $this->expectException(MigrationException::class);
        $this->migration->connection($name)->alter('bow_testing', function (SQLGenerator $generator) {
            $generator->dropColumn('name');
            $generator->dropColumn('lastname');
            $generator->addColumn('age', 'int', ['size' => 11, 'default' => 12]);
        });
    }

    public function connectionNames()
    {
        return [
            ['mysql'], ['sqlite'], ['pgsql']
        ];
    }
}
