<?php

namespace Bow\Tests\Database\Query;

use Bow\Database\Database;
use Bow\Database\Exception\ConnectionException;
use Bow\Database\Exception\QueryBuilderException;
use Bow\Database\QueryBuilder;
use Bow\Tests\Config\TestingConfiguration;

class QueryBuilderTest extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();
        Database::configure($config["database"]);
    }

    public function setUp(): void
    {
        Database::statement('drop table if exists pets');
        Database::statement(
            'create table pets (id int primary key, name varchar(255))'
        );
        Database::table("pets")->truncate();
    }

    /**
     * @return Database
     */
    public function test_get_database_connection()
    {
        $instance = Database::getInstance();

        $this->assertInstanceOf(Database::class, $instance);

        return Database::getInstance();
    }

    /**
     * @depends      test_get_database_connection
     * @dataProvider connectionNameProvider
     * @param Database $database
     */
    public function test_get_instance(string $name, Database $database)
    {
        $this->createTestingTable($name);
        $this->assertInstanceOf(QueryBuilder::class, $database->connection($name)->table('pets'));
    }

    public function createTestingTable(string $name): void
    {
        Database::connection($name)->statement('drop table if exists pets');
        Database::connection($name)->statement(
            'create table pets (id int primary key, name varchar(255))'
        );
    }

    /**
     * @depends      test_get_database_connection
     * @dataProvider connectionNameProvider
     * @param string $name
     * @param Database $database
     * @throws ConnectionException
     */
    public function test_insert_by_passing_a_array(string $name, Database $database)
    {
        $this->createTestingTable($name);
        $table = $database->connection($name)->table('pets');
        $table->truncate();

        $result = $table->insert([
            'id' => 1,
            'name' => 'Milou'
        ]);

        $this->assertEquals($result, 1);
    }

    /**
     * @depends      test_get_database_connection
     * @dataProvider connectionNameProvider
     * @param string $name
     * @param Database $database
     * @throws ConnectionException
     */
    public function test_insert_by_passing_a_multiple_array(string $name, Database $database)
    {
        $this->createTestingTable($name);
        $table = $database->connection($name)->table('pets');
        // We keep clear the pet table
        $table->truncate();

        $r = $table->insert([
            ['id' => 1, 'name' => 'Milou'],
            ['id' => 2, 'name' => 'Foli'],
            ['id' => 3, 'name' => 'Bob'],
        ]);

        $this->assertEquals($r, 3);
    }

    /**
     * @depends      test_get_database_connection
     * @dataProvider connectionNameProvider
     * @param string $name
     * @param Database $database
     * @throws ConnectionException
     */
    public function test_select_rows(string $name, Database $database)
    {
        $this->createTestingTable($name);
        $table = $database->connection($name)->table('pets');

        $this->assertInstanceOf(QueryBuilder::class, $table);

        $pets = $table->get();

        $this->assertEquals(is_array($pets), true);
    }

    /**
     * @depends      test_get_database_connection
     * @dataProvider connectionNameProvider
     * @param string $name
     * @param Database $database
     * @throws ConnectionException
     */
    public function test_select_chain_rows(string $name, Database $database)
    {
        $this->createTestingTable($name);
        $table = $database->connection($name)->table('pets');
        $pets = $table->select(['name'])->get();

        $this->assertEquals(is_array($pets), true);
    }

    /**
     * @depends      test_get_database_connection
     * @dataProvider connectionNameProvider
     * @param string $name
     * @param Database $database
     * @throws ConnectionException
     */
    public function test_select_first_chain_rows(string $name, Database $database)
    {
        $this->createTestingTable($name);

        $table = $database->connection($name)->table('pets');
        $table->insert([
            ['id' => 1, 'name' => 'Milou'],
            ['id' => 2, 'name' => 'Foli'],
            ['id' => 3, 'name' => 'Bob'],
        ]);

        $pet = $table->select(['name'])->first();

        $this->assertInstanceOf(\StdClass::class, $pet);
    }

    /**
     * @depends      test_get_database_connection
     * @dataProvider connectionNameProvider
     * @param string $name
     * @param Database $database
     * @throws ConnectionException
     * @throws QueryBuilderException
     */
    public function test_where_in_chain_rows(string $name, Database $database)
    {
        $this->createTestingTable($name);
        $table = $database->connection($name)->table('pets');
        $pets = $table->whereIn('id', [1, 3])->get();

        $this->assertEquals(is_array($pets), true);
    }

    /**
     * @depends      test_get_database_connection
     * @dataProvider connectionNameProvider
     * @param string $name
     * @param Database $database
     * @throws ConnectionException
     */
    public function test_where_null_chain_rows(string $name, Database $database)
    {
        $this->createTestingTable($name);
        $table = $database->connection($name)->table('pets');
        $pets = $table->whereNull('name')->get();

        $this->assertEquals(is_array($pets), true);
    }

    /**
     * @depends      test_get_database_connection
     * @dataProvider connectionNameProvider
     * @param string $name
     * @param Database $database
     * @throws ConnectionException
     * @throws QueryBuilderException
     */
    public function test_where_between_chain_rows(string $name, Database $database)
    {
        $this->createTestingTable($name);
        $table = $database->connection($name)->table('pets');
        $pets = $table->whereBetween('id', [1, 3])->get();

        $this->assertEquals(is_array($pets), true);
    }

    /**
     * @depends      test_get_database_connection
     * @dataProvider connectionNameProvider
     * @param string $name
     * @param Database $database
     * @throws ConnectionException
     */
    public function test_where_not_between_chain_rows(string $name, Database $database)
    {
        $this->createTestingTable($name);
        $table = $database->connection($name)->table('pets');
        $pets = $table->whereNotBetween('id', [1, 3])->get();

        $this->assertEquals(is_array($pets), true);
    }

    /**
     * @depends      test_get_database_connection
     * @dataProvider connectionNameProvider
     * @param string $name
     * @param Database $database
     * @throws ConnectionException
     * @throws QueryBuilderException
     */
    public function test_where_not_null_chain_rows(string $name, Database $database)
    {
        $this->createTestingTable($name);
        $table = $database->connection($name)->table('pets');
        $pets = $table->whereNotIn('id', [1, 3])->get();

        $this->assertEquals(is_array($pets), true);
    }

    /**
     * @depends      test_get_database_connection
     * @dataProvider connectionNameProvider
     * @param string $name
     * @param Database $database
     * @throws ConnectionException
     * @throws QueryBuilderException
     */
    public function test_where_chain_rows(string $name, Database $database)
    {
        $this->createTestingTable($name);
        $table = $database->connection($name)->table('pets');

        $pets = $table->where('id', 1)->orWhere('name', 1)
            ->whereNull('name')
            ->whereBetween('id', [1, 3])
            ->whereNotBetween('id', [1, 3])->get();

        $this->assertEquals(is_array($pets), true);
    }

    /**
     * @return array
     */
    public function connectionNameProvider(): array
    {
        return [['mysql'], ['sqlite'], ['pgsql']];
    }
}
