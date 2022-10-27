<?php

namespace Bow\Tests\Database;

use Bow\Database\Database;
use Bow\Database\QueryBuilder;

class QueryBuilderTest extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        Database::table("pets")->truncate();
    }

    public function test_get_database_connection()
    {
        $instance = Database::getInstance();

        $this->assertInstanceOf(Database::class, $instance);

        return Database::getInstance();
    }

    /**
     * @depends test_get_database_connection
     * @param Database $database
     */
    public function test_get_instance(Database $database)
    {
        $this->assertInstanceOf(QueryBuilder::class, $database->table('pets'));
    }

    /**
     * @depends test_get_database_connection
     * @param Database $database
     */
    public function test_insert_by_passing_a_array(Database $database)
    {
        $table = $database->table('pets');
        $table->truncate();

        $result = $table->insert([
            'id' => 1,
            'name' => 'Milou'
        ]);

        $this->assertEquals($result, 1);
    }

    /**
     * @depends test_get_database_connection
     * @param Database $database
     */
    public function test_insert_by_passing_a_mutilple_array(Database $database)
    {
        $table = $database->table('pets');
        // We keep clear the pet table
        $table->truncate();

        $r = $table->insert([
            [ 'id' => 1, 'name' => 'Milou'],
            [ 'id' => 2, 'name' => 'Foli'],
            [ 'id' => 3, 'name' => 'Bob'],
        ]);

        $this->assertEquals($r, 3);
    }

    /**
     * @depends test_get_database_connection
     * @param Database $database
     */
    public function testSelectRows(Database $database)
    {
        $table = $database->table('pets');

        $this->assertInstanceOf(QueryBuilder::class, $table);

        $pets = $table->get();

        $this->assertEquals(is_array($pets), true);
    }

    /**
     * @depends test_get_database_connection
     * @param Database $database
     */
    public function testSelectChainRows(Database $database)
    {
        $table = $database->table('pets');
        $pets = $table->select(['name'])->get();

        $this->assertEquals(is_array($pets), true);
    }

    /**
     * @depends test_get_database_connection
     * @param Database $database
     */
    public function testSelectFirstChainRows(Database $database)
    {
        $table = $database->table('pets');
        $pet = $table->select(['name'])->first();

        $this->assertInstanceOf(\StdClass::class, $pet);
    }

    /**
     * @depends test_get_database_connection
     * @param Database $database
     */
    public function testWhereInChainRows(Database $database)
    {
        $table = $database->table('pets');
        $pets = $table->whereIn('id', [1, 3])->get();

        $this->assertEquals(is_array($pets), true);
    }

    /**
     * @depends test_get_database_connection
     * @param Database $database
     */
    public function testWhereNullChainRows(Database $database)
    {
        $table = $database->table('pets');
        $pets = $table->whereNull('name')->get();

        $this->assertEquals(is_array($pets), true);
    }

    /**
     * @depends test_get_database_connection
     * @param Database $database
     */
    public function testWhereBetweenChainRows(Database $database)
    {
        $table = $database->table('pets');
        $pets = $table->whereBetween('id', [1, 3])->get();

        $this->assertEquals(is_array($pets), true);
    }

    /**
     * @depends test_get_database_connection
     * @param Database $database
     */
    public function testWhereNotBetweenChainRows(Database $database)
    {
        $table = $database->table('pets');
        $pets = $table->whereNotBetween('id', [1, 3])->get();

        $this->assertEquals(is_array($pets), true);
    }

    /**
     * @depends test_get_database_connection
     * @param Database $database
     */
    public function testWhereNotNullChainRows(Database $database)
    {
        $table = $database->table('pets');
        $pets = $table->whereNotIn('id', [1, 3])->get();

        $this->assertEquals(is_array($pets), true);
    }

    /**
     * @depends test_get_database_connection
     * @param Database $database
     */
    public function testWhereChainRows(Database $database)
    {
        $table = $database->table('pets');

        $pets = $table->where('id', 1)->orWhere('name', 1)
            ->whereNull('name')
            ->whereBetween('id', [1, 3])
            ->whereNotBetween('id', [1, 3])->get();

        $this->assertEquals(is_array($pets), true);
    }
}
