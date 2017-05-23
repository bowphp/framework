<?php

class ConnectionTest extends \PHPUnit_Framework_TestCase
{
    public function testGetSqliteConnection()
    {
        $sqliteAdapter = new \Bow\Database\Connection\Adapter\SqliteAdapter([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => ''
        ]);
        $this->assertInstanceOf(\Bow\Database\Connection\AbstractConnection::class, $sqliteAdapter);
        return $sqliteAdapter;
    }

    /**
     * @depends testGetSqliteConnection
     */
    public function testGetSqlitePdo($sqliteAdapter)
    {
        $this->assertInstanceOf(\PDO::class, $sqliteAdapter->getConnection());
    }

    public function testGetMysqlConnection()
    {
        $mysqlAdapter = new \Bow\Database\Connection\Adapter\MysqlAdapter([
            'hostname' => 'localhost',
            'username' => getenv('DB_USER') == 'travis' ? getenv('DB_USER') : 'test',
            'password' => getenv('DB_USER') == 'travis' ? '' : getenv('DB_PASSWORD'),
            'database' => 'test',
            'charset'  => getenv('DB_CHARSET') ? getenv('DB_CHARSET') : 'utf8',
            'collation' => getenv('DB_COLLATE') ? getenv('DB_COLLATE') : '',
            'port' => null,
            'socket' => null
        ]);
        $this->assertInstanceOf(\Bow\Database\Connection\AbstractConnection::class, $mysqlAdapter);
        return $mysqlAdapter;
    }

    /**
     * @depends testGetMysqlConnection
     */

    public function testGetMysqlPdo($mysqlAdapter)
    {
        $this->assertInstanceOf(\PDO::class, $mysqlAdapter->getConnection());
    }
}