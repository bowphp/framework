<?php

namespace Bow\Tests\Database\Query;

use Bow\Database\Database;
use Bow\Database\Pagination;
use Bow\Tests\Config\TestingConfiguration;

class PaginationTest extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();
        Database::configure($config["database"]);
    }

    /**
     * @dataProvider connectionNameProvider
     * @param Database $database
     */
    public function test_go_current_pagination(string $name)
    {
        $this->createTestingTable($name);
        $result = Database::table("pets")->paginate(10);

        $this->assertInstanceOf(Pagination::class, $result);
        $this->assertEquals(count($result->items()), 10);
        $this->assertEquals($result->perPage(), 10);
        $this->assertEquals($result->total(), 3);
        $this->assertEquals($result->current(), 1);
        $this->assertEquals($result->previous(), 1);
        $this->assertEquals($result->next(), 2);
    }

    public function createTestingTable(string $name)
    {
        $connection = Database::connection($name);
        $connection->statement('drop table if exists pets');
        $connection->statement('create table pets (id int primary key, name varchar(255))');
        $connection->table("pets")->truncate();
        foreach (range(1, 30) as $key) {
            $connection->insert('insert into pets values(:id, :name)', ['id' => $key, 'name' => 'Pet ' . $key]);
        }
    }

    /**
     * @dataProvider connectionNameProvider
     * @param Database $database
     */
    public function test_go_next_2_pagination(string $name)
    {
        $this->createTestingTable($name);
        $result = Database::table("pets")->paginate(10, 2);

        $this->assertInstanceOf(Pagination::class, $result);
        $this->assertEquals(count($result->items()), 10);
        $this->assertEquals($result->perPage(), 10);
        $this->assertEquals($result->total(), 3);
        $this->assertEquals($result->current(), 2);
        $this->assertEquals($result->previous(), 1);
        $this->assertEquals($result->next(), 3);
    }

    /**
     * @dataProvider connectionNameProvider
     * @param Database $database
     */
    public function test_go_next_3_pagination(string $name)
    {
        $this->createTestingTable($name);
        $result = Database::table("pets")->paginate(10, 3);

        $this->assertInstanceOf(Pagination::class, $result);
        $this->assertEquals(count($result->items()), 10);
        $this->assertEquals($result->perPage(), 10);
        $this->assertEquals($result->total(), 3);
        $this->assertEquals($result->current(), 3);
        $this->assertEquals($result->previous(), 2);
        $this->assertEquals($result->next(), false);
    }

    /**
     * @return array
     */
    public function connectionNameProvider()
    {
        return [['mysql'], ['sqlite'], ['pgsql']];
    }
}
