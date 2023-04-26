<?php

namespace Bow\Tests\Database\Query;

use Bow\Database\Database;
use Bow\Tests\Config\TestingConfiguration;

class DatabaseQueryTest extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();
        Database::configure($config["database"]);
    }

    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @return array
     */
    public function connectionNameProvider()
    {
        return [['mysql'], ['sqlite'], ['pgsql']];
    }

    /**
     * @dataProvider connectionNameProvider
     * @param string $name
     */
    public function test_instance_of_database(string $name)
    {
        $this->assertInstanceOf(Database::class, Database::connection($name));
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_get_database_connection(string $name)
    {
        $instance = Database::connection($name);
        $adapter = $instance->getAdapterConnection();

        $this->assertEquals($name, $adapter->getName());
        $this->assertInstanceOf(Database::class, $instance);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_simple_insert_table(string $name)
    {
        $database = Database::connection($name);
        $this->createTestingTable();

        $result = $database->insert("insert into pets values(1, 'Bob'), (2, 'Milo');");

        $this->assertEquals($result, 2);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_array_insert_table(string $name)
    {
        $database = Database::connection($name);
        $this->createTestingTable();

        $result = $database->insert("insert into pets values(:id, :name);", [
            "id" => 1,
            'name' => 'Popy'
        ]);

        $this->assertEquals($result, 1);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_array_multile_insert_table(string $name)
    {
        $database = Database::connection($name);
        $this->createTestingTable();

        $result = $database->insert("insert into pets values(:id, :name);", [
            [ "id" => 1, 'name' => 'Ploy'],
            [ "id" => 2, 'name' => 'Cesar'],
            [ "id" => 3, 'name' => 'Louis'],
        ]);

        $this->assertEquals($result, 3);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_select_table(string $name)
    {
        $database = Database::connection($name);
        $this->createTestingTable();

        $pets = $database->select("select * from pets");

        $this->assertTrue(is_array($pets));
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_select_table_and_check_item_length(string $name)
    {
        $database = Database::connection($name);
        $this->createTestingTable();

        $database->insert("insert into pets values(:id, :name);", [
            ["id" => 1, 'name' => 'Ploy'],
            ["id" => 2, 'name' => 'Cesar'],
            ["id" => 3, 'name' => 'Louis'],
        ]);

        $pets = $database->select("select * from pets");

        $this->assertEquals(count($pets), 3);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_select_with_get_one_element_table(string $name)
    {
        $database = Database::connection($name);
        $this->createTestingTable();

        $database->insert("insert into pets values(:id, :name);", ["id" => 1, 'name' => 'Ploy']);

        $pets = $database->select("select * from pets where id = :id", ['id' => 1]);

        $this->assertTrue(is_array($pets));
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_select_with_not_get_element_table(string $name)
    {
        $database = Database::connection($name);
        $this->createTestingTable();

        $pets = $database->select("select * from pets where id = :id", ['id' => 7]);

        $this->assertTrue(is_array($pets));
        $this->assertTrue(count($pets) == 0);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_select_one_table(string $name)
    {
        $database = Database::connection($name);
        $this->createTestingTable();

        $database->insert("insert into pets values(:id, :name);", ["id" => 1, 'name' => 'Ploy']);

        $pet = $database->selectOne("select * from pets where id = :id", ['id' => 1]);

        $this->assertTrue(!is_array($pet));
        $this->assertTrue(is_object($pet));
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_update_table(string $name)
    {
        $database = Database::connection($name);
        $this->createTestingTable();

        $database->insert("insert into pets values(:id, :name);", ["id" => 1, 'name' => 'Ploy']);

        $result = $database->update("update pets set name = 'Bob' where id = :id", ['id' => 1]);
        $this->assertEquals($result, 1);

        $pet = $database->selectOne("select * from pets where id = :id", ['id' => 1]);
        $this->assertEquals($pet->name, 'Bob');
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_delete_table(string $name)
    {
        $database = Database::connection($name);
        $this->createTestingTable();

        $database->insert("insert into pets values(:id, :name);", ["id" => 1, 'name' => 'Ploy']);

        $result = $database->delete("delete from pets where id = :id", ['id' => 1]);
        $this->assertEquals($result, 1);

        $result = $database->delete("delete from pets where id = :id", ['id' => 1]);
        $this->assertEquals($result, 0);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_transaction_table(string $name)
    {
        $database = Database::connection($name);
        $this->createTestingTable();

        $database->insert("insert into pets values(:id, :name);", ["id" => 1, 'name' => 'Ploy']);
        $result = 0;
        $database->startTransaction(function () use ($database, &$result) {
            $result = $database->delete("delete from pets where id = :id", ['id' => 1]);
            $this->assertEquals($database->inTransaction(), true);
        });

        $this->assertEquals($result, 1);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_rollback_table(string $name)
    {
        $result = 0;

        $database = Database::connection($name);
        $this->createTestingTable();

        $database->insert("insert into pets values(:id, :name);", ["id" => 1, 'name' => 'Ploy']);

        $database->startTransaction();
        $result = $database->delete("delete from pets where id = 1");

        $this->assertEquals($database->inTransaction(), true);
        $this->assertEquals($result, 1);

        $database->rollback();

        $pet = $database->selectOne("select * from pets where id = 1");

        if (!$database->inTransaction()) {
            $result = 0;
        }

        $this->assertEquals($result, 0);
        $this->assertEquals($pet->name, "Ploy");
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_stement_table(string $name)
    {
        $database = Database::connection($name);
        $this->createTestingTable();

        $result = $database->statement("drop table pets");

        $this->assertEquals(is_bool($result), true);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_stement_table_2(string $name)
    {
        $database = Database::connection($name);
        $this->createTestingTable();

        $result = $database->statement('create table if not exists pets (id int primary key, name varchar(255))');

        $this->assertEquals(is_bool($result), true);
    }

    public function createTestingTable()
    {
        Database::statement('drop table if exists pets');
        Database::statement(
            'create table pets (id int primary key, name varchar(255))'
        );
    }
}
