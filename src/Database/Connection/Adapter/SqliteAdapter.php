<?php

namespace Bow\Database\Connection\Adapter;

use Bow\Database\Connection\AbstractConnection;
use PDO;

class SqliteAdapter extends AbstractConnection
{
    /**
     * The connexion name
     *
     * @var string
     */
    protected $name = 'sqlite';

    /**
     * SqliteAdapter constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        $this->connection();
    }

    /**
     * @inheritDoc
     */
    public function connection()
    {
        $this->pdo = new PDO($this->config['driver'] . ':' . $this->config['database']);

        $this->pdo->setAttribute(
            PDO::ATTR_DEFAULT_FETCH_MODE,
            isset($this->config['fetch']) ? $this->config['fetch'] : $this->fetch
        );

        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_EMPTY_STRING);
    }
}
