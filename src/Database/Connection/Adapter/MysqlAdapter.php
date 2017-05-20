<?php
namespace Bow\Database\Connection\Adapter;

use PDO;
use Bow\Support\Str;
use Bow\Database\Connection\AbstractConnection;

class MysqlAdapter extends AbstractConnection
{
    /**
     * @var string
     */
    protected $name = 'mysql';

    /**
     * MysqlAdapter constructor.
     *
     * @param array $config
     */
    public function __construct($config)
    {
        $this->config = $config;
        $this->connection();
    }

    /**
     * @inheritdoc
     */
    public function connection()
    {
        // Construction de la dsn
        $dns  = "mysql:host=" . (isset($this->config['socket']) && $this->config['socket'] != null ? $this->config['socket'] : $this->config['hostname']);
        $dns .= isset($this->config['port']) && $this->config['port'] != null ? ":" . $this->config["port"] : "";
        $dns .= ";dbname=". $this->config['database'];
        $username = $this->config["username"];
        $password = $this->config["password"];
        // Configuration suppelement coté PDO
        $options = [
            PDO::ATTR_DEFAULT_FETCH_MODE => $this->fetch,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . Str::upper($this->config["charset"]),
            PDO::ATTR_ORACLE_NULLS => PDO::NULL_EMPTY_STRING
        ];

        $this->pdo = new PDO($dns, $username, $password, $options);
    }
}