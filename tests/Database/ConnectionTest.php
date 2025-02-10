<?php

namespace Bow\Tests\Database;

use Bow\Configuration\Loader as ConfigurationLoader;
use Bow\Database\Connection\AbstractConnection;
use Bow\Database\Connection\Adapters\MysqlAdapter;
use Bow\Database\Connection\Adapters\PostgreSQLAdapter;
use Bow\Database\Connection\Adapters\SqliteAdapter;
use Bow\Tests\Config\TestingConfiguration;

class ConnectionTest extends \PHPUnit\Framework\TestCase
{
    private static ConfigurationLoader $config;

    public static function setUpBeforeClass(): void
    {
        static::$config = TestingConfiguration::getConfig();
    }

    public function test_get_sqlite_connection()
    {
        $config = static::$config["database"];
        $sqliteAdapter = new SqliteAdapter($config['connections']['sqlite']);

        $this->assertInstanceOf(AbstractConnection::class, $sqliteAdapter);

        return $sqliteAdapter;
    }

    /**
     * @depends test_get_sqlite_connection
     */
    public function test_get_sqlite_pdo($sqliteAdapter)
    {
        $this->assertInstanceOf(\PDO::class, $sqliteAdapter->getConnection());
        $this->assertEquals($sqliteAdapter->getConnection()->getAttribute(\PDO::ATTR_DRIVER_NAME), 'sqlite');
    }

    /**
     * @depends test_get_sqlite_connection
     */
    public function test_sqlite_adapter_name(SqliteAdapter $sqliteAdapter)
    {
        $this->assertEquals($sqliteAdapter->getName(), 'sqlite');
    }

    /**
     * @return MysqlAdapter
     */
    public function test_get_mysql_connection(): MysqlAdapter
    {
        $config = static::$config["database"];
        $mysqlAdapter = new MysqlAdapter($config['connections']['mysql']);

        $this->assertInstanceOf(AbstractConnection::class, $mysqlAdapter);

        return $mysqlAdapter;
    }

    /**
     * @depends test_get_mysql_connection
     */
    public function test_get_mysql_pdo(MysqlAdapter $mysqlAdapter)
    {
        $this->assertInstanceOf(\PDO::class, $mysqlAdapter->getConnection());
        $this->assertEquals($mysqlAdapter->getConnection()->getAttribute(\PDO::ATTR_DRIVER_NAME), 'mysql');
    }

    /**
     * @depends test_get_mysql_connection
     */
    public function test_mysql_adapter_name(MysqlAdapter $mysqlAdapter)
    {
        $this->assertEquals($mysqlAdapter->getName(), 'mysql');
    }

    /**
     * @return PostgreSQLAdapter
     */
    public function test_get_pgsql_connection(): PostgreSQLAdapter
    {
        $config = static::$config["database"];
        $pgsqlAdapter = new PostgreSQLAdapter($config['connections']['pgsql']);

        $this->assertInstanceOf(AbstractConnection::class, $pgsqlAdapter);

        return $pgsqlAdapter;
    }

    /**
     * @depends test_get_pgsql_connection
     */
    public function test_get_pgsql_pdo(PostgreSQLAdapter $pgsqlAdapter)
    {
        $this->assertInstanceOf(\PDO::class, $pgsqlAdapter->getConnection());
        $this->assertEquals($pgsqlAdapter->getConnection()->getAttribute(\PDO::ATTR_DRIVER_NAME), 'pgsql');
    }

    /**
     * @depends test_get_pgsql_connection
     */
    public function test_pgsql_adapter_name(PostgreSQLAdapter $pgsqlAdapter)
    {
        $this->assertEquals($pgsqlAdapter->getName(), 'pgsql');
    }
}
