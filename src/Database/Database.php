<?php

namespace Bow\Database;

use Bow\Database\Connection\AbstractConnection;
use Bow\Database\Connection\Adapter\MysqlAdapter;
use Bow\Database\Connection\Adapter\SqliteAdapter;
use Bow\Database\Exception\ConnectionException;
use Bow\Database\Exception\DatabaseException;
use Bow\Security\Sanitize;
use PDO;
use StdClass;

class Database
{
    /**
     * @var AbstractConnection;
     */
    private static $adapter;

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
     * @param array $config
     * @return Database
     */
    public static function configure($config)
    {
        if (is_null(static::$instance)) {
            static::$instance = new self();

            static::$name = $config['default'];

            static::$config = $config;
        }

        return static::$instance;
    }

    /**
     * Retourne l'instance de Database
     *
     * @return Database
     */
    public static function getInstance()
    {
        static::verifyConnection();

        return static::$instance;
    }
    /**
     * connection, lance la connection sur la DB
     *
     * @param  null $name
     * @return null|Database
     *
     * @throws ConnectionException
     */
    public static function connection($name = null)
    {
        if (is_null($name) || strlen($name) == 0) {
            if (is_null(static::$name)) {
                static::$name = static::$config['default'];
            }

            $name = static::$name;
        }

        if (!isset(static::$config['connection'][$name])) {
            throw new ConnectionException('Le point de connection "' . $name . '" n\'est pas définie.');
        }

        if ($name !== static::$name) {
            static::$adapter = null;
        }

        $config = static::$config['connection'][$name];

        static::$name = $name;

        if (static::$adapter === null) {
            if ($name == 'mysql') {
                static::$adapter = new MysqlAdapter($config);
            } elseif ($name == 'sqlite') {
                static::$adapter = new SqliteAdapter($config);
            } else {
                throw new ConnectionException('Ce driver n\'est pas prie en compte.');
            }

            static::$adapter->setFetchMode(static::$config['fetch']);
        }

        if (static::$adapter->getConnection() instanceof PDO && $name == static::$name) {
            return static::getInstance();
        }

        return static::getInstance();
    }

    /**
     * currentZone, retourne la zone courante.
     *
     * @return string|null
     */
    public static function getConnectionName()
    {
        return static::$name;
    }

    /**
     * Permet de retouner l'instance de l'adapteur
     *
     * @return AbstractConnection
     */
    public static function getConnectionAdapter()
    {
        static::verifyConnection();

        return static::$adapter;
    }

    /**
     * éxécute une requête update
     *
     * @param  string $sqlstatement
     * @param  array  $data
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
     * @param  $sqlstatement
     * @param  array        $data
     * @return mixed|null
     */
    public static function select($sqlstatement, array $data = [])
    {
        static::verifyConnection();

        if (!preg_match("/^(select\s.+?\sfrom\s.+;?|desc\s.+;?)$/i", $sqlstatement)) {
            throw new DatabaseException('Erreur de synthax sur la réquete', E_USER_ERROR);
        }

        $pdostatement = static::$adapter->getConnection()->prepare($sqlstatement);

        static::$adapter->bind($pdostatement, Sanitize::make($data, true));

        $pdostatement->execute();

        return Sanitize::make($pdostatement->fetchAll());
    }

    /**
     * éxécute une requête select et retourne un seul enregistrement
     *
     * @param  $sqlstatement
     * @param  array        $data
     * @return mixed|null
     */
    public static function selectOne($sqlstatement, array $data = [])
    {
        static::verifyConnection();

        if (!preg_match("/^select\s.+?\sfrom\s.+;?$/i", $sqlstatement)) {
            throw new DatabaseException('Erreur de synthax sur la réquete', E_USER_ERROR);
        }

        $pdostatement = static::$adapter->getConnection()->prepare($sqlstatement);

        static::$adapter->bind($pdostatement, $data);

        $pdostatement->execute();

        return Sanitize::make($pdostatement->fetch());
    }

    /**
     * éxécute une requête insert
     *
     * @param  $sqlstatement
     * @param  array        $data
     * @return null
     */
    public static function insert($sqlstatement, array $data = [])
    {
        static::verifyConnection();

        if (!preg_match(
            "/^insert\s+into\s+[\w\d_-`]+\s?(\(.+\))?\s+(values\s?(\(.+\),?)+|\s?set\s+(.+)+);?$/i",
            $sqlstatement
        )
        ) {
            throw new DatabaseException('Erreur de synthax sur la réquete', E_USER_ERROR);
        }

        if (empty($data)) {
            $pdoStement = static::$adapter->getConnection()->prepare($sqlstatement);

            $pdoStement->execute();

            return $pdoStement->rowCount();
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

        if (!empty($collector)) {
            return static::executePrepareQuery($sqlstatement, $collector);
        }

        return $r;
    }

    /**
     * éxécute une requête de type DROP|CREATE TABLE|TRAUNCATE|ALTER Builder
     *
     * @param  $sqlstatement
     * @return bool
     */
    public static function statement($sqlstatement)
    {
        static::verifyConnection();

        if (!preg_match("/^((drop|alter|create)\s+table|truncate|call)(\s+)?(.+?);?$/i", $sqlstatement)) {
            throw new DatabaseException('Erreur de synthax sur la réquete', E_USER_ERROR);
        }

        return static::$adapter->getConnection()->exec($sqlstatement) === 0;
    }

    /**
     * éxécute une requête delete
     *
     * @param  $sqlstatement
     * @param  array        $data
     * @return bool
     */
    public static function delete($sqlstatement, array $data = [])
    {
        static::verifyConnection();

        if (!preg_match("/^delete\sfrom\s[\w\d_`]+\swhere\s.+;?$/i", $sqlstatement)) {
            throw new DatabaseException('Erreur de synthax sur la réquete', E_USER_ERROR);
        }

        return static::executePrepareQuery($sqlstatement, $data);
    }

    /**
     * Charge le factory Builder
     *
     * @param string $table
     * @return QueryBuilder
     */
    public static function table($table)
    {
        static::verifyConnection();

        $table = static::$adapter->getTablePrefix().$table;

        return new QueryBuilder($table, static::$adapter->getConnection());
    }

    /**
     * Lancement du debut d'un transaction
     *
     * @param callable $callback
     */
    public static function startTransaction(callable $callback = null)
    {
        static::verifyConnection();

        if (!static::$adapter->getConnection()->inTransaction()) {
            static::$adapter->getConnection()->beginTransaction();
        }

        if (is_callable($callback)) {
            try {
                call_user_func_array($callback, []);

                static::commit();
            } catch (DatabaseException $e) {
                static::rollback();
            }
        }
    }

    /**
     * Vérifie si l'execution de la base de donnée est en transation
     *
     * @return bool
     */
    public static function inTransaction()
    {
        static::verifyConnection();

        return static::$adapter->getConnection()->inTransaction();
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
     * @throws
     */
    private static function verifyConnection()
    {
        if (is_null(static::$adapter)) {
            static::connection(static::$name);
        }
    }
    
    /**
     * Récupère l'identifiant de la dernière enregistrement.
     *
     * @param  string $name
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
     * @param  $sqlstatement
     * @param  array        $data
     * @return mixed
     */
    private static function executePrepareQuery($sqlstatement, array $data = [])
    {
        $pdostatement = static::$adapter->getConnection()->prepare($sqlstatement);

        static::$adapter->bind($pdostatement, Sanitize::make($data, true));

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
     * @param array  $arguments
     *
     * @throws DatabaseException
     *
     * @return mixed
     */
    public function __call($method, array $arguments)
    {
        if (method_exists(static::$instance, $method)) {
            return call_user_func_array([static::$instance, $method], $arguments);
        }

        throw new DatabaseException("$method n'est pas une methode.", E_USER_ERROR);
    }
}
