<?php

use Bow\Database\Database;

class ConnectionAndQueryTest extends \PHPUnit\Framework\TestCase
{
    public function additionProvider()
    {
        return [['mysql'], ['sqlite']];
    }

    /**
     * @dataProvider additionProvider
     * @param $name
     */
    public function test_instance_of_database($name)
    {
        $this->assertInstanceOf(Database::class, \Bow\Database\Database::connection($name));
    }

    public function test_get_database_connection()
    {
        return Database::getInstance();
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_create_table(Database $db)
    {
        $this->assertInstanceOf(Database::class, $db);

        $db->getPdo()->exec('DROP TABLE IF EXISTS pets');

        $db->getPdo()->exec('CREATE TABLE IF NOT EXISTS pets (id INT, name VARCHAR(255))');
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_simple_insert_table(Database $db)
    {
        $this->assertEquals($db->insert("INSERT INTO pets VALUES (1, 'Bob'), (2, 'Milo');"), 2);
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_array_insert_table(Database $db)
    {
        $this->assertEquals($db->insert("INSERT INTO pets VALUES(:id, :name);", [
            "id" => 3,
            'name' => 'Popy'
        ]), 1);
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_array_multile_insert_table(Database $db)
    {
        $this->assertEquals($db->insert("INSERT INTO pets VALUES(:id, :name);", [
            [ "id" => 4, 'name' => 'Ploy'],
            [ "id" => 5, 'name' => 'Cesar'],
        ]), 2);
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_select_table(Database $db)
    {
        $pets = $db->select("SELECT * FROM pets");

        $this->assertTrue(is_array($pets));
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_select_table_2(Database $db)
    {
        $pets = $db->select("SELECT * FROM pets");

        $this->assertEquals(count($pets), 5);
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_select_with_get_one_element_table(Database $db)
    {
        $pets = $db->select("SELECT * FROM pets WHERE id = :id", ['id' => 1]);

        $this->assertTrue(is_array($pets));
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_select_with_not_get_element_table(Database $db)
    {
        $pets = $db->select("SELECT * FROM pets WHERE id = :id", [
            'id' => 6
        ]);

        $this->assertTrue(is_array($pets));

        $this->assertTrue(count($pets) == 0);
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_select_one_table(Database $db)
    {
        $pets = $db->selectOne("SELECT * FROM pets WHERE id = :id", [
            'id' => 1
        ]);

        $this->assertTrue(is_object($pets));
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_update_table($db)
    {
        $r = $db->update("UPDATE pets SET name = 'Filou' WHERE id = :id", [
            'id' => 1
        ]);

        $this->assertEquals($r, 1);
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_delete_table(Database $db)
    {
        $r = $db->delete("DELETE FROM pets WHERE id = :id", ['id' => 1]);

        $this->assertEquals($r, 1);
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_transaction_table(Database $db)
    {
        $db->startTransaction(function () use ($db) {
            $r = $db->delete("DELETE FROM pets WHERE id = :id", ['id' => 2]);

            $this->assertEquals($db->inTransaction(), true);

            $this->assertEquals($r, 1);
        });
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_rollback_table(Database $db)
    {
        $r = 0;
        
        $db->startTransaction();

        $r = $db->delete("DELETE FROM pets WHERE id = :id", [
            'id' => 3
        ]);

        $this->assertEquals($db->inTransaction(), true);

        $this->assertEquals($r, 1);

        $db->rollback();

        $pet = $db->selectOne("SELECT * FROM pets WHERE id = 3");

        if (!$db->inTransaction()) {
            $r = 0;
        }

        $this->assertEquals($r, 0);

        $this->assertEquals(is_object($pet), true);
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_stement_table(Database $db)
    {
        $r = $db->statement("DROP TABLE pets");

        $this->assertEquals(is_bool($r), true);
    }

    /**
     * @depends test_get_database_connection
     */
    public function test_stement_table_2(Database $db)
    {
        $r = $db->statement('CREATE TABLE IF NOT EXISTS pets (id INT, name VARCHAR(255))');

        $this->assertEquals(is_bool($r), true);
    }
}
