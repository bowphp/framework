<?php

declare(strict_types=1);

namespace Bow\Database\Connection\Adapters;

use Bow\Database\Connection\AbstractConnection;
use InvalidArgumentException;
use PDO;

class PostgreSQLAdapter extends AbstractConnection
{
    /**
     * Default PORT
     *
     * @var int
     */
    public const PORT = 5432;
    /**
     * The connexion nane
     *
     * @var ?string
     */
    protected ?string $name = 'pgsql';

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
        // Build of the pgsql dsn
        if (isset($config['socket']) && !is_null($config['socket']) && !empty($config['socket'])) {
            $hostname = $config['socket'];
            $port = '';
        } else {
            $hostname = $config['hostname'] ?? null;
            $port = (string)($config['port'] ?? self::PORT);
        }

        // Formatting connection parameters
        $dsn = sprintf("pgsql:host=%s;port=%s;dbname=%s", $hostname, $port, $config['database']);

        if (isset($config['sslmode'])) {
            $dsn .= ';sslmode=' . $config['sslmode'];
        }

        if (isset($config['sslrootcert'])) {
            $dsn .= ';sslrootcert=' . $config['sslrootcert'];
        }

        if (isset($config['sslcert'])) {
            $dsn .= ';sslcert=' . $config['sslcert'];
        }

        if (isset($config['sslkey'])) {
            $dsn .= ';sslkey=' . $config['sslkey'];
        }

        if (isset($config['sslcrl'])) {
            $dsn .= ';sslcrl=' . $config['sslcrl'];
        }

        if (isset($config['application_name'])) {
            $dsn .= ';application_name=' . $config['application_name'];
        }

        $username = $config["username"];
        $password = $config["password"];

        // Configuration the PDO attributes that we want to set
        $options = [
            PDO::ATTR_DEFAULT_FETCH_MODE => $config['fetch'] ?? $this->fetch,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];

        // Build the PDO connection
        $pdo = new PDO($dsn, $username, $password, $options);

        if ($config["charset"]) {
            $pdo->query('SET NAMES \'' . $config["charset"] . '\'');
        }

        return $pdo;
    }
}
