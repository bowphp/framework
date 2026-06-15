<?php

declare(strict_types=1);

namespace Bow\Database;

use PDO;
use ErrorException;
use Bow\Security\Sanitize;
use Bow\Database\QueryEvent;
use Bow\Database\Exception\DatabaseException;
use Bow\Database\Connection\AbstractConnection;
use Bow\Database\Exception\ConnectionException;
use Bow\Database\Connection\Adapters\MysqlAdapter;
use Bow\Database\Connection\Adapters\SqliteAdapter;
use Bow\Database\Connection\Adapters\PostgreSQLAdapter;

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
     * @var ?Database
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
     * @var ?string
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
     * Connection, starts the connection on the DB
     *
     * @param  ?string $name
     * @return ?Database
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

        return static::getInstance();
    }

    /**
     * Resolve the write (primary) connection.
     *
     * @return PDO
     */
    private static function writeConnection(): PDO
    {
        static::ensureDatabaseConnection();

        return static::$adapter->getWriteConnection();
    }

    /**
     * Resolve the read (replica) connection.
     *
     * While a transaction is open on the primary, reads are routed to the
     * primary so they observe their own uncommitted changes.
     *
     * @return PDO
     */
    private static function readConnection(): PDO
    {
        static::ensureDatabaseConnection();

        if (
            static::$adapter->hasWriteConnection()
            && static::$adapter->getWriteConnection()->inTransaction()
        ) {
            return static::$adapter->getWriteConnection();
        }

        return static::$adapter->getReadConnection();
    }

    /**
     * Returns the Database instance
     *
     * @return Database
     */
    public static function getInstance(): Database
    {
        static::ensureDatabaseConnection();

        return static::$instance;
    }

    /**
     * Starts the verification of the connection establishment
     *
     * @throws
     */
    private static function ensureDatabaseConnection(): void
    {
        if (is_null(static::$adapter)) {
            static::connection(static::$name);
        }
    }

    /**
     * Get the connexion name
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
        static::ensureDatabaseConnection();

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
        static::ensureDatabaseConnection();

        if (preg_match("/^update\s[\w\d_`]+\s+\bset\b\s.+\s\bwhere\b\s+.+$/i", $sql_statement)) {
            return static::executePrepareQuery($sql_statement, $data);
        }

        return 0;
    }

    /**
     * Execute the request of type delete insert update
     *
     * @param  string $sql_statement
     * @param  array  $data
     * @return int
     */
    private static function executePrepareQuery(string $sql_statement, array $data = []): int
    {
        $pdo_statement = static::writeConnection()
            ->prepare($sql_statement);

        static::$adapter->bind(
            $pdo_statement,
            Sanitize::make($data, true)
        );

        $start_at = microtime(true);
        $pdo_statement->execute();
        $ended_at = microtime(true);

        static::triggerQueryEvent($sql_statement, $ended_at - $start_at, $data);

        return $pdo_statement->rowCount();
    }

    /**
     * Execute a SELECT request
     *
     * @param  string $sql_statement
     * @param  array  $data
     * @return mixed|null
     */
    public static function select(string $sql_statement, array $data = []): mixed
    {
        static::ensureDatabaseConnection();

        if (!preg_match("/^\s*select\b/i", $sql_statement)) {
            throw new DatabaseException(
                'Syntax Error on the Request: ' . $sql_statement,
                E_USER_ERROR
            );
        }

        $pdo_statement = static::readConnection()
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
    public static function selectOne(string $sql_statement, array $data = []): mixed
    {
        static::ensureDatabaseConnection();

        if (!preg_match("/^\s*select\b/i", $sql_statement)) {
            throw new DatabaseException(
                'Syntax Error on the Request: ' . $sql_statement,
                E_USER_ERROR
            );
        }

        // Prepare query
        $pdo_statement = static::readConnection()
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
     * @param  string $sql_statement
     * @param  array  $data
     * @return int
     */
    public static function insert(string $sql_statement, array $data = []): int
    {
        static::ensureDatabaseConnection();

        if (!preg_match("/^\s*insert\b/i", $sql_statement)) {
            throw new DatabaseException(
                'Syntax Error on the Request: ' . $sql_statement,
                E_USER_ERROR
            );
        }

        if (empty($data)) {
            $pdo_statement = static::writeConnection()->prepare($sql_statement);

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
     * @param  string $sql_statement
     * @return bool
     */
    public static function statement(string $sql_statement): bool
    {
        static::ensureDatabaseConnection();

        $sql_statement = trim($sql_statement);

        return static::writeConnection()
            ->exec($sql_statement) === 0;
    }

    /**
     * Execute a delete request
     *
     * @param  string $sql_statement
     * @param  array  $data
     * @return int
     */
    public static function delete(string $sql_statement, array $data = []): int
    {
        static::ensureDatabaseConnection();

        if (!preg_match("/^\s*delete\b/i", $sql_statement)) {
            throw new DatabaseException(
                'Syntax Error on the Request',
                E_USER_ERROR
            );
        }

        return static::executePrepareQuery($sql_statement, $data);
    }

    /**
     * Load the query builder factory on the table name
     *
     * @param  string $table
     * @return QueryBuilder
     */
    public static function table(string $table): QueryBuilder
    {
        static::ensureDatabaseConnection();

        $table = static::$adapter->getTablePrefix() . $table;

        return new QueryBuilder(
            $table,
            static::$adapter
        );
    }

    /**
     * Starting the start of a transaction wrapper on top of the callback
     *
     * @param  callable $callback
     * @return mixed
     */
    public static function transaction(callable $callback): mixed
    {
        static::startTransaction();

        try {
            $result = call_user_func_array($callback, []);

            static::commit();

            return $result;
        } catch (DatabaseException $e) {
            static::rollback();

            throw $e;
        }
    }

    /**
     * Starting the start of a transaction
     *
     * @return void
     */
    public static function startTransaction(): void
    {
        static::ensureDatabaseConnection();

        if (!static::writeConnection()->inTransaction()) {
            static::writeConnection()->beginTransaction();
        }
    }

    /**
     * Check if database execution is in the transaction
     *
     * @return bool
     */
    public static function inTransaction(): bool
    {
        static::ensureDatabaseConnection();

        return static::writeConnection()->inTransaction();
    }

    /**
     * Validate a transaction
     */
    public static function commit(): void
    {
        static::commitTransaction();
    }

    /**
     * Validate a transaction
     */
    public static function commitTransaction(): void
    {
        if (static::inTransaction()) {
            static::writeConnection()->commit();
        }
    }

    /**
     * Cancel a transaction
     */
    public static function rollback(): void
    {
        static::rollbackTransaction();
    }

    /**
     * Cancel a transaction
     */
    public static function rollbackTransaction(): void
    {
        if (static::inTransaction()) {
            static::writeConnection()->rollBack();
        }
    }

    /**
     * Retrieves the identifier of the last record.
     *
     * @param  ?string $name
     * @return int|string|PDO
     */
    public static function lastInsertId(?string $name = null): int|string|PDO
    {
        static::ensureDatabaseConnection();

        if ($name === null) {
            return static::writeConnection();
        }

        return static::writeConnection()->lastInsertId($name);
    }

    /**
     * PDO, returns the instance of the connection.
     *
     * @return PDO
     */
    public static function getPdo(): PDO
    {
        return static::writeConnection();
    }

    /**
     * Modify the PDO instance
     *
     * @param PDO $pdo
     */
    public static function setPdo(PDO $pdo): void
    {
        static::$adapter->setConnection($pdo);
    }

    /**
     * Trigger the query executed event
     *
     * @param  string $sql
     * @param  float $execution_time
     * @param  array  $bindings
     * @return void
     */
    public static function triggerQueryEvent(string $sql, float $execution_time = 0, array $bindings = []): void
    {
        $event = new QueryEvent($sql, $execution_time, $bindings);

        app_event($event);
    }

    /**
     * __call
     *
     * @param  string $method
     * @param  array  $arguments
     * @return mixed
     * @throws DatabaseException|ErrorException
     */
    public function __call(string $method, array $arguments)
    {
        if (is_null(static::$instance)) {
            throw new ErrorException(
                "Unable to get database instance before configuration"
            );
        }

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
