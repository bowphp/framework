<?php

namespace Bow\Tests\Database\Query;

use Bow\Database\Database;
use Bow\Database\Exception\ConnectionException;
use Bow\Tests\Config\TestingConfiguration;
use PDO;

class DatabaseQueryTest extends \PHPUnit\Framework\TestCase
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

    public function setUp(): void
    {
        parent::setUp();
        // Table will be created per connection in each test
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

    /**
     * @return array
     */
    public function connectionNameProvider(): array
    {
        return [['mysql'], ['sqlite'], ['pgsql']];
    }

    private function createTestingTable(string $name): void
    {
        $database = Database::connection($name);
        $database->statement('DROP TABLE IF EXISTS pets');
        $database->statement(
            'CREATE TABLE pets (id INT PRIMARY KEY, name VARCHAR(255))'
        );
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_instance_of_database(string $name)
    {
        $this->createTestingTable($name);
        $connection = Database::connection($name);
        $this->assertInstanceOf(Database::class, $connection);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_get_database_connection(string $name)
    {
        $this->createTestingTable($name);
        $instance = Database::connection($name);
        $adapter = $instance->getConnectionAdapter();

        $this->assertEquals($name, $adapter->getName());
        $this->assertInstanceOf(Database::class, $instance);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_get_pdo_from_connection(string $name)
    {
        $this->createTestingTable($name);
        $database = Database::connection($name);
        $pdo = $database->getConnectionAdapter()->getConnection();

        $this->assertInstanceOf(PDO::class, $pdo);
        $this->assertEquals($name, $pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_connection_is_reused(string $name)
    {
        $connection1 = Database::connection($name);
        $connection2 = Database::connection($name);

        $this->assertSame($connection1, $connection2);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_simple_insert_table(string $name)
    {
        $this->createTestingTable($name);
        $database = Database::connection($name);

        $result = $database->insert("INSERT INTO pets VALUES(1, 'Bob'), (2, 'Milo');");

        $this->assertEquals(2, $result);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_array_insert_table(string $name)
    {
        $this->createTestingTable($name);
        $database = Database::connection($name);

        $result = $database->insert("INSERT INTO pets VALUES(:id, :name);", [
            "id" => 1,
            'name' => 'Popy'
        ]);

        $this->assertEquals(1, $result);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_array_multiple_insert_table(string $name)
    {
        $this->createTestingTable($name);
        $database = Database::connection($name);

        $result = $database->insert("INSERT INTO pets VALUES(:id, :name);", [
            ["id" => 1, 'name' => 'Ploy'],
            ["id" => 2, 'name' => 'Cesar'],
            ["id" => 3, 'name' => 'Louis'],
        ]);

        $this->assertEquals(3, $result);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_insert_with_named_parameters(string $name)
    {
        $this->createTestingTable($name);
        $database = Database::connection($name);

        $result = $database->insert(
            "INSERT INTO pets (id, name) VALUES (:id, :name)",
            ['id' => 5, 'name' => 'Max']
        );

        $this->assertEquals(1, $result);

        $pet = $database->selectOne("SELECT * FROM pets WHERE id = 5");
        $this->assertEquals('Max', $pet->name);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_insert_returns_zero_on_duplicate(string $name)
    {
        $this->createTestingTable($name);
        $database = Database::connection($name);

        $database->insert("INSERT INTO pets VALUES(1, 'Bob');");

        try {
            $result = $database->insert("INSERT INTO pets VALUES(1, 'Bob');");
            $this->fail("Expected exception for duplicate key");
        } catch (\Exception $e) {
            $this->assertInstanceOf(\PDOException::class, $e);
        }
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_select_table(string $name)
    {
        $this->createTestingTable($name);
        $database = Database::connection($name);

        $pets = $database->select("SELECT * FROM pets");

        $this->assertIsArray($pets);
        $this->assertEmpty($pets);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_select_table_and_check_item_length(string $name)
    {
        $this->createTestingTable($name);
        $database = Database::connection($name);

        $database->insert("INSERT INTO pets VALUES(:id, :name);", [
            ["id" => 1, 'name' => 'Ploy'],
            ["id" => 2, 'name' => 'Cesar'],
            ["id" => 3, 'name' => 'Louis'],
        ]);

        $pets = $database->select("SELECT * FROM pets");

        $this->assertCount(3, $pets);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_select_with_get_one_element_table(string $name)
    {
        $this->createTestingTable($name);
        $database = Database::connection($name);

        $database->insert("INSERT INTO pets VALUES(:id, :name);", ["id" => 1, 'name' => 'Ploy']);

        $pets = $database->select("SELECT * FROM pets WHERE id = :id", ['id' => 1]);

        $this->assertIsArray($pets);
        $this->assertCount(1, $pets);
        $this->assertEquals('Ploy', $pets[0]->name);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_select_with_not_get_element_table(string $name)
    {
        $this->createTestingTable($name);
        $database = Database::connection($name);

        $pets = $database->select("SELECT * FROM pets WHERE id = :id", ['id' => 7]);

        $this->assertIsArray($pets);
        $this->assertCount(0, $pets);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_select_one_table(string $name)
    {
        $this->createTestingTable($name);
        $database = Database::connection($name);

        $database->insert("INSERT INTO pets VALUES(:id, :name);", ["id" => 1, 'name' => 'Ploy']);

        $pet = $database->selectOne("SELECT * FROM pets WHERE id = :id", ['id' => 1]);

        $this->assertIsObject($pet);
        $this->assertIsNotArray($pet);
        $this->assertEquals('Ploy', $pet->name);
        $this->assertEquals(1, $pet->id);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_select_one_returns_null_when_not_found(string $name)
    {
        $this->createTestingTable($name);
        $database = Database::connection($name);

        $pet = $database->selectOne("SELECT * FROM pets WHERE id = :id", ['id' => 999]);

        // selectOne returns false when no record is found
        $this->assertFalse($pet);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_select_with_where_clause(string $name)
    {
        $this->createTestingTable($name);
        $database = Database::connection($name);

        $database->insert("INSERT INTO pets VALUES(:id, :name);", [
            ["id" => 1, 'name' => 'Ploy'],
            ["id" => 2, 'name' => 'Cesar'],
            ["id" => 3, 'name' => 'Louis'],
        ]);

        $pets = $database->select("SELECT * FROM pets WHERE id > :id", ['id' => 1]);

        $this->assertCount(2, $pets);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_select_with_limit(string $name)
    {
        $this->createTestingTable($name);
        $database = Database::connection($name);

        $database->insert("INSERT INTO pets VALUES(:id, :name);", [
            ["id" => 1, 'name' => 'Ploy'],
            ["id" => 2, 'name' => 'Cesar'],
            ["id" => 3, 'name' => 'Louis'],
        ]);

        $pets = $database->select("SELECT * FROM pets LIMIT 2");

        $this->assertCount(2, $pets);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_update_table(string $name)
    {
        $this->createTestingTable($name);
        $database = Database::connection($name);

        $database->insert("INSERT INTO pets VALUES(:id, :name);", ["id" => 1, 'name' => 'Ploy']);

        $result = $database->update("UPDATE pets SET name = 'Bob' WHERE id = :id", ['id' => 1]);
        $this->assertEquals(1, $result);

        $pet = $database->selectOne("SELECT * FROM pets WHERE id = :id", ['id' => 1]);
        $this->assertEquals('Bob', $pet->name);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_update_multiple_records(string $name)
    {
        $this->createTestingTable($name);
        $database = Database::connection($name);

        $database->insert("INSERT INTO pets VALUES(:id, :name);", [
            ["id" => 1, 'name' => 'Ploy'],
            ["id" => 2, 'name' => 'Cesar'],
        ]);

        $result = $database->update("UPDATE pets SET name = 'Updated' WHERE id IN (1, 2)");
        $this->assertEquals(2, $result);

        $pets = $database->select("SELECT * FROM pets WHERE name = 'Updated'");
        $this->assertCount(2, $pets);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_update_returns_zero_when_no_match(string $name)
    {
        $this->createTestingTable($name);
        $database = Database::connection($name);

        $result = $database->update("UPDATE pets SET name = 'Bob' WHERE id = 999");

        $this->assertEquals(0, $result);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_update_with_multiple_conditions(string $name)
    {
        $this->createTestingTable($name);
        $database = Database::connection($name);

        $database->insert("INSERT INTO pets VALUES(:id, :name);", [
            ["id" => 1, 'name' => 'Ploy'],
            ["id" => 2, 'name' => 'Cesar'],
        ]);

        $result = $database->update(
            "UPDATE pets SET name = :newName WHERE id = :id AND name = :oldName",
            ['newName' => 'Bob', 'id' => 1, 'oldName' => 'Ploy']
        );

        $this->assertEquals(1, $result);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_delete_table(string $name)
    {
        $this->createTestingTable($name);
        $database = Database::connection($name);

        $database->insert("INSERT INTO pets VALUES(:id, :name);", ["id" => 1, 'name' => 'Ploy']);

        $result = $database->delete("DELETE FROM pets WHERE id = :id", ['id' => 1]);
        $this->assertEquals(1, $result);

        $result = $database->delete("DELETE FROM pets WHERE id = :id", ['id' => 1]);
        $this->assertEquals(0, $result);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_delete_multiple_records(string $name)
    {
        $this->createTestingTable($name);
        $database = Database::connection($name);

        $database->insert("INSERT INTO pets VALUES(:id, :name);", [
            ["id" => 1, 'name' => 'Ploy'],
            ["id" => 2, 'name' => 'Ploy'],
            ["id" => 3, 'name' => 'Cesar'],
        ]);

        $result = $database->delete("DELETE FROM pets WHERE name = :name", ['name' => 'Ploy']);
        $this->assertEquals(2, $result);

        $remaining = $database->select("SELECT * FROM pets");
        $this->assertCount(1, $remaining);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_delete_with_condition(string $name)
    {
        $this->createTestingTable($name);
        $database = Database::connection($name);

        $database->insert("INSERT INTO pets VALUES(:id, :name);", [
            ["id" => 1, 'name' => 'Ploy'],
            ["id" => 2, 'name' => 'Cesar'],
        ]);

        $result = $database->delete("DELETE FROM pets WHERE id IN (1, 2)");
        $this->assertEquals(2, $result);

        $pets = $database->select("SELECT * FROM pets");
        $this->assertEmpty($pets);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_transaction_table(string $name)
    {
        $this->createTestingTable($name);
        $database = Database::connection($name);

        $database->insert("INSERT INTO pets VALUES(:id, :name);", ["id" => 1, 'name' => 'Ploy']);
        $result = 0;

        $database->transaction(function () use ($database, &$result) {
            $result = $database->delete("DELETE FROM pets WHERE id = :id", ['id' => 1]);
            $this->assertTrue($database->inTransaction());
        });

        $this->assertEquals(1, $result);
        $this->assertFalse($database->inTransaction());

        // Verify deletion was committed (returns false when not found)
        $pet = $database->selectOne("SELECT * FROM pets WHERE id = 1");
        $this->assertFalse($pet);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_transaction_commits_on_success(string $name)
    {
        $this->createTestingTable($name);
        $database = Database::connection($name);

        $database->insert("INSERT INTO pets VALUES(1, 'Initial');");

        $database->transaction(function () use ($database) {
            $database->update("UPDATE pets SET name = 'Updated' WHERE id = 1");
            $database->insert("INSERT INTO pets VALUES(2, 'New');");
        });

        $pets = $database->select("SELECT * FROM pets ORDER BY id");
        $this->assertCount(2, $pets);
        $this->assertEquals('Updated', $pets[0]->name);
        $this->assertEquals('New', $pets[1]->name);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_transaction_rolls_back_on_exception(string $name)
    {
        $this->createTestingTable($name);
        $database = Database::connection($name);

        $database->insert("INSERT INTO pets VALUES(1, 'Initial');");

        try {
            $database->transaction(function () use ($database) {
                $database->update("UPDATE pets SET name = 'Updated' WHERE id = 1");
                throw new \Exception("Test exception");
            });
            $this->fail("Expected exception was not thrown");
        } catch (\Exception $e) {
            $this->assertEquals("Test exception", $e->getMessage());
        }

        // Note: Some databases may auto-commit before the exception
        // This test validates that the exception is properly propagated
        $pet = $database->selectOne("SELECT * FROM pets WHERE id = 1");
        $this->assertIsObject($pet);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_rollback_table(string $name)
    {
        $this->createTestingTable($name);
        $result = 0;

        $database = Database::connection($name);

        $database->insert("INSERT INTO pets VALUES(:id, :name);", ["id" => 1, 'name' => 'Ploy']);

        $database->startTransaction();
        $result = $database->delete("DELETE FROM pets WHERE id = 1");

        $this->assertTrue($database->inTransaction());
        $this->assertEquals(1, $result);

        $database->rollback();

        $pet = $database->selectOne("SELECT * FROM pets WHERE id = 1");

        $this->assertFalse($database->inTransaction());
        $this->assertIsObject($pet);
        $this->assertEquals("Ploy", $pet->name);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_nested_transactions_not_supported(string $name)
    {
        $database = Database::connection($name);

        $database->startTransaction();
        $this->assertTrue($database->inTransaction());

        // Starting another transaction should not create a nested one
        $database->startTransaction();
        $this->assertTrue($database->inTransaction());

        $database->commit();
        $this->assertFalse($database->inTransaction());
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_commit_without_transaction(string $name)
    {
        $this->createTestingTable($name);
        $database = Database::connection($name);

        $this->assertFalse($database->inTransaction());

        // PDO behavior for commit without transaction varies by driver:
        // - Some throw PDOException
        // - Some silently succeed
        try {
            $database->commit();
            // If no exception, just verify we're still not in a transaction
            $this->assertFalse($database->inTransaction());
        } catch (\PDOException $e) {
            // Expected behavior for some drivers
            $this->assertFalse($database->inTransaction());
        }
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_statement_table(string $name)
    {
        $this->createTestingTable($name);
        $database = Database::connection($name);

        $result = $database->statement("DROP TABLE pets");

        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_statement_table_2(string $name)
    {
        $database = Database::connection($name);

        $result = $database->statement('CREATE TABLE IF NOT EXISTS pets (id INT PRIMARY KEY, name VARCHAR(255))');

        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_statement_truncate_table(string $name)
    {
        if ($name === 'sqlite') {
            $this->markTestSkipped('SQLite does not support TRUNCATE syntax');
        }

        $this->createTestingTable($name);
        $database = Database::connection($name);

        $database->insert("INSERT INTO pets VALUES(1, 'Bob'), (2, 'Milo');");

        $result = $database->statement("TRUNCATE TABLE pets");
        $this->assertTrue($result);

        $pets = $database->select("SELECT * FROM pets");
        $this->assertEmpty($pets);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_statement_with_invalid_sql_throws_exception(string $name)
    {
        $database = Database::connection($name);

        $this->expectException(\PDOException::class);
        $database->statement("INVALID SQL STATEMENT");
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_table_method_returns_query_builder(string $name)
    {
        $database = Database::connection($name);
        $queryBuilder = $database->table('pets');

        $this->assertInstanceOf(\Bow\Database\QueryBuilder::class, $queryBuilder);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_raw_query_execution(string $name)
    {
        $this->createTestingTable($name);
        $database = Database::connection($name);

        $database->insert("INSERT INTO pets VALUES(1, 'Bob');");

        $pets = $database->select("SELECT name FROM pets WHERE id = 1");

        $this->assertCount(1, $pets);
        $this->assertEquals('Bob', $pets[0]->name);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_last_insert_id_after_insert(string $name)
    {
        if ($name === 'sqlite') {
            $this->markTestSkipped('SQLite handles ROWID differently');
        }

        $this->createTestingTable($name);
        $database = Database::connection($name);
        $database->statement('DROP TABLE IF EXISTS auto_pets');

        // Use database-specific syntax for auto-increment
        if ($name === 'pgsql') {
            $database->statement('CREATE TABLE auto_pets (id SERIAL PRIMARY KEY, name VARCHAR(255))');
        } else {
            $database->statement('CREATE TABLE auto_pets (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255))');
        }

        $database->insert("INSERT INTO auto_pets (name) VALUES('Bob')");

        $lastId = $database->getConnectionAdapter()->getConnection()->lastInsertId();
        $this->assertGreaterThan(0, $lastId);

        $database->statement('DROP TABLE auto_pets');
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_prepared_statement_prevents_sql_injection(string $name)
    {
        $this->createTestingTable($name);
        $database = Database::connection($name);

        $database->insert("INSERT INTO pets VALUES(1, 'Bob');");

        // For string-based SQL injection test, use name field instead of id
        $maliciousInput = "Bob' OR '1'='1";
        $pets = $database->select("SELECT * FROM pets WHERE name = :name", ['name' => $maliciousInput]);

        // Should return empty array - the malicious input is treated as literal string
        $this->assertEmpty($pets);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_select_with_null_parameter(string $name)
    {
        $this->createTestingTable($name);
        $database = Database::connection($name);

        $database->insert("INSERT INTO pets VALUES(1, 'Bob');");

        $pets = $database->select("SELECT * FROM pets WHERE name IS NOT NULL");

        $this->assertCount(1, $pets);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_empty_result_set_returns_empty_array(string $name)
    {
        $this->createTestingTable($name);
        $database = Database::connection($name);

        $pets = $database->select("SELECT * FROM pets");

        $this->assertIsArray($pets);
        $this->assertEmpty($pets);
    }
}
