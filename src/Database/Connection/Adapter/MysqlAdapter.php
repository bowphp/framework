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
     * Port par defaut
     */
    const PORT = 3306;

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

        // Formatage des paramètres de connection
        $host  = "mysql:host=".$hostname.$port;
        $database = "dbname=".$this->config['database'];
        $username = $this->config["username"];
        $password = $this->config["password"];

        // Configuration suppelement coté PDO
        $options = [
            PDO::ATTR_DEFAULT_FETCH_MODE => isset($this->config['fetch']) ? $this->config['fetch'] : $this->fetch,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . Str::upper($this->config["charset"]),
            PDO::ATTR_ORACLE_NULLS => PDO::NULL_EMPTY_STRING
        ];

        $this->pdo = new PDO($host.';'.$database, $username, $password, $options);
    }
}
