<?php


class TestConnection extends \PHPUnit_Framework_TestCase
{
    public function testGetSqliteConnection()
    {
        $sqliteAdapter = new \Bow\Database\Connection\Adapter\SqliteAdapter([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => ''
        ]);
        $this->assertInstanceOf(\Bow\Database\Connection\AbstractConnection::class, $sqliteAdapter);
    }

    public function testGetMysqlConnection()
    {
        $mysqlAdapter = new \Bow\Database\Connection\Adapter\MysqlAdapter([
            'hostname' => 'localhost',
            'username' => $GLOBALS['DB_USER'],
            'password' => $GLOBALS['DB_PASSWORD'],
            'database' => $GLOBALS['DB_DATABASENAME'],
            'charset'  => $GLOBALS['DB_CHARSET'],
            'collation' => $GLOBALS['DB_COLLATE'],
            'port' => null,
            'socket' => null
        ]);
        $this->assertInstanceOf(\Bow\Database\Connection\AbstractConnection::class, $mysqlAdapter);
    }

    public function testGetMysqlPdo()
    {
        $mysqlAdapter = new \Bow\Database\Connection\Adapter\MysqlAdapter([
            'hostname' => 'localhost',
            'username' => $GLOBALS['DB_USER'],
            'password' => $GLOBALS['DB_PASSWORD'],
            'database' => $GLOBALS['DB_DATABASENAME'],
            'charset'  => $GLOBALS['DB_CHARSET'],
            'collation' => $GLOBALS['DB_COLLATE'],
            'port' => null,
            'socket' => null
        ]);
        $this->assertInstanceOf(\PDO::class, $mysqlAdapter->getConnection());
    }

    public function testGetSqlitePdo()
    {
        $sqliteAdapter = new \Bow\Database\Connection\Adapter\SqliteAdapter([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => ''
        ]);
        $this->assertInstanceOf(\PDO::class, $sqliteAdapter->getConnection());
    }
}