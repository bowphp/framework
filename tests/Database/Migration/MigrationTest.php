<?php

namespace Bow\Tests\Database\Migration;

use Bow\Database\Database;
use Bow\Database\Exception\MigrationException;
use Bow\Database\Migration\Migration;
use Bow\Database\Migration\Table;
use Bow\Tests\Config\TestingConfiguration;
use Bow\Tests\Database\Stubs\MigrationExtendedStub;
use Exception;

class MigrationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * The migration instance
     *
     * @var Migration
     */
    private Migration $migration;

    /**
     * Track tables created during tests for cleanup
     *
     * @var array
     */
    private array $testTables = [];

    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();
        Database::configure($config["database"]);
    }

    protected function setUp(): void
    {
        $this->migration = new MigrationExtendedStub();
        $this->testTables = [];
        ob_start();
    }

    protected function tearDown(): void
    {
        ob_get_clean();
        
        // Clean up all test tables
        foreach ($this->testTables as $table => $connections) {
            foreach ($connections as $name) {
                try {
                    Database::connection($name)->statement("DROP TABLE IF EXISTS {$table}");
                } catch (Exception $e) {
                    // Ignore cleanup errors
                }
            }
        }
    }

    /**
     * Track a table for cleanup
     *
     * @param string $table
     * @param string $connection
     * @return void
     */
    private function trackTable(string $table, string $connection): void
    {
        if (!isset($this->testTables[$table])) {
            $this->testTables[$table] = [];
        }
        $this->testTables[$table][] = $connection;
    }

    // ===== Connection Tests =====

    /**
     * @dataProvider connectionNames
     */
    public function test_connection_switching(string $name)
    {
        $result = $this->migration->connection($name);
        
        $this->assertInstanceOf(Migration::class, $result);
        $this->assertEquals($name, $this->migration->getAdapterName());
    }

    /**
     * @dataProvider connectionNames
     */
    public function test_get_adapter_name(string $name)
    {
        $this->migration->connection($name);
        $adapterName = $this->migration->getAdapterName();
        
        $this->assertEquals($name, $adapterName);
        $this->assertIsString($adapterName);
    }

    /**
     * @dataProvider connectionNames
     */
    public function test_get_table_prefixed(string $name)
    {
        $this->migration->connection($name);
        $tableName = $this->migration->getTablePrefixed('users');
        
        $this->assertIsString($tableName);
        $this->assertStringContainsString('users', $tableName);
    }

    // ===== Create Table Tests =====

    /**
     * @dataProvider connectionNames
     */
    public function test_create_success(string $name)
    {
        $this->trackTable('bow_testing', $name);
        Database::connection($name)->statement("DROP TABLE IF EXISTS bow_testing");

        $status = $this->migration->connection($name)->create('bow_testing', function (Table $generator) use ($name) {
            $generator->addColumn('id', 'string', ['size' => 225, 'primary' => true]);
            $generator->addColumn('name', 'string', ['size' => 225]);
            $generator->addColumn('lastname', 'string', ['size' => 225]);
            if ($name === 'pgsql') {
                $generator->addColumn('created_at', 'timestamp');
            } else {
                $generator->addColumn('created_at', 'datetime');
            }
        });

        $this->assertInstanceOf(Migration::class, $status);
        
        // Verify table was created
        $result = Database::connection($name)->select('SELECT * FROM bow_testing');
        $this->assertIsArray($result);
    }

    /**
     * @dataProvider connectionNames
     */
    public function test_create_with_multiple_columns(string $name)
    {
        $this->trackTable('bow_users', $name);
        Database::connection($name)->statement("DROP TABLE IF EXISTS bow_users");

        $status = $this->migration->connection($name)->create('bow_users', function (Table $generator) use ($name) {
            $generator->addColumn('id', 'int', ['primary' => true, 'autoincrement' => true]);
            $generator->addColumn('username', 'string', ['size' => 100, 'unique' => true]);
            $generator->addColumn('email', 'string', ['size' => 255]);
            $generator->addColumn('age', 'int', ['nullable' => true]);
            if ($name === 'pgsql') {
                $generator->addColumn('created_at', 'timestamp');
            } else {
                $generator->addColumn('created_at', 'datetime');
            }
        });

        $this->assertInstanceOf(Migration::class, $status);
    }

    /**
     * @dataProvider connectionNames
     */
    public function test_create_fail_with_invalid_column_type(string $name)
    {
        $this->trackTable('bow_testing', $name);
        Database::connection($name)->statement("DROP TABLE IF EXISTS bow_testing");

        if ($name != 'sqlite') {
            $this->expectException(MigrationException::class);
        }

        $status = $this->migration->connection($name)->create('bow_testing', function (Table $generator) {
            $generator->addColumn('id', 'string', ['size' => 225, 'primary' => true]);
            $generator->addColumn('name', 'typenotfound', ['size' => 225]); // SQLite transforms unknown types to NULL
            $generator->addColumn('lastname', 'string', ['size' => 225]);
            $generator->addColumn('created_at', 'datetime');
        });

        if ($name == 'sqlite') {
            $this->assertInstanceOf(Migration::class, $status);
        }
    }

    /**
     * @dataProvider connectionNames
     */
    public function test_create_empty_table(string $name)
    {
        $this->trackTable('bow_empty', $name);
        Database::connection($name)->statement("DROP TABLE IF EXISTS bow_empty");

        $status = $this->migration->connection($name)->create('bow_empty', function (Table $generator) {
            $generator->addColumn('id', 'int', ['primary' => true, 'autoincrement' => true]);
        });

        $this->assertInstanceOf(Migration::class, $status);
    }

    // ===== Alter Table Tests =====

    /**
     * @dataProvider connectionNames
     */
    public function test_alter_add_column(string $name)
    {
        $this->trackTable('bow_testing', $name);
        $this->migration->connection($name)->addSql('DROP TABLE IF EXISTS bow_testing');
        $this->migration->connection($name)->addSql('CREATE TABLE bow_testing (name varchar(255))');

        $status = $this->migration->connection($name)->alter('bow_testing', function (Table $generator) {
            $generator->addColumn('age', 'int', ['size' => 11, 'default' => 12]);
        });

        $this->assertInstanceOf(Migration::class, $status);
    }

    /**
     * @dataProvider connectionNames
     */
    public function test_alter_drop_column(string $name)
    {
        $this->trackTable('bow_testing', $name);
        $this->migration->connection($name)->addSql('DROP TABLE IF EXISTS bow_testing');
        $this->migration->connection($name)->addSql('CREATE TABLE bow_testing (name varchar(255), age int)');

        // SQLite has limited ALTER TABLE support - dropping columns requires table recreation
        if ($name === 'sqlite') {
            $this->expectException(MigrationException::class);
        }

        $status = $this->migration->connection($name)->alter('bow_testing', function (Table $generator) {
            $generator->dropColumn('age');
        });

        if ($name !== 'sqlite') {
            $this->assertInstanceOf(Migration::class, $status);
        }
    }

    /**
     * @dataProvider connectionNames
     */
    public function test_alter_success(string $name)
    {
        $this->trackTable('bow_testing', $name);
        $this->migration->connection($name)->addSql('DROP TABLE IF EXISTS bow_testing');
        $this->migration->connection($name)->addSql('CREATE TABLE bow_testing (name varchar(255))');

        $status = $this->migration->connection($name)->alter('bow_testing', function (Table $generator) {
            $generator->dropColumn('name');
            $generator->addColumn('age', 'int', ['size' => 11, 'default' => 12]);
        });

        $this->assertInstanceOf(Migration::class, $status);
    }

    /**
     * @dataProvider connectionNames
     */
    public function test_alter_fail_nonexistent_table(string $name)
    {
        $this->expectException(MigrationException::class);
        
        $this->migration->connection($name)->alter('nonexistent_table', function (Table $generator) {
            $generator->dropColumn('name');
        });
    }

    /**
     * @dataProvider connectionNames
     */
    public function test_alter_fail_invalid_column(string $name)
    {
        $this->trackTable('bow_testing', $name);
        $this->migration->connection($name)->addSql('DROP TABLE IF EXISTS bow_testing');
        $this->migration->connection($name)->addSql('CREATE TABLE bow_testing (name varchar(255))');

        $this->expectException(MigrationException::class);
        
        $this->migration->connection($name)->alter('bow_testing', function (Table $generator) {
            $generator->dropColumn('nonexistent_column');
        });
    }

    // ===== Drop Table Tests =====

    /**
     * @dataProvider connectionNames
     */
    public function test_drop_existing_table(string $name)
    {
        $this->trackTable('bow_testing', $name);
        Database::connection($name)->statement("DROP TABLE IF EXISTS bow_testing");
        Database::connection($name)->statement("CREATE TABLE bow_testing (id INT, name VARCHAR(255))");

        $status = $this->migration->connection($name)->drop('bow_testing');
        
        $this->assertInstanceOf(Migration::class, $status);
        
        // Verify table was dropped
        $this->expectException(Exception::class);
        Database::connection($name)->select('SELECT * FROM bow_testing');
    }

    /**
     * @dataProvider connectionNames
     */
    public function test_drop_nonexistent_table_throws_exception(string $name)
    {
        $this->expectException(MigrationException::class);
        
        $this->migration->connection($name)->drop('nonexistent_table_xyz');
    }

    /**
     * @dataProvider connectionNames
     */
    public function test_drop_if_exists_existing_table(string $name)
    {
        $this->trackTable('bow_testing', $name);
        Database::connection($name)->statement("DROP TABLE IF EXISTS bow_testing");
        Database::connection($name)->statement("CREATE TABLE bow_testing (id INT, name VARCHAR(255))");

        $status = $this->migration->connection($name)->dropIfExists('bow_testing', false);
        
        $this->assertInstanceOf(Migration::class, $status);
    }

    /**
     * @dataProvider connectionNames
     */
    public function test_drop_if_exists_nonexistent_table(string $name)
    {
        $status = $this->migration->connection($name)->dropIfExists('nonexistent_table_xyz', false);
        
        $this->assertInstanceOf(Migration::class, $status);
    }

    // ===== Add SQL Tests =====

    /**
     * @dataProvider connectionNames
     */
    public function test_addSql_create_and_insert(string $name)
    {
        $this->trackTable('bow_testing', $name);
        $this->migration->connection($name)->addSql('DROP TABLE IF EXISTS bow_testing');
        $this->migration->connection($name)->addSql('CREATE TABLE bow_testing (name varchar(255))');

        $result = Database::connection($name)->insert("INSERT INTO bow_testing(name) VALUES('Bow Framework')");
        $this->assertEquals(1, $result);

        $result = Database::connection($name)->select('SELECT * FROM bow_testing');
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    /**
     * @dataProvider connectionNames
     */
    public function test_addSql_multiple_statements(string $name)
    {
        $this->trackTable('bow_testing', $name);
        $this->migration->connection($name)->addSql('DROP TABLE IF EXISTS bow_testing');
        
        $status1 = $this->migration->connection($name)->addSql('CREATE TABLE bow_testing (id INT, name VARCHAR(255))');
        $status2 = $this->migration->connection($name)->addSql("INSERT INTO bow_testing VALUES(1, 'Test')");
        
        $this->assertInstanceOf(Migration::class, $status1);
        $this->assertInstanceOf(Migration::class, $status2);
        
        $result = Database::connection($name)->select('SELECT * FROM bow_testing');
        $this->assertCount(1, $result);
    }

    /**
     * @dataProvider connectionNames
     */
    public function test_addSql_drop_and_fail_insert(string $name)
    {
        $this->trackTable('bow_testing', $name);
        $this->migration->connection($name)->addSql('DROP TABLE IF EXISTS bow_testing');
        $this->migration->connection($name)->addSql('CREATE TABLE bow_testing (name varchar(255))');

        $result = Database::connection($name)->insert("INSERT INTO bow_testing(name) VALUES('Bow Framework')");
        $this->assertEquals(1, $result);

        $this->migration->connection($name)->addSql('DROP TABLE bow_testing');

        $this->expectException(Exception::class);
        Database::connection($name)->insert("INSERT INTO bow_testing(name) VALUES('Another Value')");
    }

    /**
     * @dataProvider connectionNames
     */
    public function test_addSql_invalid_syntax(string $name)
    {
        $this->expectException(MigrationException::class);
        
        $this->migration->connection($name)->addSql('INVALID SQL SYNTAX HERE');
    }

    // ===== Rename Table Tests =====

    /**
     * @dataProvider connectionNames
     */
    public function test_rename_table_success(string $name)
    {
        $this->trackTable('bow_old_table', $name);
        $this->trackTable('bow_new_table', $name);
        
        Database::connection($name)->statement("DROP TABLE IF EXISTS bow_old_table");
        Database::connection($name)->statement("DROP TABLE IF EXISTS bow_new_table");
        Database::connection($name)->statement("CREATE TABLE bow_old_table (id INT, name VARCHAR(255))");

        $status = $this->migration->connection($name)->renameTable('bow_old_table', 'bow_new_table');
        
        $this->assertInstanceOf(Migration::class, $status);
        
        // Verify new table exists
        $result = Database::connection($name)->select('SELECT * FROM bow_new_table');
        $this->assertIsArray($result);
    }

    /**
     * @dataProvider connectionNames
     */
    public function test_rename_nonexistent_table(string $name)
    {
        $this->expectException(MigrationException::class);
        
        $this->migration->connection($name)->renameTable('nonexistent_table', 'new_table');
    }

    // ===== Chain Operations Tests =====

    /**
     * @dataProvider connectionNames
     */
    public function test_chained_operations(string $name)
    {
        $this->trackTable('bow_chain_test', $name);
        
        $status = $this->migration->connection($name)
            ->addSql('DROP TABLE IF EXISTS bow_chain_test')
            ->addSql('CREATE TABLE bow_chain_test (id INT, name VARCHAR(255))')
            ->addSql("INSERT INTO bow_chain_test VALUES(1, 'Test')");
        
        $this->assertInstanceOf(Migration::class, $status);
        
        $result = Database::connection($name)->select('SELECT * FROM bow_chain_test');
        $this->assertCount(1, $result);
    }

    /**
     * @dataProvider connectionNames
     */
    public function test_create_alter_drop_sequence(string $name)
    {
        $this->trackTable('bow_sequence', $name);
        
        // Create
        $this->migration->connection($name)
            ->create('bow_sequence', function (Table $generator) {
                $generator->addColumn('id', 'int', ['primary' => true]);
                $generator->addColumn('name', 'string', ['size' => 100]);
            });
        
        // Alter
        $this->migration->connection($name)
            ->alter('bow_sequence', function (Table $generator) {
                $generator->addColumn('email', 'string', ['size' => 255]);
            });
        
        // Drop
        $status = $this->migration->connection($name)->drop('bow_sequence');
        
        $this->assertInstanceOf(Migration::class, $status);
    }

    // ===== Edge Cases =====

    /**
     * @dataProvider connectionNames
     */
    public function test_create_table_with_special_characters_in_name(string $name)
    {
        $this->trackTable('bow_test_123', $name);
        Database::connection($name)->statement("DROP TABLE IF EXISTS bow_test_123");

        $status = $this->migration->connection($name)->create('bow_test_123', function (Table $generator) {
            $generator->addColumn('id', 'int', ['primary' => true]);
        });

        $this->assertInstanceOf(Migration::class, $status);
    }

    /**
     * @dataProvider connectionNames
     */
    public function test_multiple_connection_switches(string $name)
    {
        $connections = ['mysql', 'sqlite', 'pgsql'];
        
        foreach ($connections as $conn) {
            $result = $this->migration->connection($conn);
            $this->assertEquals($conn, $this->migration->getAdapterName());
        }
        
        // Finally switch back to the original connection
        $this->migration->connection($name);
        $this->assertEquals($name, $this->migration->getAdapterName());
    }

    public function connectionNames()
    {
        return [
            ['mysql'], 
            ['sqlite'], 
            ['pgsql']
        ];
    }
}
