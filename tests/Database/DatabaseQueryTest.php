<?php

namespace Bow\Tests\Database;

use Bow\Database\Database;
use Bow\Tests\Config\TestingConfiguration;

class DatabaseQueryTest extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();
        Database::configure($config["database"]);
    }

    public static function tearDownAfterClass(): void
    {
        Database::statement('drop table pets');
    }

    public function setUp(): void
    {
        Database::statement(
            'create table if not exists pets (id int primary key, name varchar(255))'
        );
    }

    /**
     * @return array
     */
    public function connectionNameProvider()
    {
        return [['mysql'], ['sqlite']];
    }

    public function test_get_database_connection()
    {
        $instance = Database::getInstance();

        $this->assertInstanceOf(\Bow\Database\Database::class, $instance);

        return Database::getInstance();
    }

    /**
     * @dataProvider connectionNameProvider
     * @param string $name
     */
    public function test_instance_of_database(string $name)
    {
        $this->assertInstanceOf(Database::class, \Bow\Database\Database::connection($name));
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_simple_insert_table(Database $database)
    {
        $result = $database->insert("insert into pets values(1, 'Bob'), (2, 'Milo');");

        $this->assertEquals($result, 2);
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_array_insert_table(Database $database)
    {
        $result = $database->insert("insert into pets values(:id, :name);", [
            "id" => 3,
            'name' => 'Popy'
        ]);

        $this->assertEquals($result, 1);
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_array_multile_insert_table(Database $database)
    {
        $result = $database->insert("insert into pets values(:id, :name);", [
            [ "id" => 4, 'name' => 'Ploy'],
            [ "id" => 5, 'name' => 'Cesar'],
            [ "id" => 6, 'name' => 'Louis'],
        ]);

        $this->assertEquals($result, 3);
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_select_table(Database $database)
    {
        $pets = $database->select("select * from pets");

        $this->assertTrue(is_array($pets));
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_select_table_and_check_item_length(Database $database)
    {
        $pets = $database->select("select * from pets");

        $this->assertEquals(count($pets), 6);
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_select_with_get_one_element_table(Database $database)
    {
        $pets = $database->select("select * from pets where id = :id", ['id' => 1]);

        $this->assertTrue(is_array($pets));
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_select_with_not_get_element_table(Database $database)
    {
        $pets = $database->select("select * from pets where id = :id", [
            'id' => 7
        ]);

        $this->assertTrue(is_array($pets));
        $this->assertTrue(count($pets) == 0);
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_select_one_table(Database $database)
    {
        $pet = $database->selectOne("select * from pets where id = :id", [
            'id' => 1
        ]);

        $this->assertTrue(!is_array($pet));
        $this->assertTrue(is_object($pet));
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_update_table($database)
    {
        $result = $database->update("update pets set name = 'Bob' where id = :id", [
            'id' => 1
        ]);

        $this->assertEquals($result, 1);
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_delete_table(Database $database)
    {
        $result = $database->delete("delete from pets where id = :id", ['id' => 1]);
        $this->assertEquals($result, 1);

        $result = $database->delete("delete from pets where id = :id", ['id' => 1]);
        $this->assertEquals($result, 0);
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_transaction_table(Database $database)
    {
        $database->startTransaction(function () use ($database) {
            $result = $database->delete("delete from pets where id = :id", ['id' => 2]);

            $this->assertEquals($database->inTransaction(), true);
            $this->assertEquals($result, 1);
        });
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_rollback_table(Database $database)
    {
        $result = 0;

        $database->startTransaction();

        $result = $database->delete("delete from pets where id = 3");

        $this->assertEquals($database->inTransaction(), true);
        $this->assertEquals($result, 1);

        $database->rollback();

        $pet = $database->selectOne("select * from pets where id = 3");

        if (!$database->inTransaction()) {
            $result = 0;
        }

        $this->assertEquals($result, 0);
        $this->assertEquals(is_object($pet), true);
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_stement_table(Database $database)
    {
        $result = $database->statement("drop table pets");

        $this->assertEquals(is_bool($result), true);
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_stement_table_2(Database $database)
    {
        $result = $database->statement('create table if not exists pets (id int primary key, name varchar(255))');

        $this->assertEquals(is_bool($result), true);
    }
}
