<?php

namespace Bow\Database\Connection\Adapter;

use Bow\Database\Connection\AbstractConnection;
use Bow\Support\Str;
use PDO;

class MysqlAdapter extends AbstractConnection
{
    /**
     * The connexion nane
     *
     * @var string
     */
    protected $name = 'mysql';

    /**
     * Default PORT
     *
     * @var int
     */
    const PORT = 3306;

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
    public function connection()
    {
        // Build of the mysql dsn
        if (isset($this->config['socket']) && !is_null($this->config['socket']) && !empty($this->config['socket'])) {
            $hostname = $this->config['socket'];
        } else {
            $hostname = $this->config['hostname'];
        }

        $port = '';

        if ($hostname !== 'localhost' || $hostname == '127.0.0.1') {
            if (isset($this->config['port']) && !is_null($this->config['port']) && !empty($this->config['port'])) {
                $port = ':'.$this->config['port'];
            } else {
                $port = ':'.self::PORT;
            }
        }

        // Formatting connection parameters
        $host  = "mysql:host=".$hostname.$port;

        $database = "dbname=".$this->config['database'];

        $username = $this->config["username"];

        $password = $this->config["password"];

        // Configuration extra PDO side
        $options = [
            PDO::ATTR_DEFAULT_FETCH_MODE => isset($this->config['fetch']) ? $this->config['fetch'] : $this->fetch,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . Str::upper($this->config["charset"]),
            PDO::ATTR_ORACLE_NULLS => PDO::NULL_EMPTY_STRING
        ];

        $this->pdo = new PDO($host.';'.$database, $username, $password, $options);
    }
}
