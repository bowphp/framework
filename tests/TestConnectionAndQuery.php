<?php

use Bow\Support\Collection;
use Bow\Database\Database;

class TestConnectionAndQuery extends \PHPUnit\Framework\TestCase
{
    public function additionProvider()
    {
        return [0 => ['first'], 1 => ['seconds']];
    }

    /**
     * @dataProvider additionProvider
     */
    public function testInstanceOfDatabase($name)
    {
        Database::configure([
            'fetch' => \PDO::FETCH_OBJ,
            'default' => 'first',
            'first' => [
                'scheme' => 'mysql',
                'mysql' => [
                    'hostname' => 'localhost',
                    'username' => $GLOBALS['DB_USER'],
                    'password' => $GLOBALS['DB_PASSWORD'],
                    'database' => $GLOBALS['DB_DATABASENAME'],
                    'charset'  => $GLOBALS['DB_CHARSET'],
                    'collation' => $GLOBALS['DB_COLLATE'],
                    'port' => null,
                    'socket' => null
                ]
            ],
            'seconds' => [
                'scheme' => 'sqlite',
                'sqlite' => [
                    'driver' => 'sqlite',
                    'database' => __DIR__.'/data/database.sqlite',
                    'prefix' => ''
                ]
            ]
        ]);

        $this->assertInstanceOf(Database::class, \Bow\Database\Database::connection($name));
    }

    public function testGetDatabaseConnection()
    {
        return Database::instance();
    }

    /**
     * @depends testGetDatabaseConnection
     */
    public function testCreateTable($db)
    {
        $this->assertInstanceOf(Bow\Database\Database::class, $db);
        $db->getPdo()->exec('DROP TABLE IF EXISTS pets');
        $db->getPdo()->exec('CREATE TABLE IF NOT EXISTS pets (id INT, name VARCHAR(255))');
    }

    /**
     * @depends testGetDatabaseConnection
     */
    public function testSimpleInsertTable($db)
    {
        $this->assertEquals($db->insert("INSERT INTO pets VALUES (1, 'Bob'), (2, 'Milo');"), 2);
    }

    /**
     * @depends testGetDatabaseConnection
     */
    public function testArrayInsertTable($db)
    {
        $this->assertEquals($db->insert("INSERT INTO pets VALUES(:id, :name);", [
            "id" => 3,
            'name' => 'Popy'
        ]), 1);
    }

    /**
     * @depends testGetDatabaseConnection
     */
    public function testArrayMultileInsertTable($db)
    {
        $this->assertEquals($db->insert("INSERT INTO pets VALUES(:id, :name);", [
            [ "id" => 4, 'name' => 'Ploy'],
            [ "id" => 5, 'name' => 'Cesar'],
        ]), 2);
    }

    /**
     * @depends testGetDatabaseConnection
     */
    public function testSelectTable($db)
    {
        $pets = $db->select("SELECT * FROM pets");
        $this->assertInstanceOf(Collection::class, $pets);
    }

    /**
     * @depends testGetDatabaseConnection
     */
    public function testSelect2Table($db)
    {
        $pets = $db->select("SELECT * FROM pets");
        $this->assertEquals(count($pets), 5);
    }

    /**
     * @depends testGetDatabaseConnection
     */
    public function testSelectWithGetOneElementTable($db)
    {
        $pets = $db->select("SELECT * FROM pets WHERE id = :id", [
            'id' => 1
        ]);
        $this->assertInstanceOf(Collection::class, $pets);
    }

    /**
     * @depends testGetDatabaseConnection
     */
    public function testSelectWithNotGetElementTable($db)
    {
        $pets = $db->select("SELECT * FROM pets WHERE id = :id", [
            'id' => 6
        ]);
        $this->assertInstanceOf(Collection::class, $pets);
        $this->assertEquals($pets->isEmpty(), true);
    }

    /**
     * @depends testGetDatabaseConnection
     */
    public function testSelectOneTable($db)
    {
        $pets = $db->selectOne("SELECT * FROM pets WHERE id = :id", [
            'id' => 1
        ]);
        $this->assertEquals(is_object($pets), true);
    }

    /**
     * @depends testGetDatabaseConnection
     */
    public function testUpdateTable($db)
    {
        $r = $db->update("UPDATE pets SET name = 'Filou' WHERE id = :id", [
            'id' => 1
        ]);
        $this->assertEquals($r, 1);
    }

    /**
     * @depends testGetDatabaseConnection
     */
    public function testDeleteTable($db)
    {
        $r = $db->delete("DELETE FROM pets WHERE id = :id", [
            'id' => 1
        ]);
        $this->assertEquals($r, 1);
    }

    /**
     * @depends testGetDatabaseConnection
     */
    public function testTransactionTable($db)
    {
        $db->startTransaction(function () use ($db) {
            $r = $db->delete("DELETE FROM pets WHERE id = :id", [
                'id' => 2
            ]);
            $this->assertEquals($db->inTransaction(), true);
            $this->assertEquals($r, 1);
        });
        $db->commit();
    }

    /**
     * @depends testGetDatabaseConnection
     */
    public function testRollbackTable($db)
    {
        $r = 0;
        $db->startTransaction(function () use ($db, & $r) {
            $r = $db->delete("DELETE FROM pets WHERE id = :id", [
                'id' => 3
            ]);
            $this->assertEquals($db->inTransaction(), true);
            $this->assertEquals($r, 1);
        });
        $db->rollback();
        $pet = $db->selectOne("SELECT * FROM pets WHERE id = 3");
        if (! $db->inTransaction()) {
            $r = 0;
        }
        $this->assertEquals($r, 0);
        $this->assertEquals(is_object($pet), true);
    }

    /**
     * @depends testGetDatabaseConnection
     */
    public function testStementTable($db)
    {
        $r = $db->statement("DROP TABLE pets");
        $this->assertEquals(is_bool($r), true);
    }

    /**
     * @depends testGetDatabaseConnection
     */
    public function testStement2Table($db)
    {
        $r = $db->statement('CREATE TABLE IF NOT EXISTS pets (id INT, name VARCHAR(255))');
        $this->assertEquals(is_bool($r), true);
    }
}