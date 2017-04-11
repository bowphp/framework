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
     */
    public function testGetInstance($db)
    {
        $this->assertInstanceOf(\Bow\Database\QueryBuilder\QueryBuilder::class, $db->table('pets'));
    }

    /**
     * @depends testGetDatabaseConnection
     */
    public function testInsertRows($db)
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
     */
    public function testInsertMutilRows($db)
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
     */
    public function testSelectRows($db)
    {
        $table = $db->table('pets');
        $pets = $table->get();
        $this->assertInstanceOf(\Bow\Support\Collection::class, $pets);
    }

    /**
     * @depends testGetDatabaseConnection
     */
    public function testSelectChainRows($db)
    {
        $table = $db->table('pets');
        $pets = $table->select(['name'])->get();
        $this->assertInstanceOf(\Bow\Support\Collection::class, $pets);
    }
}