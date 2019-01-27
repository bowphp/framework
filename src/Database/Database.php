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
     * The adapter instance
     *
     * @var AbstractConnection;
     */
    private static $adapter;

    /**
     * The singleton Database instance
     *
     * @var Database
     */
    private static $instance;

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
    private static $name;

    /**
     * Load configuration
     *
     * @param array $config
     *
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
     * Returns the Database instance
     *
     * @return Database
     */
    public static function getInstance()
    {
        static::verifyConnection();

        return static::$instance;
    }

    /**
     * Connection, starts the connection on the DB
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
                throw new ConnectionException('This driver is not praised');
            }

            static::$adapter->setFetchMode(static::$config['fetch']);
        }

        if (static::$adapter->getConnection() instanceof PDO && $name == static::$name) {
            return static::getInstance();
        }

        return static::getInstance();
    }

    /**
     * Get connexion nane
     *
     * @return string|null
     */
    public static function getConnectionName()
    {
        return static::$name;
    }

    /**
     * Get adapter connexion instance
     *
     * @return AbstractConnection
     */
    public static function getConnectionAdapter()
    {
        static::verifyConnection();

        return static::$adapter;
    }

    /**
     * Execute an UPDATE request
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
     * Execute a SELECT request
     *
     * @param  string $sqlstatement
     * @param  array        $data
     * @return mixed|null
     */
    public static function select($sqlstatement, array $data = [])
    {
        static::verifyConnection();

        if (!preg_match(
            "/^(select\s.+?\sfrom\s.+;?|desc\s.+;?)$/i",
            $sqlstatement
        )) {
            throw new DatabaseException(
                'Syntax Error on the Request',
                E_USER_ERROR
            );
        }

        $pdostatement = static::$adapter
            ->getConnection()
            ->prepare($sqlstatement);

        static::$adapter->bind(
            $pdostatement,
            Sanitize::make($data, true)
        );

        $pdostatement->execute();

        return Sanitize::make($pdostatement->fetchAll());
    }

    /**
     * Executes a select query and returns a single record
     *
     * @param  string $sqlstatement
     * @param  array  $data
     * @return mixed|null
     */
    public static function selectOne($sqlstatement, array $data = [])
    {
        static::verifyConnection();

        if (!preg_match("/^select\s.+?\sfrom\s.+;?$/i", $sqlstatement)) {
            throw new DatabaseException(
                'Syntax Error on the Request',
                E_USER_ERROR
            );
        }

        // Prepare query
        $pdostatement = static::$adapter
            ->getConnection()
            ->prepare($sqlstatement);

        // Bind data
        static::$adapter->bind($pdostatement, $data);

        // Execute query
        $pdostatement->execute();

        return Sanitize::make($pdostatement->fetch());
    }

    /**
     * Execute an insert query
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
        )) {
            throw new DatabaseException(
                'Syntax Error on the Request',
                E_USER_ERROR
            );
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
     * Executes a request of type DROP | CREATE TABLE | TRAUNCATE | ALTER Builder
     *
     * @param string $sqlstatement
     * @return bool
     */
    public static function statement($sqlstatement)
    {
        static::verifyConnection();

        if (!preg_match(
            "/^((drop|alter|create)\s+(?:(?:temp|temporary)\s+)?table|truncate|call)(\s+)?(.+?);?$/i",
            $sqlstatement
        )) {
            throw new DatabaseException(
                'Syntax Error on the Request',
                E_USER_ERROR
            );
        }

        return static::$adapter
            ->getConnection()
            ->exec($sqlstatement) === 0;
    }

    /**
     * Execute a delete request
     *
     * @param  $sqlstatement
     * @param  array        $data
     * @return bool
     */
    public static function delete($sqlstatement, array $data = [])
    {
        static::verifyConnection();

        if (!preg_match(
            "/^delete\sfrom\s[\w\d_`]+\swhere\s.+;?$/i",
            $sqlstatement
        )) {
            throw new DatabaseException(
                'Syntax Error on the Request',
                E_USER_ERROR
            );
        }

        return static::executePrepareQuery($sqlstatement, $data);
    }

    /**
     * Load the query builder factory on table name
     *
     * @param string $table
     * @return QueryBuilder
     */
    public static function table($table)
    {
        static::verifyConnection();

        $table = static::$adapter->getTablePrefix().$table;

        return new QueryBuilder(
            $table,
            static::$adapter->getConnection()
        );
    }

    /**
     * Starting the start of a transaction
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
     * Check if database execution is in transation
     *
     * @return bool
     */
    public static function inTransaction()
    {
        static::verifyConnection();

        return static::$adapter->getConnection()->inTransaction();
    }

    /**
     * Validate a transaction
     */
    public static function commit()
    {
        static::verifyConnection();

        static::$adapter->getConnection()->commit();
    }

    /**
     * Cancel a transaction
     */
    public static function rollback()
    {
        static::verifyConnection();

        static::$adapter->getConnection()->rollBack();
    }

    /**
     * Starts the verification of the connection establishment
     *
     * @throws
     */
    private static function verifyConnection()
    {
        if (is_null(static::$adapter)) {
            static::connection(static::$name);
        }
    }
    
    /**
     * Retrieves the identifier of the last record.
     *
     * @param  string $name
     * @return int
     */
    public static function lastInsertId($name = null)
    {
        static::verifyConnection();

        return (int) static::$adapter
            ->getConnection()
            ->lastInsertId($name);
    }

    /**
     * Execute the request of type delete insert update
     *
     * @param string $sqlstatement
     * @param array $data
     * @return mixed
     */
    private static function executePrepareQuery($sqlstatement, array $data = [])
    {
        $pdostatement = static::$adapter
            ->getConnection()
            ->prepare($sqlstatement);

        static::$adapter->bind(
            $pdostatement,
            Sanitize::make($data, true)
        );

        $pdostatement->execute();

        $r = $pdostatement->rowCount();

        return $r;
    }

    /**
     * PDO, returns the instance of the connection.
     *
     * @return PDO
     */
    public static function getPdo()
    {
        static::verifyConnection();

        return static::$adapter->getConnection();
    }

    /**
     * Modify the PDO instance
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
     * @param string $method
     * @param array  $arguments
     *
     * @throws DatabaseException
     *
     * @return mixed
     */
    public function __call($method, array $arguments)
    {
        if (method_exists(static::$instance, $method)) {
            return call_user_func_array(
                [static::$instance, $method],
                $arguments
            );
        }

        throw new DatabaseException(
            sprintf("%s is not a method.", $method),
            E_USER_ERROR
        );
    }
}
