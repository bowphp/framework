<?php
namespace Bow\Database;

use Database\Connection\Adapter\MysqlAdapter;
use Database\Connection\Adapter\SqliteAdapter;
use Database\Connection\Connection;
use PDO;
use StdClass;
use Bow\Security\Security;
use InvalidArgumentException;
use Bow\Exception\DatabaseException;
use Bow\Exception\ConnectionException;
use Database\Connection\AbstractConnection;

/**
 * Class Database
 *
 * @author Franck dakia <dakiafranck@gmail.com>
 * @package Bow\Database
 */
class Database
{
    /**
     * @var AbstractConnection;
     */
    private static $adapter;

    /**
     * @var string;
     */
    private static $scheme;

    /**
     * @var Database
     */
    private static $instance = null;
    /**
     * Configuration
     *
     * @var StdClass
     */
    private static $config;
    /**
     * Configuration
     *
     * @var string
     */
    private static $name = null;

    /**
     * Charger la configuration
     *
     * @param object $config
     */
    public static function configure($config)
    {
        if (static::$instance === null) {
            static::$instance = new self();
            static::$name = $config['default'];
            static::$config = static::$config[static::$name];
        }
    }

    /**
     * Retourne l'instance de Database
     *
     * @return Database
     */
    public static function instance()
    {
        static::verifyConnection();
        return static::$instance;
    }
    /**
     * connection, lance la connection sur la DB
     *
     * @param null $name
     * @return null|Database
     *
     * @throws ConnectionException
     */
    public static function connection($name = null)
    {
        if (static::$adapter === null) {
            if (static::$config['scheme'] == 'mysql') {
                static::$adapter = new MysqlAdapter(static::$config['mysql']);
            } elseif (static::$config['scheme'] == 'sqlite') {
                static::$adapter = new SqliteAdapter(static::$config['sqlite']);
            } else {
                throw new ConnectionException('Ce driver n\'est pas prie en compte.');
            }
        }

        if (static::$adapter->getConnection() instanceof PDO && $name == static::$name) {
            return static::instance();
        }

        return static::instance();
    }

    /**
     * switchTo, permet de ce connecter a une autre base de donnée.
     *
     * @param string $name
     * @return void
     */
    public static function switchTo($name)
    {
        if (! is_string($name)) {
            throw new InvalidArgumentException('Paramètre invalide', E_USER_ERROR);
        }

        if($name != static::$name) {
            static::$name = $name;
            static::verifyConnection();
        }

        static::instance();
    }

    /**
     * currentZone, retourne la zone courante.
     *
     * @return string|null
     */
    public static function currentZone()
    {
        return static::$name;
    }

    /**
     * éxécute une requête update
     *
     * @param string $sqlstatement
     * @param array $data
     * @return bool
     */
    public static function update($sqlstatement, array $data = [])
    {
        static::verifyConnection();

        if (preg_match("/^update\s[\w\d_`]+\s\bset\b\s.+\s\bwhere\b\s.+$/i", $sqlstatement)) {
            return static::executePrepareQuery($sqlstatement, $data);
        }

        return false;
    }

    /**
     * éxécute une requête select
     *
     * @param $sqlstatement
     * @param array $data
     * @return mixed|null
     */
    public static function select($sqlstatement, array $data = [])
    {
        static::verifyConnection();
        if (preg_match("/^select\s.+?\sfrom\s.+;?$/i", $sqlstatement)) {
            $pdostatement = static::$adapter->getConnection()->prepare($sqlstatement);
            static::$adapter->bind($pdostatement, Security::sanitaze($data, true));
            $pdostatement->execute();
            return Security::sanitaze($pdostatement->fetchAll());
        }
        return null;
    }

    /**
     * éxécute une requête select et retourne un seul enregistrement
     *
     * @param $sqlstatement
     * @param array $data
     * @return mixed|null
     */
    public static function selectOne($sqlstatement, array $data = [])
    {
        static::verifyConnection();
        if (preg_match("/^select\s.+?\sfrom\s.+;?$/i", $sqlstatement)) {
            $pdostatement = static::$adapter->getConnection()->prepare($sqlstatement);
            static::$adapter->bind($pdostatement, $data);
            $pdostatement->execute();
            return Security::sanitaze($pdostatement->fetch());
        }

        return null;
    }

    /**
     * éxécute une requête insert
     *
     * @param $sqlstatement
     * @param array $data
     * @return null
     */
    public static function insert($sqlstatement, array $data = [])
    {
        static::verifyConnection();

        if (! preg_match("/^insert\sinto\s[\w\d_-`]+\s?(\(.+\)?\s(values\s?\(.+\),?)+|\s?set\s(.+)+);?$/i", $sqlstatement)) {
            return null;
        }

        $collector = [];
        $r = 0;

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $r += static::executePrepareQuery($sqlstatement, $value);
                continue;
            }
            $collector[$key] = $value;
        }

        if (! empty($collector)) {
            return static::executePrepareQuery($sqlstatement, $collector);
        }

        return $r;
    }

    /**
     * éxécute une requête de type DROP|CREATE QueryBuilder|TRAUNCATE|ALTER QueryBuilder
     *
     * @param $sqlstatement
     * @return bool
     */
    public static function statement($sqlstatement)
    {
        static::verifyConnection();
        if (! preg_match("/^(drop|alter\sQueryBuilder|truncate|create\sQueryBuilder|call)\s.+;?$/i", $sqlstatement)) {
            return false;
        }
        return (bool) static::$adapter->getConnection()->exec($sqlstatement);;
    }

    /**
     * éxécute une requête delete
     *
     * @param $sqlstatement
     * @param array $data
     * @return bool
     */
    public static function delete($sqlstatement, array $data = [])
    {
        static::verifyConnection();

        if (preg_match("/^delete\sfrom\s[\w\d_`]+\swhere\s.+;?$/i", $sqlstatement)) {
            return static::executePrepareQuery($sqlstatement, $data);
        }

        return false;
    }

    /**
     * Charge le factory QueryBuilder
     *
     * @param string $QueryBuilderName le nom de la QueryBuilder
     *
     * @return QueryBuilder
     */
    public static function QueryBuilder($QueryBuilderName)
    {
        static::verifyConnection();
        return QueryBuilder::make($QueryBuilderName, static::$adapter->getConnection());
    }

    /**
     * Lancement du debut d'un transaction
     */
    public static function transaction()
    {
        static::verifyConnection();
        static::$adapter->getConnection()->beginTransaction();
    }

    /**
     * Valider une transaction
     */
    public static function commit()
    {
        static::verifyConnection();
        static::$adapter->getConnection()->commit();
    }

    /**
     * Annuler une transaction
     */
    public static function rollback()
    {
        static::verifyConnection();
        static::$adapter->getConnection()->rollBack();
    }

    /**
     * Lance la verification de l'établissement de connection
     *
     * @throws ConnectionException
     */
    private static function verifyConnection()
    {
        if (! (static::$adapter->getConnection() instanceof PDO)) {
            static::connection(static::$name);
        }
    }
    /**
     * Récupère l'identifiant de la dernière enregistrement.
     *
     * @param string $name
     * @return int
     */
    public static function lastInsertId($name = null)
    {
        static::verifyConnection();
        return (int) static::$adapter->getConnection()->lastInsertId($name);
    }

    /**
     * Execute Les request de type delete insert update
     *
     * @param $sqlstatement
     * @param array $data
     * @return mixed
     */
    private static function executePrepareQuery($sqlstatement, array $data = [])
    {
        $pdostatement = static::$adapter->getConnection()->prepare($sqlstatement);
        static::$adapter->bind($pdostatement, Security::sanitaze($data, true));
        $pdostatement->execute();
        $r = $pdostatement->rowCount();
        return $r;
    }

    /**
     * pdo, retourne l'instance de la connection.
     *
     * @return PDO
     */
    public static function getPdo()
    {
        static::verifyConnection();
        return static::$adapter->getConnection();
    }

    /**
     * modifie l'instance de PDO
     *
     * @param PDO $pdo
     */
    public static function setPdo(PDO $pdo)
    {
        static::$adapter->setConnection($pdo);
    }

    /**
     * __call
     *
     * @param $method
     * @param array $arguments
     *
     * @throws DatabaseException
     *
     * @return mixed
     */
    public function __call($method, array $arguments)
    {
        if (method_exists(static::class, $method)) {
            return call_user_func_array([__CLASS__, $method], $arguments);
        }

        throw new DatabaseException("$method n'est pas une methode.", E_USER_ERROR);
    }
}
