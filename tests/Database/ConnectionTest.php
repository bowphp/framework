<?php

namespace Bow\Tests\Database;

use Bow\Configuration\Loader as ConfigurationLoader;
use Bow\Database\Connection\AbstractConnection;
use Bow\Database\Connection\Adapters\MysqlAdapter;
use Bow\Database\Connection\Adapters\PostgreSQLAdapter;
use Bow\Database\Connection\Adapters\SqliteAdapter;
use Bow\Tests\Config\TestingConfiguration;
use InvalidArgumentException;
use PDO;

class ConnectionTest extends \PHPUnit\Framework\TestCase
{
    private static ?ConfigurationLoader $config = null;
    private static ?SqliteAdapter $sqliteAdapter = null;
    private static ?MysqlAdapter $mysqlAdapter = null;
    private static ?PostgreSQLAdapter $pgsqlAdapter = null;

    public static function setUpBeforeClass(): void
    {
        static::initializeConfig();
    }
    
    private static function initializeConfig(): void
    {
        if (static::$config !== null) {
            return;
        }
        
        static::$config = TestingConfiguration::getConfig();
        
        $database = static::$config["database"] ?? null;
        
        if (!$database) {
            throw new \RuntimeException("Database config not found");
        }
        
        // Initialize adapters once for all tests
        static::$sqliteAdapter = new SqliteAdapter($database['connections']['sqlite']);
        static::$mysqlAdapter = new MysqlAdapter($database['connections']['mysql']);
        static::$pgsqlAdapter = new PostgreSQLAdapter($database['connections']['pgsql']);
    }

    public function test_sqlite_connection_instance()
    {
        static::initializeConfig();  // Ensure config is initialized
        $this->assertNotNull(static::$sqliteAdapter, "SQLite adapter should not be null");
        $this->assertInstanceOf(AbstractConnection::class, static::$sqliteAdapter);
        $this->assertInstanceOf(SqliteAdapter::class, static::$sqliteAdapter);
    }

    public function test_sqlite_pdo_connection()
    {
        $pdo = static::$sqliteAdapter->getConnection();
        $this->assertInstanceOf(PDO::class, $pdo);
        $this->assertEquals('sqlite', $pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
    }

    public function test_sqlite_adapter_name()
    {
        $this->assertEquals('sqlite', static::$sqliteAdapter->getName());
    }

    public function test_sqlite_pdo_driver()
    {
        $this->assertEquals('sqlite', static::$sqliteAdapter->getPdoDriver());
    }

    public function test_sqlite_config_retrieval()
    {
        $config = static::$sqliteAdapter->getConfig();
        $this->assertIsArray($config);
        $this->assertArrayHasKey('driver', $config);
        $this->assertEquals('sqlite', $config['driver']);
    }

    public function test_sqlite_table_prefix()
    {
        $prefix = static::$sqliteAdapter->getTablePrefix();
        $this->assertIsString($prefix);
    }

    public function test_sqlite_charset()
    {
        $charset = static::$sqliteAdapter->getCharset();
        $this->assertIsString($charset);
        $this->assertNotEmpty($charset);
    }

    public function test_sqlite_collation()
    {
        $collation = static::$sqliteAdapter->getCollation();
        $this->assertIsString($collation);
        $this->assertNotEmpty($collation);
    }

    public function test_sqlite_set_fetch_mode()
    {
        static::$sqliteAdapter->setFetchMode(PDO::FETCH_ASSOC);
        $pdo = static::$sqliteAdapter->getConnection();
        $this->assertEquals(PDO::FETCH_ASSOC, $pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE));
        
        // Reset to default
        static::$sqliteAdapter->setFetchMode(PDO::FETCH_OBJ);
    }

    public function test_sqlite_connection_can_be_set()
    {
        $newPdo = new PDO('sqlite::memory:');
        static::$sqliteAdapter->setConnection($newPdo);
        
        $retrievedPdo = static::$sqliteAdapter->getConnection();
        $this->assertSame($newPdo, $retrievedPdo);
        
        // Restore original connection
        static::$sqliteAdapter->connection();
    }

    // ===== MySQL Tests =====
    
    public function test_mysql_connection_instance()
    {
        $this->assertInstanceOf(AbstractConnection::class, static::$mysqlAdapter);
        $this->assertInstanceOf(MysqlAdapter::class, static::$mysqlAdapter);
    }

    public function test_mysql_pdo_connection()
    {
        $pdo = static::$mysqlAdapter->getConnection();
        $this->assertInstanceOf(PDO::class, $pdo);
        $this->assertEquals('mysql', $pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
    }

    public function test_mysql_adapter_name()
    {
        $this->assertEquals('mysql', static::$mysqlAdapter->getName());
    }

    public function test_mysql_pdo_driver()
    {
        $this->assertEquals('mysql', static::$mysqlAdapter->getPdoDriver());
    }

    public function test_mysql_config_retrieval()
    {
        $config = static::$mysqlAdapter->getConfig();
        $this->assertIsArray($config);
        $this->assertArrayHasKey('driver', $config);
        $this->assertEquals('mysql', $config['driver']);
    }

    public function test_mysql_charset()
    {
        $charset = static::$mysqlAdapter->getCharset();
        $this->assertIsString($charset);
        $this->assertNotEmpty($charset);
    }

    public function test_mysql_collation()
    {
        $collation = static::$mysqlAdapter->getCollation();
        $this->assertIsString($collation);
        $this->assertNotEmpty($collation);
    }

    public function test_mysql_table_prefix()
    {
        $prefix = static::$mysqlAdapter->getTablePrefix();
        $this->assertIsString($prefix);
    }

    // ===== PostgreSQL Tests =====
    
    public function test_pgsql_connection_instance()
    {
        $this->assertInstanceOf(AbstractConnection::class, static::$pgsqlAdapter);
        $this->assertInstanceOf(PostgreSQLAdapter::class, static::$pgsqlAdapter);
    }

    public function test_pgsql_pdo_connection()
    {
        $pdo = static::$pgsqlAdapter->getConnection();
        $this->assertInstanceOf(PDO::class, $pdo);
        $this->assertEquals('pgsql', $pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
    }

    public function test_pgsql_adapter_name()
    {
        $this->assertEquals('pgsql', static::$pgsqlAdapter->getName());
    }

    public function test_pgsql_pdo_driver()
    {
        $this->assertEquals('pgsql', static::$pgsqlAdapter->getPdoDriver());
    }

    public function test_pgsql_config_retrieval()
    {
        $config = static::$pgsqlAdapter->getConfig();
        $this->assertIsArray($config);
        $this->assertArrayHasKey('driver', $config);
        $this->assertEquals('pgsql', $config['driver']);
    }

    public function test_pgsql_charset()
    {
        $charset = static::$pgsqlAdapter->getCharset();
        $this->assertIsString($charset);
        $this->assertNotEmpty($charset);
    }

    public function test_pgsql_collation()
    {
        $collation = static::$pgsqlAdapter->getCollation();
        $this->assertIsString($collation);
        $this->assertNotEmpty($collation);
    }

    public function test_pgsql_table_prefix()
    {
        $prefix = static::$pgsqlAdapter->getTablePrefix();
        $this->assertIsString($prefix);
    }

    // ===== Binding Tests =====
    
    public function test_bind_with_string_parameters()
    {
        $pdo = static::$sqliteAdapter->getConnection();
        $stmt = $pdo->prepare('SELECT :name AS name, :value AS value');
        
        $bindings = ['name' => 'test', 'value' => 'data'];
        $boundStmt = static::$sqliteAdapter->bind($stmt, $bindings);
        
        $this->assertInstanceOf(\PDOStatement::class, $boundStmt);
        $boundStmt->execute();
        $result = $boundStmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertEquals('test', $result['name']);
        $this->assertEquals('data', $result['value']);
    }

    public function test_bind_with_integer_parameters()
    {
        $pdo = static::$sqliteAdapter->getConnection();
        $stmt = $pdo->prepare('SELECT :id AS id, :count AS count');
        
        $bindings = ['id' => 123, 'count' => 456];
        $boundStmt = static::$sqliteAdapter->bind($stmt, $bindings);
        
        $boundStmt->execute();
        $result = $boundStmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertEquals(123, $result['id']);
        $this->assertEquals(456, $result['count']);
    }

    public function test_bind_with_null_parameters()
    {
        $pdo = static::$sqliteAdapter->getConnection();
        $stmt = $pdo->prepare('SELECT :value AS value');
        
        $bindings = ['value' => null];
        $boundStmt = static::$sqliteAdapter->bind($stmt, $bindings);
        
        $boundStmt->execute();
        $result = $boundStmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertNull($result['value']);
    }

    public function test_bind_with_mixed_parameters()
    {
        $pdo = static::$sqliteAdapter->getConnection();
        $stmt = $pdo->prepare('SELECT :string AS string, :integer AS integer, :null AS null_val');
        
        $bindings = [
            'string' => 'text',
            'integer' => 789,
            'null' => null
        ];
        $boundStmt = static::$sqliteAdapter->bind($stmt, $bindings);
        
        $boundStmt->execute();
        $result = $boundStmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertEquals('text', $result['string']);
        $this->assertEquals(789, $result['integer']);
        $this->assertNull($result['null_val']);
    }

    public function test_bind_with_float_parameters()
    {
        $pdo = static::$sqliteAdapter->getConnection();
        $stmt = $pdo->prepare('SELECT :price AS price');
        
        $bindings = ['price' => 19.99];
        $boundStmt = static::$sqliteAdapter->bind($stmt, $bindings);
        
        $boundStmt->execute();
        $result = $boundStmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertEquals(19.99, (float) $result['price']);
    }

    // ===== Error Handling Tests =====
    
    public function test_sqlite_missing_driver_throws_exception()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Please select the right sqlite driver");
        
        $invalidConfig = [];
        new SqliteAdapter($invalidConfig);
    }

    public function test_sqlite_missing_database_throws_exception()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The database is not defined");
        
        $invalidConfig = ['driver' => 'sqlite'];
        new SqliteAdapter($invalidConfig);
    }

    // ===== Data Provider Tests =====
    
    /**
     * @dataProvider adapterProvider
     */
    public function test_all_adapters_have_valid_names(AbstractConnection $adapter, string $expectedName)
    {
        $this->assertEquals($expectedName, $adapter->getName());
    }

    /**
     * @dataProvider adapterProvider
     */
    public function test_all_adapters_return_pdo_instance(AbstractConnection $adapter)
    {
        $this->assertInstanceOf(PDO::class, $adapter->getConnection());
    }

    /**
     * @dataProvider adapterProvider
     */
    public function test_all_adapters_have_config(AbstractConnection $adapter)
    {
        $config = $adapter->getConfig();
        $this->assertIsArray($config);
        $this->assertNotEmpty($config);
    }

    /**
     * @dataProvider adapterProvider
     */
    public function test_all_adapters_support_fetch_mode_changes(AbstractConnection $adapter)
    {
        $originalMode = $adapter->getConnection()->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE);
        
        $adapter->setFetchMode(PDO::FETCH_NUM);
        $this->assertEquals(PDO::FETCH_NUM, $adapter->getConnection()->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE));
        
        // Restore original mode
        $adapter->setFetchMode($originalMode);
    }

    public function adapterProvider(): array
    {
        // Initialize config if not already done
        static::initializeConfig();
        
        return [
            'sqlite' => [static::$sqliteAdapter, 'sqlite'],
            'mysql' => [static::$mysqlAdapter, 'mysql'],
            'pgsql' => [static::$pgsqlAdapter, 'pgsql'],
        ];
    }
}
