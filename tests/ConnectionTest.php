<?php

class ConnectionTest extends \PHPUnit\Framework\TestCase
{
    public function test_get_sqlite_connection()
    {
        $config = require __DIR__.'/config/database.php';

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
        $config = require __DIR__.'/config/database.php';

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
