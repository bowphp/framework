<?php

use \Bow\Database\Database;

class QueryBuilderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetDatabaseConnection()
    {
        return Database::instance();
    }

    /**
     * @depends testGetDatabaseConnection
     * @param Database $db
     */
    public function testGetInstance(Bow\Database\Database $db)
    {
        $this->assertInstanceOf(\Bow\Database\Query\Builder::class, $db->table('pets'));
    }

    /**
     * @depends testGetDatabaseConnection
     * @param Database $db
     */
    public function testInsertRows(Bow\Database\Database $db)
    {
        $table = $db->table('pets');

        $r = $table->insert([
            'id' => 1,
            'name' => 'Milou'
        ]);

        $this->assertEquals($r, 1);
    }

    /**
     * @depends testGetDatabaseConnection
     * @param Database $db
     */
    public function testInsertMutilRows(Bow\Database\Database $db)
    {
        $table = $db->table('pets');

        $r = $table->insert([
            [ 'id' => 1, 'name' => 'Milou'],
            [ 'id' => 2, 'name' => 'Foli'],
            [ 'id' => 3, 'name' => 'Bob'],
        ]);

        $this->assertEquals($r, 3);
    }

    /**
     * @depends testGetDatabaseConnection
     * @param Database $db
     */
    public function testSelectRows(Bow\Database\Database $db)
    {
        $table = $db->table('pets');

        $this->assertInstanceOf(\Bow\Database\Query\Builder::class, $table);

        $pets = $table->get();

        $this->assertInstanceOf(\Bow\Support\Collection::class, $pets);
    }

    /**
     * @depends testGetDatabaseConnection
     * @param Database $db
     */
    public function testSelectChainRows(Bow\Database\Database $db)
    {
        $table = $db->table('pets');

        $pets = $table->select(['name'])->get();

        $this->assertInstanceOf(\Bow\Support\Collection::class, $pets);
    }

    /**
     * @depends testGetDatabaseConnection
     * @param Database $db
     */
    public function testSelectFirstChainRows(Bow\Database\Database $db)
    {
        $table = $db->table('pets');

        $pet = $table->select(['name'])->first();

        $this->assertInstanceOf(\Bow\Database\SqlUnity::class, $pet);
    }

    /**
     * @depends testGetDatabaseConnection
     * @param Database $db
     */
    public function testwhereInChainRows(Bow\Database\Database $db)
    {
        $table = $db->table('pets');

        $pets = $table->whereIn('id', [1, 3])->get();

        $this->assertInstanceOf(\Bow\Support\Collection::class, $pets);
    }

    /**
     * @depends testGetDatabaseConnection
     * @param Database $db
     */
    public function testWhereNullChainRows(Bow\Database\Database $db)
    {
        $table = $db->table('pets');

        $pets = $table->whereNull('name')->get();

        $this->assertInstanceOf(\Bow\Support\Collection::class, $pets);
    }

    /**
     * @depends testGetDatabaseConnection
     * @param Database $db
     */
    public function testWhereBetweenChainRows(Bow\Database\Database $db)
    {
        $table = $db->table('pets');

        $pets = $table->whereBetween('id', [1, 3])->get();

        $this->assertInstanceOf(\Bow\Support\Collection::class, $pets);
    }

    /**
     * @depends testGetDatabaseConnection
     * @param Database $db
     */
    public function testWhereNotBetweenChainRows(Bow\Database\Database $db)
    {
        $table = $db->table('pets');

        $pets = $table->whereNotBetween('id', [1, 3])->get();

        $this->assertInstanceOf(\Bow\Support\Collection::class, $pets);
    }

    /**
     * @depends testGetDatabaseConnection
     * @param Database $db
     */
    public function testWhereNotNullChainRows(Bow\Database\Database $db)
    {
        $table = $db->table('pets');

        $pets = $table->whereNotIn('id', [1, 3])->get();

        $this->assertInstanceOf(\Bow\Support\Collection::class, $pets);
    }

    /**
     * @depends testGetDatabaseConnection
     * @param Database $db
     */
    public function testWhereChainRows(Bow\Database\Database $db)
    {
        $table = $db->table('pets');

        $pets = $table->where('id', 1)->orWhere('name', 1)->whereNull('name')->whereBetween('id', [1, 3])->whereNotBetween('id', [1, 3])->get();

        $this->assertInstanceOf(\Bow\Support\Collection::class, $pets);
    }
}
