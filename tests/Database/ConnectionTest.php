<?php

namespace Bow\Tests\Database;

use Bow\Tests\Config\TestingConfiguration;
use Bow\Database\Connection\AbstractConnection;
use Bow\Database\Connection\Adapter\MysqlAdapter;
use Bow\Database\Connection\Adapter\SqliteAdapter;
use Bow\Configuration\Loader as ConfigurationLoader;

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
}
