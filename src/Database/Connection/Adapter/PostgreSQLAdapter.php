<?php

declare(strict_types=1);

namespace Bow\Database\Connection\Adapter;

use PDO;
use InvalidArgumentException;
use Bow\Database\Connection\AbstractConnection;

class PostgreSQLAdapter extends AbstractConnection
{
    /**
     * The connexion nane
     *
     * @var ?string
     */
    protected ?string $name = 'pgsql';

    /**
     * Default PORT
     *
     * @var int
     */
    public const PORT = 5432;

    /**
     * MysqlAdapter constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        $this->connection();
    }

    /**
     * Make connexion
     *
     * @return void
     */
    public function connection(): void
    {
        // Build of the mysql dsn
        if (isset($this->config['socket']) && !is_null($this->config['socket']) && !empty($this->config['socket'])) {
            $hostname = $this->config['socket'];
            $port = '';
        } else {
            $hostname = $this->config['hostname'] ?? null;
            $port = (string) ($this->config['port'] ?? self::PORT);
        }

        // Check the existence of database definition
        if (!isset($this->config['database'])) {
            throw new InvalidArgumentException("The database is not defined");
        }

        // Formatting connection parameters
        $dsn  = sprintf("pgsql:host=%s;port=%s;dbname=%s", $hostname, $port, $this->config['database']);

        if (isset($this->config['sslmode'])) {
            $dsn .= ';sslmode=' . $this->config['sslmode'];
        }

        if (isset($this->config['sslrootcert'])) {
            $dsn .= ';sslrootcert=' . $this->config['sslrootcert'];
        }

        if (isset($this->config['sslcert'])) {
            $dsn .= ';sslcert=' . $this->config['sslcert'];
        }

        if (isset($this->config['sslkey'])) {
            $dsn .= ';sslkey=' . $this->config['sslkey'];
        }

        if (isset($this->config['sslcrl'])) {
            $dsn .= ';sslcrl=' . $this->config['sslcrl'];
        }

        if (isset($this->config['application_name'])) {
            $dsn .= ';application_name=' . $this->config['application_name'];
        }

        $username = $this->config["username"];
        $password = $this->config["password"];

        // Configuration the PDO attributes that we want to set
        $options = [
            PDO::ATTR_DEFAULT_FETCH_MODE => $this->config['fetch'] ?? $this->fetch,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];

        // Build the PDO connection
        $this->pdo = new PDO($dsn, $username, $password, $options);

        if ($this->config["charset"]) {
            $this->pdo->query('SET NAMES \'' . $this->config["charset"] . '\'');
        }
    }
}
