<?php

declare(strict_types=1);

namespace Bow\Database\Connection\Adapters;

use Bow\Database\Connection\AbstractConnection;
use Bow\Support\Str;
use InvalidArgumentException;
use PDO;

class MysqlAdapter extends AbstractConnection
{
    /**
     * Default PORT
     *
     * @var int
     */
    public const PORT = 3306;
    /**
     * The connexion nane
     *
     * @var ?string
     */
    protected ?string $name = 'mysql';

    /**
     * Validate the connection configuration.
     *
     * @param  array $config
     * @return void
     */
    protected function validateConfig(array $config): void
    {
        // Check the existence of database definition
        if (!isset($config['database'])) {
            throw new InvalidArgumentException("The database is not defined");
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
        // Build of the mysql dsn
        if (isset($config['socket']) && !empty($config['socket'])) {
            $hostname = $config['socket'];
            $port = '';
        } else {
            $hostname = $config['hostname'] ?? null;
            $port = (string)($config['port'] ?? self::PORT);
        }

        // Formatting connection parameters
        $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s", $hostname, $port, $config['database']);

        $username = $config["username"];
        $password = $config["password"];

        // Configuration the PDO attributes that we want to set
        $options = [
            PDO::ATTR_DEFAULT_FETCH_MODE => $config['fetch'] ?? $this->fetch,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . Str::upper($config["charset"]),
            PDO::ATTR_ORACLE_NULLS => PDO::NULL_EMPTY_STRING
        ];

        // Build the PDO connection
        return new PDO($dsn, $username, $password, $options);
    }
}
