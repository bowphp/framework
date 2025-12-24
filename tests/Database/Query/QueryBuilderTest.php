<?php

namespace Bow\Tests\Database\Query;

use Bow\Database\Database;
use Bow\Database\Exception\ConnectionException;
use Bow\Database\Exception\QueryBuilderException;
use Bow\Database\QueryBuilder;
use Bow\Tests\Config\TestingConfiguration;

class QueryBuilderTest extends \PHPUnit\Framework\TestCase
{
    private static bool $configured = false;

    public static function setUpBeforeClass(): void
    {
        if (!static::$configured) {
            $config = TestingConfiguration::getConfig();
            Database::configure($config["database"]);
            static::$configured = true;
        }
    }

    public function tearDown(): void
    {
        // Clean up test table after each test for all connections
        foreach (['mysql', 'sqlite', 'pgsql'] as $name) {
            try {
                Database::connection($name)->statement('DROP TABLE IF EXISTS pets');
            } catch (\Exception $e) {
                // Ignore errors during cleanup
            }
        }
        parent::tearDown();
    }

    private function createTestingTable(string $name): void
    {
        $connection = Database::connection($name);
        $connection->statement('DROP TABLE IF EXISTS pets');
        $connection->statement('CREATE TABLE pets (id INT PRIMARY KEY, name VARCHAR(255))');
    }

    public function test_get_database_connection()
    {
        $instance = Database::getInstance();
        $this->assertInstanceOf(Database::class, $instance);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_get_query_builder_instance(string $name)
    {
        $this->createTestingTable($name);
        $table = Database::connection($name)->table('pets');
        
        $this->assertInstanceOf(QueryBuilder::class, $table);
    }

    /**
     * @dataProvider connectionNameProvider
     * @param string $name
     * @throws ConnectionException
     */
    public function test_insert_by_passing_a_array(string $name)
    {
        $this->createTestingTable($name);
        $table = Database::connection($name)->table('pets');
        $table->truncate();

        $result = $table->insert([
            'id' => 1,
            'name' => 'Milou'
        ]);

        $this->assertEquals($result, 1);
    }

    /**
     * @dataProvider connectionNameProvider
     * @param string $name
     * @throws ConnectionException
     */
    public function test_insert_by_passing_a_multiple_array(string $name)
    {
        $this->createTestingTable($name);
        $table = Database::connection($name)->table('pets');
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
     * @dataProvider connectionNameProvider
     * @param string $name
     * @throws ConnectionException
     */
    public function test_select_rows(string $name)
    {
        $this->createTestingTable($name);
        $table = Database::connection($name)->table('pets');

        $this->assertInstanceOf(QueryBuilder::class, $table);

        $pets = $table->get();

        $this->assertEquals(is_array($pets), true);
    }

    /**
     * @dataProvider connectionNameProvider
     * @param string $name
     * @throws ConnectionException
     */
    public function test_select_chain_rows(string $name)
    {
        $this->createTestingTable($name);
        $table = Database::connection($name)->table('pets');
        $pets = $table->select(['name'])->get();

        $this->assertEquals(is_array($pets), true);
    }

    /**
     * @dataProvider connectionNameProvider
     * @param string $name
     * @throws ConnectionException
     */
    public function test_select_first_chain_rows(string $name)
    {
        $this->createTestingTable($name);

        $table = Database::connection($name)->table('pets');
        $table->insert([
            ['id' => 1, 'name' => 'Milou'],
            ['id' => 2, 'name' => 'Foli'],
            ['id' => 3, 'name' => 'Bob'],
        ]);

        $pet = $table->select(['name'])->first();

        $this->assertInstanceOf(\StdClass::class, $pet);
    }

    /**
     * @dataProvider connectionNameProvider
     * @param string $name
     * @throws ConnectionException
     * @throws QueryBuilderException
     */
    public function test_where_in_chain_rows(string $name)
    {
        $this->createTestingTable($name);
        $table = Database::connection($name)->table('pets');
        $pets = $table->whereIn('id', [1, 3])->get();

        $this->assertEquals(is_array($pets), true);
    }

    /**
     * @dataProvider connectionNameProvider
     * @param string $name
     * @throws ConnectionException
     */
    public function test_where_null_chain_rows(string $name)
    {
        $this->createTestingTable($name);
        $table = Database::connection($name)->table('pets');
        $pets = $table->whereNull('name')->get();

        $this->assertEquals(is_array($pets), true);
    }

    /**
     * @dataProvider connectionNameProvider
     * @param string $name
     * @throws ConnectionException
     * @throws QueryBuilderException
     */
    public function test_where_between_chain_rows(string $name)
    {
        $this->createTestingTable($name);
        $table = Database::connection($name)->table('pets');
        $pets = $table->whereBetween('id', [1, 3])->get();

        $this->assertEquals(is_array($pets), true);
    }

    /**
     * @dataProvider connectionNameProvider
     * @param string $name
     * @throws ConnectionException
     */
    public function test_where_not_between_chain_rows(string $name)
    {
        $this->createTestingTable($name);
        $table = Database::connection($name)->table('pets');
        $pets = $table->whereNotBetween('id', [1, 3])->get();

        $this->assertEquals(is_array($pets), true);
    }

    /**
     * @dataProvider connectionNameProvider
     * @param string $name
     * @throws ConnectionException
     * @throws QueryBuilderException
     */
    public function test_where_not_null_chain_rows(string $name)
    {
        $this->createTestingTable($name);
        $table = Database::connection($name)->table('pets');
        $pets = $table->whereNotIn('id', [1, 3])->get();

        $this->assertEquals(is_array($pets), true);
    }

    /**
     * @dataProvider connectionNameProvider
     * @param string $name
     * @throws ConnectionException
     * @throws QueryBuilderException
     */
    public function test_where_chain_rows(string $name)
    {
        $this->createTestingTable($name);
        $table = Database::connection($name)->table('pets');

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
