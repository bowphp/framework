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
        if (isset($this->config['socket']) && !empty($this->config['socket'])) {
            $hostname = $this->config['socket'];
            $port = '';
        } else {
            $hostname = $this->config['hostname'] ?? null;
            $port = (string)($this->config['port'] ?? self::PORT);
        }

        // Check the existence of database definition
        if (!isset($this->config['database'])) {
            throw new InvalidArgumentException("The database is not defined");
        }

        // Formatting connection parameters
        $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s", $hostname, $port, $this->config['database']);

        $username = $this->config["username"];
        $password = $this->config["password"];

        // Configuration the PDO attributes that we want to set
        $options = [
            PDO::ATTR_DEFAULT_FETCH_MODE => $this->config['fetch'] ?? $this->fetch,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . Str::upper($this->config["charset"]),
            PDO::ATTR_ORACLE_NULLS => PDO::NULL_EMPTY_STRING
        ];

        // Build the PDO connection
        $this->pdo = new PDO($dsn, $username, $password, $options);
    }
}
