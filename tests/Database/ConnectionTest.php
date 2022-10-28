<?php

namespace Bow\Tests\Database;

use Bow\Configuration\Loader as ConfigurationLoader;
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
        $sqliteAdapter = new \Bow\Database\Connection\Adapter\SqliteAdapter($config['connections']['sqlite']);

        $this->assertInstanceOf(\Bow\Database\Connection\AbstractConnection::class, $sqliteAdapter);

        return $sqliteAdapter;
    }

    /**
     * @depends test_get_sqlite_connection
     */
    public function test_get_sqlite_pdo($sqliteAdapter)
    {
        $this->assertInstanceOf(\PDO::class, $sqliteAdapter->getConnection());
    }

    /**
     * @return \Bow\Database\Connection\Adapter\MysqlAdapter
     */
    public function test_get_mysql_connection()
    {
        $config = static::$config["database"];
        $mysqlAdapter = new \Bow\Database\Connection\Adapter\MysqlAdapter($config['connections']['mysql']);

        $this->assertInstanceOf(\Bow\Database\Connection\AbstractConnection::class, $mysqlAdapter);

        return $mysqlAdapter;
    }

    /**
     * @depends test_get_mysql_connection
     */
    public function test_get_mysql_pdo($mysqlAdapter)
    {
        $this->assertInstanceOf(\PDO::class, $mysqlAdapter->getConnection());
    }
}
