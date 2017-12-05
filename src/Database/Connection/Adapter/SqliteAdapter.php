<?php
namespace Bow\Database\Connection\Adapter;

use PDO;
use Bow\Database\Connection\AbstractConnection;

class SqliteAdapter extends AbstractConnection
{
    /**
     * @var string
     */
    protected $name = 'sqlite';

    /**
     * SqliteAdapter constructor.
     *
     * @param $config
     */
    public function __construct($config)
    {
        $this->config = $config;
        $this->connection();
    }

    /**
     * @inheritDoc
     */
    public function connection()
    {
        $this->pdo = new PDO($this->config['driver'].':'.$this->config['database']);
        $this->pdo->setAttribute(
            PDO::ATTR_DEFAULT_FETCH_MODE,
            isset($this->config['fetch']) ? $this->config['fetch'] : $this->fetch
        );
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_EMPTY_STRING);
    }
}
