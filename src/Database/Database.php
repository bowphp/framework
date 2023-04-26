<?php

declare(strict_types=1);

namespace Bow\Database;

use PDO;
use Bow\Security\Sanitize;
use Bow\Database\Exception\DatabaseException;
use Bow\Database\Connection\AbstractConnection;
use Bow\Database\Exception\ConnectionException;
use Bow\Database\Connection\Adapter\MysqlAdapter;
use Bow\Database\Connection\Adapter\SqliteAdapter;
use Bow\Database\Connection\Adapter\PostgreSQLAdapter;

class Database
{
    /**
     * The adapter instance
     *
     * @var ?AbstractConnection;
     */
    private static ?AbstractConnection $adapter = null;

    /**
     * The singleton Database instance
     *
     * @var Database
     */
    private static ?Database $instance = null;

    /**
     * Configuration
     *
     * @var array
     */
    private static array $config = [];

    /**
     * Configuration
     *
     * @var string
     */
    private static ?string $name = null;

    /**
     * Load configuration
     *
     * @param array $config
     *
     * @return Database
     */
    public static function configure(array $config): Database
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
    public static function getInstance(): Database
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
    public static function connection(?string $name = null): ?Database
    {
        if (is_null($name) || strlen($name) == 0) {
            $name = static::$name ?? static::$config['default'];
        }

        if (!isset(static::$config['connections'][$name])) {
            throw new ConnectionException('The connection "' . $name . '" is not defined.');
        }

        if ($name !== static::$name) {
            static::$adapter = null;
        }

        $config = static::$config['connections'][$name];

        static::$name = $name;

        if (static::$adapter === null) {
            if ($config['driver'] == 'mysql') {
                static::$adapter = new MysqlAdapter($config);
            } elseif ($config['driver'] == 'sqlite') {
                static::$adapter = new SqliteAdapter($config);
            } elseif ($config['driver'] == 'pgsql') {
                static::$adapter = new PostgreSQLAdapter($config);
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
     * Get connexion name
     *
     * @return string|null
     */
    public static function getConnectionName(): ?string
    {
        return static::$name;
    }

    /**
     * Get adapter connexion instance
     *
     * @return ?AbstractConnection
     */
    public static function getConnectionAdapter(): ?AbstractConnection
    {
        static::verifyConnection();

        return static::$adapter;
    }

    /**
     * Execute an UPDATE request
     *
     * @param  string $sql_statement
     * @param  array  $data
     * @return int
     */
    public static function update(string $sql_statement, array $data = []): int
    {
        static::verifyConnection();

        if (preg_match("/^update\s[\w\d_`]+\s+\bset\b\s.+\s\bwhere\b\s+.+$/i", $sql_statement)) {
            return static::executePrepareQuery($sql_statement, $data);
        }

        return 0;
    }

    /**
     * Execute a SELECT request
     *
     * @param  string $sql_statement
     * @param  array        $data
     * @return mixed|null
     */
    public static function select(string $sql_statement, array $data = []): mixed
    {
        static::verifyConnection();

        if (
            !preg_match(
                "/^(select\s.+?\sfrom\s.+;?|desc\s.+;?)$/i",
                $sql_statement
            )
        ) {
            throw new DatabaseException(
                'Syntax Error on the Request',
                E_USER_ERROR
            );
        }

        $pdo_statement = static::$adapter
            ->getConnection()
            ->prepare($sql_statement);

        static::$adapter->bind(
            $pdo_statement,
            Sanitize::make($data, true)
        );

        $pdo_statement->execute();

        return Sanitize::make($pdo_statement->fetchAll());
    }

    /**
     * Executes a select query and returns a single record
     *
     * @param  string $sql_statement
     * @param  array  $data
     * @return mixed|null
     */
    public static function selectOne(string $sql_statement, array $data = [])
    {
        static::verifyConnection();

        if (!preg_match("/^select\s.+?\sfrom\s.+;?$/i", $sql_statement)) {
            throw new DatabaseException(
                'Syntax Error on the Request',
                E_USER_ERROR
            );
        }

        // Prepare query
        $pdo_statement = static::$adapter
            ->getConnection()
            ->prepare($sql_statement);

        // Bind data
        static::$adapter->bind($pdo_statement, $data);

        // Execute query
        $pdo_statement->execute();

        return Sanitize::make($pdo_statement->fetch());
    }

    /**
     * Execute an insert query
     *
     * @param  $sql_statement
     * @param  array        $data
     * @return int
     */
    public static function insert(string $sql_statement, array $data = []): int
    {
        static::verifyConnection();

        if (
            !preg_match(
                "/^insert\s+into\s+[\w\d_-`]+\s?(\(.+\))?\s+(values\s?(\(.+\),?)+|\s?set\s+(.+)+);?$/i",
                $sql_statement
            )
        ) {
            throw new DatabaseException(
                'Syntax Error on the Request',
                E_USER_ERROR
            );
        }

        if (empty($data)) {
            $pdo_statement = static::$adapter->getConnection()->prepare($sql_statement);

            $pdo_statement->execute();

            return $pdo_statement->rowCount();
        }

        $collection = [];

        $result = 0;

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $result += static::executePrepareQuery($sql_statement, $value);
                continue;
            }

            $collection[$key] = $value;
        }

        if (!empty($collection)) {
            return static::executePrepareQuery($sql_statement, $collection);
        }

        return $result;
    }

    /**
     * Executes a request of type DROP | CREATE TABLE | TRUNCATE | ALTER Builder
     *
     * @param string $sql_statement
     * @return bool
     */
    public static function statement(string $sql_statement): bool
    {
        static::verifyConnection();

        return static::$adapter
            ->getConnection()
            ->exec($sql_statement) === 0;
    }

    /**
     * Execute a delete request
     *
     * @param  $sql_statement
     * @param  array        $data
     * @return int
     */
    public static function delete(string $sql_statement, array $data = []): int
    {
        static::verifyConnection();

        if (!preg_match("/^delete\s+from\s+[\w\d_`]+\s+where\s+.+;?$/i", $sql_statement)) {
            throw new DatabaseException(
                'Syntax Error on the Request',
                E_USER_ERROR
            );
        }

        return static::executePrepareQuery($sql_statement, $data);
    }

    /**
     * Load the query builder factory on table name
     *
     * @param string $table
     * @return QueryBuilder
     */
    public static function table(string $table): QueryBuilder
    {
        static::verifyConnection();

        $table = static::$adapter->getTablePrefix() . $table;

        return new QueryBuilder(
            $table,
            static::$adapter->getConnection()
        );
    }

    /**
     * Starting the start of a transaction
     *
     * @param callable $callback
     * @return void
     */
    public static function startTransaction(?callable $callback = null): void
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
     * Check if database execution is in transaction
     *
     * @return bool
     */
    public static function inTransaction(): bool
    {
        static::verifyConnection();

        return static::$adapter->getConnection()->inTransaction();
    }

    /**
     * Validate a transaction
     */
    public static function commit(): void
    {
        static::verifyConnection();

        static::$adapter->getConnection()->commit();
    }

    /**
     * Cancel a transaction
     */
    public static function rollback(): void
    {
        static::verifyConnection();

        static::$adapter->getConnection()->rollBack();
    }

    /**
     * Starts the verification of the connection establishment
     *
     * @throws
     */
    private static function verifyConnection(): void
    {
        if (is_null(static::$adapter)) {
            static::connection(static::$name);
        }
    }

    /**
     * Retrieves the identifier of the last record.
     *
     * @param  ?string $name
     * @return int|string
     */
    public static function lastInsertId(?string $name = null): int|string
    {
        static::verifyConnection();

        return static::$adapter->getConnection()->lastInsertId($name);
    }

    /**
     * Execute the request of type delete insert update
     *
     * @param string $sql_statement
     * @param array $data
     * @return int
     */
    private static function executePrepareQuery(string $sql_statement, array $data = []): int
    {
        $pdo_statement = static::$adapter
            ->getConnection()
            ->prepare($sql_statement);

        static::$adapter->bind(
            $pdo_statement,
            Sanitize::make($data, true)
        );

        $pdo_statement->execute();

        return $pdo_statement->rowCount();
    }

    /**
     * PDO, returns the instance of the connection.
     *
     * @return PDO
     */
    public static function getPdo(): PDO
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
    public function __call(string $method, array $arguments)
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
