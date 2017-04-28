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
    }

    public function testGetMysqlConnection()
    {
        $mysqlAdapter = new \Bow\Database\Connection\Adapter\MysqlAdapter([
            'hostname' => 'localhost',
            'username' => getenv('DB_USER') ? getenv('DB_USER') : 'travis',
            'password' => getenv('DB_PASSWORD') ? getenv('DB_PASSWORD') : '',
            'database' => getenv('DB_NAME') ? getenv('DB_NAME') : 'test',
            'charset'  => getenv('DB_CHARSET') ? getenv('DB_CHARSET') : 'utf8',
            'collation' => getenv('DB_COLLATE') ? getenv('DB_COLLATE') : '',
            'port' => null,
            'socket' => null
        ]);
        $this->assertInstanceOf(\Bow\Database\Connection\AbstractConnection::class, $mysqlAdapter);
    }

    public function testGetMysqlPdo()
    {
        $mysqlAdapter = new \Bow\Database\Connection\Adapter\MysqlAdapter([
            'hostname' => 'localhost',
            'username' => getenv('DB_USER') ? getenv('DB_USER') : 'travis',
            'password' => getenv('DB_PASSWORD') ? getenv('DB_PASSWORD') : '',
            'database' => getenv('DB_NAME') ? getenv('DB_NAME') : 'test',
            'charset'  => getenv('DB_CHARSET') ? getenv('DB_CHARSET') : 'utf8',
            'collation' => getenv('DB_COLLATE') ? getenv('DB_COLLATE') : '',
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