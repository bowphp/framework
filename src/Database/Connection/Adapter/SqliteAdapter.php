<?php

declare(strict_types=1);

namespace Bow\Database\Connection\Adapter;

use Bow\Database\Connection\AbstractConnection;
use InvalidArgumentException;
use PDO;

class SqliteAdapter extends AbstractConnection
{
    /**
     * The connexion name
     *
     * @var string
     */
    protected ?string $name = 'sqlite';

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
    public function connection(): void
    {
        if (!isset($this->config['driver'])) {
            throw new InvalidArgumentException("Please select the right sqlite driver");
        }

        if (!isset($this->config['database'])) {
            throw new InvalidArgumentException('The database is not defined');
        }

        // Build the PDO connection
        $this->pdo = new PDO('sqlite:' . $this->config['database']);

        // Set the PDO attributes that we want
        $this->pdo->setAttribute(
            PDO::ATTR_DEFAULT_FETCH_MODE,
            isset($this->config['fetch']) ? $this->config['fetch'] : $this->fetch
        );

        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_EMPTY_STRING);
    }
}
