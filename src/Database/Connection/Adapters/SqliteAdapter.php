<?php

declare(strict_types=1);

namespace Bow\Database\Connection\Adapters;

use Bow\Database\Connection\AbstractConnection;
use InvalidArgumentException;
use PDO;

class SqliteAdapter extends AbstractConnection
{
    /**
     * The connexion name
     *
     * @var ?string
     */
    protected ?string $name = 'sqlite';

    /**
     * Validate the connection configuration.
     *
     * @param  array $config
     * @return void
     */
    protected function validateConfig(array $config): void
    {
        if (!isset($config['driver'])) {
            throw new InvalidArgumentException("Please select the right sqlite driver");
        }

        if (!isset($config['database'])) {
            throw new InvalidArgumentException('The database is not defined');
        }
    }

    /**
     * Build a PDO instance from the given configuration.
     *
     * @param  array $config
     * @return PDO
     */
    protected function makePdo(array $config): PDO
    {
        // Build the PDO connection
        $pdo = new PDO('sqlite:' . $config['database']);

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_EMPTY_STRING);
        $pdo->setAttribute(
            PDO::ATTR_DEFAULT_FETCH_MODE,
            $config['fetch'] ?? $this->fetch
        );

        return $pdo;
    }
}
