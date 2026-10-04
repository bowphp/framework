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
     * @dataProvider connectionNameProvider
     */
    public function test_lock_for_update_generates_correct_sql(string $name)
    {
        $this->createTestingTable($name);
        $table = Database::connection($name)->table('pets');

        $table->lockForUpdate();
        $sql = $table->toSql();

        $this->assertStringEndsWith('for update', $sql);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_lock_for_update_executes_query(string $name)
    {
        if ($name === 'sqlite') {
            $this->markTestSkipped('SQLite does not support FOR UPDATE locking.');
        }

        $this->createTestingTable($name);
        $table = Database::connection($name)->table('pets');
        $table->insert([
            ['id' => 1, 'name' => 'Milou'],
            ['id' => 2, 'name' => 'Foli'],
        ]);

        Database::connection($name)->startTransaction();

        $pets = Database::connection($name)->table('pets')->lockForUpdate()->get();

        Database::connection($name)->rollback();

        $this->assertIsArray($pets);
        $this->assertCount(2, $pets);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_lock_for_update_flag_resets_after_to_sql(string $name)
    {
        $this->createTestingTable($name);
        $table = Database::connection($name)->table('pets');

        $table->lockForUpdate();
        $table->toSql();

        $sql = $table->toSql();

        $this->assertStringNotContainsString('for update', $sql);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_shared_lock_generates_correct_sql(string $name)
    {
        $this->createTestingTable($name);
        $table = Database::connection($name)->table('pets');

        $table->sharedLock();
        $sql = $table->toSql();

        if ($name === 'pgsql') {
            $this->assertStringEndsWith('for share', $sql);
        } else {
            $this->assertStringEndsWith('lock in share mode', $sql);
        }
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_shared_lock_executes_query(string $name)
    {
        if ($name === 'sqlite') {
            $this->markTestSkipped('SQLite does not support shared locking.');
        }

        $this->createTestingTable($name);
        $table = Database::connection($name)->table('pets');
        $table->insert([
            ['id' => 1, 'name' => 'Milou'],
            ['id' => 2, 'name' => 'Foli'],
        ]);

        Database::connection($name)->startTransaction();

        $pets = Database::connection($name)->table('pets')->sharedLock()->get();

        Database::connection($name)->rollback();

        $this->assertIsArray($pets);
        $this->assertCount(2, $pets);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_shared_lock_flag_resets_after_to_sql(string $name)
    {
        $this->createTestingTable($name);
        $table = Database::connection($name)->table('pets');

        $table->sharedLock();
        $table->toSql();

        $sql = $table->toSql();

        $this->assertStringNotContainsString('for share', $sql);
        $this->assertStringNotContainsString('lock in share mode', $sql);
    }

    /**
     * SQL-injection guard: a malicious column name must be rejected.
     *
     * Uses a local in-memory SQLite PDO so it needs no external connection.
     */
    public function test_where_rejects_unsafe_column_identifier()
    {
        $builder = new QueryBuilder('users', new \PDO('sqlite::memory:'));

        $this->expectException(QueryBuilderException::class);

        $builder->where('1=1 UNION SELECT password FROM admins -- ', '=', 'x');
    }

    /**
     * SQL-injection guard: whereBetween must bind both bounds as placeholders
     * instead of concatenating them into the statement.
     */
    public function test_where_between_binds_both_bounds()
    {
        $builder = new QueryBuilder('users', new \PDO('sqlite::memory:'));
        $builder->whereBetween('price', ['0', '100 OR 1=1 -- ']);

        $this->assertStringContainsString('price between ? and ?', $builder->toSql());

        $property = (new \ReflectionObject($builder))->getProperty('where_data_binding');
        $property->setAccessible(true);

        $this->assertSame(['0', '100 OR 1=1 -- '], $property->getValue($builder));
    }

    /**
     * @return array
     */
    public function connectionNameProvider(): array
    {
        return [['mysql'], ['sqlite'], ['pgsql']];
    }
}
